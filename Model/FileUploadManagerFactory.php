<?php

namespace Arthem\Bundle\FileBundle\Model;

use Arthem\Bundle\FileBundle\ImageManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class FileUploadManagerFactory
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    protected $formFactory;

    protected $imageManager;

    protected $assetsHelper;

    protected $translator;

    protected $requestStack;

    /**
     * @param RegistryInterface $doctrine
     * @param $formFactory
     * @param $imageManager
     * @param $assetsHelper
     * @param $translator
     * @param $requestStack
     */
    public function __construct(
        RegistryInterface $doctrine,
        FormFactoryInterface $formFactory,
        ImageManager $imageManager,
        PackageInterface $assetsHelper,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->doctrine = $doctrine;
        $this->formFactory = $formFactory;
        $this->imageManager = $imageManager;
        $this->assetsHelper = $assetsHelper;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public function createManagerFor(string $className): FileUploadManager
    {
        return new FileUploadManager(
            $this->doctrine->getManagerForClass($className),
            $this->formFactory,
            $this->imageManager,
            $this->assetsHelper,
            $this->translator,
            $this->requestStack
        );
    }
}
