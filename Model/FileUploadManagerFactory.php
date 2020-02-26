<?php

namespace Arthem\Bundle\FileBundle\Model;

use Arthem\Bundle\FileBundle\ImageManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FileUploadManagerFactory
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    protected $formFactory;

    protected $imageManager;

    protected $assetsHelper;

    protected $translator;

    protected $requestStack;

    public function __construct(
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        ImageManager $imageManager,
        Packages $assetsHelper,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->formFactory = $formFactory;
        $this->imageManager = $imageManager;
        $this->assetsHelper = $assetsHelper;
        $this->translator = $translator;
    }

    public function createManagerFor(string $className): FileUploadManager
    {
        return new FileUploadManager(
            $this->doctrine->getManagerForClass($className),
            $this->formFactory,
            $this->imageManager,
            $this->assetsHelper,
            $this->translator
        );
    }
}
