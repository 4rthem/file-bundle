<?php

namespace Arthem\Bundle\FileBundle\EventListener;

use Arthem\Bundle\FileBundle\Model\FileInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadableListener implements EventSubscriber
{
    /**
     * @var UploadableManager
     */
    private $uploadableManager;

    public function __construct(UploadableManager $uploadableManager)
    {
        $this->uploadableManager = $uploadableManager;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof FileInterface && $object->getFile() && !$object->isPlaceholder()) {
            $this->triggerUpdate($object, $object->getFile());
        }
    }

    public function triggerUpdate(FileInterface $object, UploadedFile $file)
    {
        $this->uploadableManager->markEntityToUpload($object, $file);
    }
}
