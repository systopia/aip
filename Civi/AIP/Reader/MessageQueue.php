<?php
/*-------------------------------------------------------+
| SYSTOPIA Automatic Input Processing (AIP) Framework    |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

namespace Civi\AIP\Reader;


use Cassandra\Exception\TimeoutException;
use Civi\FormProcessor\API\Exception;
use CRM_Aip_ExtensionUtil as E;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class MessageQueue extends Base
{
    public $exchange = 'router';
    public $queue = 'msgs';
    public $consumerTag = 'consumer';
    public AMQPChannel $channel;
    public $timeout = 1000;
    public array $receivedMessages = [];
    public AMQPMessage $currentMessage;
    public AMQPStreamConnection $connection;

    public function __construct() {
        parent::__construct();
    }

    /**
     * The file this is working on
     *
     * @var resource $current_file_handle
     */
    protected $current_file_handle = null;

    /**
     * The headers of the current CSV file
     *
     * @var ?array $current_file_headers
     */
    protected ?array $current_file_headers = null;

    /**
     * The record currently being processed
     *
     * @var ?array
     */
    protected ?array $current_record = null;

    /**
     * The record to be processed next
     *
     * @var ?array
     */
    protected ?array $lookahead_record = null;

    /**
     * The record that was processed last
     *
     * @var ?array
     */
    protected ?array $last_processed_record = null;

    /**
     * Check if the component is ready,
     *   i.e. configured correctly.
     *
     * @throws \Exception
     *   an exception will be thrown if something's wrong with the
     *     configuration or state
     */
    public function verifyConfiguration()
    {
        # read config values
        $requiredConfigParams = ['host', 'port', 'vhost'];
        $optionalConfigParams = ['user', 'pass'];
        $sslConfigParams = ['cafile', 'local_cert', 'local_pk', 'verify_peer', 'verify_peer_name'];
        // get required config params
        foreach ($requiredConfigParams as $param){
            $this->config[$param] = $this->getConfigValue($param);
            if (empty($this->config[$param])) {
                throw new \Exception("No '".$param."' set");
            }
        }
        // get optional params
        foreach ($sslConfigParams as $param)
            $this->config[$param] = $this->getConfigValue($param);
        // get optional ssl config params
        $sslOptions = [];
        foreach ($sslConfigParams as $param)
            $sslOptions[$param] = $this->getConfigValue($param);
        if (count($sslOptions))
            $this->config['sslOptions'] = $sslOptions;
    }

    protected function connect(): ?AMQPStreamConnection
    {
        // try to create connection
        try {
            // connect to AMQP
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['vhost'],
                $this->config['sslOptions']
            );
            // return connection so Reader can work with  it.
            return $this->connection;
        } catch (AMQPRuntimeException $e) {
            $this->log('AMQPRuntimeException Error encountered: ' . $e->getMessage(), 'error');
            cleanup_connection();
            return null;
        } catch (\RuntimeException $e) {
            $this->log('RuntimeException Error encountered: ' . $e->getMessage(), 'error');
            cleanup_connection();
            return null;
        } catch (\ErrorException $e) {
            $this->log('ErrorException Error encountered: ' . $e->getMessage(), 'error');
            cleanup_connection();
            return null;
        }
    }

    public function canReadSource(string $source): bool
    {
        $connection = $this->connect();
        // connect to the AMQP Message Queue
        try {
            // declare and bind queue
            $this->channel = $connection->channel();
            $this->channel->queue_declare($this->queue, false, true, false, false);
            $this->channel->exchange_declare($this->exchange, AMQPExchangeType::DIRECT, false, true, false);
            $this->channel->queue_bind($this->queue, $this->exchange);
        }catch(AMQPTimeoutException $ex){
            $this->log('AMQPTimeoutException encountered: ' . $ex->getMessage(), 'error');
            return false;
        } catch (Exception $ex) {
            $this->log('Error encountered: ' . $ex->getMessage(), 'error');
            return false;
        }
        // Conection was successful
        return true;
    }

    function process_message($message)
    {
        echo "\n--------\n";
        echo $message->body;
        echo "\n--------\n";

        $message->ack();

        // Send a message with the string "quit" to cancel the consumer.
        if ($message->body === 'quit') {
            $message->getChannel()->basic_cancel($message->getConsumerTag());
        }
    }

    function shutdown($channel, $connection)
    {
        $channel->close();
        $connection->close();
    }

    public function process(AMQPMessage $msg)
    {
        // push message to internal Queue
        array_push($this->receivedMessages,$msg);
    }

    /**
     * Open and init the CSV file
     *
     * @throws \Exception
     *   any issues with opening/reading the file
     */
    public function initialiseWithSource($source)
    {
        parent::initialiseWithSource($source);

        // connect to Queue and register Callback
        // register process message callback
        $this->channel->basic_consume($this->queue, $this->consumerTag, false, false, false, false, array($this, 'process'));
        // register shutdown callback
        register_shutdown_function('shutdown', $this->channel, $this->connection);
        // Loop as long as the channel has callbacks registered
        // $this->channel->consume();
    }


    /**
     * Open the given source
     *
     * @param string $source
     *
     * @return void
     *
     * @throws \Exception
     *   if the file couldn't be opened
     */
    protected function openFile(string $source)
    {
        if ($this->current_file_handle) {
            $this->raiseException(E::ts("There is already an open file", [1 => $source]));
        }

        // check if accessible
        if (!$this->canReadSource($source)) {
            $this->raiseException(E::ts("Cannot open source '%1'.", [1 => $source]));
        }

        // open the file
        $this->current_file_handle = fopen($source, 'r');
        if (empty($this->current_file_handle)) {
            $this->raiseException(E::ts("Cannot read source '%1'.", [1 => $source]));
        }

        // update state
        $this->setCurrentFile($source);

        // read first record
        $this->lookahead_record = $this->readNextRecord();
    }

    public function hasMoreRecords(): bool
    {
        // always true because we want the reader to listen to the queue all the time
        return true;
    }

    protected function setTimeout(): void
    {
        $processing_time_limit = $this->getConfigValue('processing_limit/processing_time');
        $this->timeout = microtime(true) + (float) $processing_time_limit;
    }

    protected function shouldWait(): bool
    {
        $timestamp = microtime(true);
        if ($this->timeout && $timestamp > $this->timeout) {
            $this->log("Process time limit hit.");
            return false;
        }
        return true;
    }

    /**
     * Get the next record from the file
     *
     * @return array|null
     *   a record, or null if there are no more records
     *
     * @throws \Exception
     *   if there is a read error
     */
    public function getNextRecord(): ?array
    {
        $this->setTimeout();
        while($this->shouldWait()){
            if (count($this->receivedMessages)) {
                // get received message
                $this->currentMessage = array_shift($this->receivedMessages);

                // Todo: refactor message format
                $this->current_file_handle = $this->currentMessage;

                // return record
                return $this->current_record;
            }else{
                // wait for message
                usleep(30000);
            }
        }
        // if timed out throw TimeOutException
        throw new TimeoutException("Listening to Messages timed out.");
    }

    /**
     * Read the next record from the open file
     *
     * @todo needed?
     */
    public function skipNextRecord() {
        /*
        if (empty($this->current_file_handle)) {
            throw new \Exception("No file handle!");
        }

        // read record
        $separator = $this->getConfigValue('csv_separator', ';');
        $enclosure = $this->getConfigValue('csv_string_enclosure', '"');
        $escape = $this->getConfigValue('csv_string_escape', '\\');
        fgetcsv($this->current_file_handle, $separator, $enclosure, $escape);
        */
    }

    /**
     * Read the next record from the open file
     */
    public function readNextRecord() {
        /*
        if (empty($this->current_file_handle)) {
            throw new \Exception("No file opened.");
        }

        // read record
        // todo: move to class properties?
        $separator = $this->getConfigValue('csv_separator', ';');
        $enclosure = $this->getConfigValue('csv_string_enclosure', '"');
        $escape = $this->getConfigValue('csv_string_escape', '\\');
        $encoding = $this->getConfigValue('csv_string_encoding', 'UTF8');
        $skip_empty_lines = $this->getConfigValue('skip_empty_lines', false);

        $record = fgetcsv($this->current_file_handle, null, $separator, $enclosure, $escape);

        // check for empty lines
        if ($skip_empty_lines) {
            if (is_array($record) && is_null(current($record)) && count($record) <= 1) {
                // this is an empty line, move on to the next one
                // todo: address recursion issue for files _only_ consisting of line breaks
                $this->increaseLinesSkipped();
                return $this->readNextRecord();
            }
        }



        if ($record) {
            // apply the encoding
            // encode record using utf8_encode helper
            if ($encoding != 'UTF8') {
                if ($encoding == 'utf8_encode') {
                    // use the utf8_encode function
                    $new_record = [];
                    foreach ($record as $key => $value) {
                        $new_record[$key] = utf8_encode($value);
                    }
                    $record = $new_record;
                } else {
                    // use mb_convert
                    $record = mb_convert_encoding($record, 'UTF8', $encoding);
                }
            }
        } else {
            // this should be the end of the file
            $record = null;
        }

        return $record;
        */
    }

    public function markLastRecordProcessed()
    {
        // send ack to message broker
        $this->currentMessage->ack();

        // calculate internal countings
        $this->records_processed_in_this_session++;
        $this->setProcessedRecordCount($this->getProcessedRecordCount() + 1);
        $this->current_record = $this->lookahead_record;
    }

    public function markLastRecordFailed()
    {
        $this->records_processed_in_this_session++;
        $this->setFailedRecordCount($this->getFailedRecordCount() + 1);
        $this->current_record = $this->lookahead_record;
    }

    /**
     * The file this is working on
     *
     * @return string the current file path/url
     */
    public function getCurrentFile() : ?string
    {
        return "AMQP Message Broker";
    }

    /**
     * The file this is working on
     *
     * @param $file string the current file path/url
     */
    protected function setCurrentFile($file)
    {
        return $this->setStateValue('current_file', $file);
    }

    public function resetState()
    {
        $this->setStateValue('current_file', null);
        parent::resetState();
    }

    /**
     * Mark the given resource as processed/completed
     *
     * @param string $uri
     *   an URI to marked processed/completed
     */
    public function markSourceProcessed(string $uri)
    {
        $this->setStateValue('current_file', null);
    }

    /**
     * Mark the given resource as failed
     *
     * @param string $uri
     *   an URI to marked as FAILED
     */
    public function markSourceFailed(string $uri)
    {
        $this->setStateValue('current_file', null);
    }


    /**
     * Fix a mismatch of the column count of the headers,
     *  and the number of entries in the record
     *
     * @param array $file_headers
     *   the column headers
     *
     * @param array $record
     *   the record
     *
     * @return void
     */
    protected function fixHeaderRecordColumnMismatch(array &$file_headers, array &$record)
    {
        // if there are not enough headers, just add some generic ones
        while (count($file_headers) < count($record)) {
            $file_headers[] = "Column " . (count($file_headers) + 1);
        }

        // if there are not enough values, just add some empty ones
        while (count($file_headers) > count($record)) {
            $record[] = '';
        }
    }

    /**
     * Simply increases the 'lines_skipped' counter
     */
    protected function increaseLinesSkipped()
    {
        $lines_skipped = (int) $this->getStateValue('lines_skipped');
        $lines_skipped++;
        $this->setStateValue('lines_skipped', $lines_skipped);
    }



    public function cleanup_connection() {
        // Connection might already be closed.
        // Ignoring exceptions.
        try {
            if($this->connection !== null) {
                $this->connection->close();
            }
        } catch (\ErrorException $e) {
        }
    }
}