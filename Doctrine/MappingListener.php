<?php

namespace Arthem\Bundle\FileUploadBundle\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Arthem\Bundle\BaseBundle\Doctrine\Mapping\AbstractMappingListener;

class MappingListener extends AbstractMappingListener
{
    protected $fileClass;
    protected $fileTable;
    protected $imageCropClass;
    protected $imageCropTable;

    function __construct($fileClass, $fileTable, $imageCropClass = null, $imageCropTable = null)
    {
        $this->fileClass      = $fileClass;
        $this->fileTable      = $fileTable;
        $this->imageCropClass = $imageCropClass;
        $this->imageCropTable = $imageCropTable;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        $className = $metadata->getName();
        if ($className === $this->fileClass) {
            $this->setConcrete($metadata, $this->fileTable);

            if (null !== $this->imageCropClass) {
                $metadata->mapOneToMany([
                    'targetEntity' => $this->imageCropClass,
                    'fieldName'    => 'crops',
                    'cascade'      => ['remove'],
                    'mappedBy'     => 'file',
                ]);

                $metadata->mapField([
                    'fieldName' => 'cropDates',
                    'type'      => 'json_array',
                    'nullable'  => true,
                ]);
            }
        } elseif (null !== $this->imageCropClass && $className === $this->imageCropClass) {
            $this->setConcrete($metadata, $this->imageCropTable);

            $metadata->mapManyToOne([
                'targetEntity' => $this->fileClass,
                'fieldName'    => 'file',
                'inversedBy'   => 'crops',
                'joinColumns'  => [
                    [
                        'onDelete' => 'CASCADE',
                        'nullable' => false,
                    ],
                ],
            ]);
        }
    }
}
