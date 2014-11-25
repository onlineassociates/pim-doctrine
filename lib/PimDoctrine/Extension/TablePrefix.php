<?php

namespace PimDoctrine\Extension;

use \Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Class TablePrefix
 *
 * @package PimDoctrine\Extension
 */
class TablePrefix
{
    protected $prefix = '';

    /**
     * @param $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = (string)$prefix;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $classMetadata->setTableName(
            $this->prefix
            . $classMetadata->getTableName()
        );

        foreach (
            $classMetadata->getAssociationMappings() as $fieldName => $mapping
        ) {
            if ($mapping['type']
                == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY
            ) {
                $mappedTableName
                    = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];

                $classMetadata->associationMappings[$fieldName]['joinTable']['name']
                    = $this->prefix . $mappedTableName;
            }
        }
    }

}