<?php

namespace Arthem\Bundle\FileBundle\EventListener;

use Arthem\Bundle\FileBundle\Model\FileInterface;
use Arthem\Bundle\FileBundle\Storage\PathStrategy\PathStrategyInterface;
use Arthem\Bundle\FileBundle\Storage\StorageAdapterInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadableListener implements EventSubscriber
{
    /**
     * @var UploadableManager
     */
    private $uploadableManager;

    /**
     * @var StorageAdapterInterface|null
     */
    private $storageAdapter;

    /**
     * @var PathStrategyInterface|null
     */
    private $pathStrategy;

    private $uploadedFiles = [];

    public function __construct(UploadableManager $uploadableManager)
    {
        $this->uploadableManager = $uploadableManager;
    }

    public function setStorageAdapter(StorageAdapterInterface $storageAdapter = null)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function setPathStrategy(PathStrategyInterface $pathStrategy = null)
    {
        $this->pathStrategy = $pathStrategy;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postFlush,
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof FileInterface && $object->getFile() && !$object->isPlaceholder()) {
            if (null !== $this->pathStrategy) {
                $object->setPath($this->pathStrategy->getPath($object));
            }

            $this->triggerUpdate($object, $object->getFile());
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        while ($file = array_shift($this->uploadedFiles)) {
            $key = $file->getPath();
            $this->storageAdapter->store($key, file_get_contents(
                GedmoUploadableListener::getUploadTmpDir().'/'.$file->getPath()
            ));
        }
    }

    public function triggerUpdate(FileInterface $object, UploadedFile $file)
    {
        $this->uploadableManager->markEntityToUpload($object, $file);

        if (null !== $this->storageAdapter) {
            $this->uploadedFiles[spl_object_hash($object)] = $object;
        }
    }
}
