<?php

use CRM_Aip_ExtensionUtil as E;

/**
 * AipProcess entity.
 *
 * Provided by the Automated Input Processing extension.
 *
 * @package Civi\Api4
 */
class CRM_Aip_BAO_AipProcess extends CRM_Aip_DAO_AipProcess
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array {
        return [
            'civi.afform_admin.metadata' => 'afformAdminMetadata',
        ];
    }

    /**
     * Provides Afform metadata about this entity.
     *
     * @see \Civi\AfformAdmin\AfformAdminMeta::getMetadata().
     */
    public static function afformAdminMetadata(GenericHookEvent $event): void {
        $entity = 'AipProcess';
        $event->entities[$entity] = [
            'entity' => $entity,
            'label' => $entity,
            'icon' => NULL, // TODO.
            'type' => 'primary',
            'defaults' => '{}',
        ];
    }
}
