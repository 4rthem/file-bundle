<?php

namespace Arthem\Bundle\FileBundle\Model;

use Arthem\Bundle\FileBundle\Form\Type\FileType;
use Arthem\Bundle\FileBundle\ImageManager;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class FileUploadManager
{
    /**
     * @var ObjectManager
     */
    protected $om;

    protected $formFactory;

    protected $imageManager;

    protected $assetsHelper;

    protected $translator;

    public function __construct(
        ObjectManager $om,
        FormFactoryInterface $formFactory,
        ImageManager $imageManager,
        Packages $assetsHelper,
        TranslatorInterface $translator
    ) {
        $this->om = $om;
        $this->formFactory = $formFactory;
        $this->imageManager = $imageManager;
        $this->assetsHelper = $assetsHelper;
        $this->translator = $translator;
    }

    /**
     * @return FormInterface
     */
    public function getForm(array $fileOptions = [])
    {
        $formBuilder = $this->formFactory->createNamedBuilder('file', FormType::class);
        $formBuilder->add('file', FileType::class, $fileOptions);

        return $formBuilder->getForm();
    }

    public function handleForm(
        Request $request,
        \Closure $callback = null,
        array $fileOptions = [],
        ?callable $urlHandler = null,
        ?callable $onFilePersisted = null
    ) {
        $multiple = $fileOptions['multiple'] ?? false;
        $form = $this->getForm($fileOptions);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var FileInterface|FileInterface[] $data */
            $data = $form->get('file')->getData();
            if ($callback) {
                call_user_func($callback, $data, $this->om);
            }
            if ($multiple) {
                foreach ($data as $d) {
                    $this->om->persist($d);
                }
            } else {
                $this->om->persist($data);
            }
            $this->om->flush();

            if ($multiple) {
                $files = [];
                foreach ($data as $d) {
                    $this->om->persist($d);
                    $files[] = $this->getFileResponse($d, $request, $urlHandler);

                    $onFilePersisted && $onFilePersisted($d);
                }

                $response = new JsonResponse([
                    'files' => $files,
                ]);
            } else {
                $file = $this->getFileResponse($data, $request, $urlHandler);
                $onFilePersisted && $onFilePersisted($data);

                $response = new JsonResponse([
                    'file' => $file,
                ]);
            }

            return $response;
        }

        $errors = [];
        $this->getErrors($form, $errors);

        $response = new JsonResponse([
            'errors' => $errors,
        ], 400);

        return $response;
    }

    public function getFileResponse(FileInterface $file, Request $request, ?callable $urlHandler)
    {
        if (0 === strpos($file->getMimeType(), 'image/')) {
            if ($originFilterName = $request->get('origin_filter_name')) {
                $fileUrl = $this->imageManager->getImagePath($file, $originFilterName);
            } else {
                $fileUrl = null !== $urlHandler ? $urlHandler($file) : $this->assetsHelper->getUrl($file->getPath());
            }
            if ($filterName = $request->get('filter_name')) {
                $thumbnailUrl = $this->imageManager->getImagePath($file, $filterName);
            }
        } else {
            $fileUrl = null !== $urlHandler ? $urlHandler($file) : $this->assetsHelper->getUrl($file->getPath());
        }

        $file = [
            'id' => $file->getId(),
            'url' => $fileUrl,
            'name' => $file->getOriginalFilename(),
            'mime_type' => $file->getMimeType(),
        ];
        if (isset($thumbnailUrl)) {
            $file['thumbnail_url'] = $thumbnailUrl;
        }

        return $file;
    }

    private function getErrors(FormInterface $form, array &$errors)
    {
        /** @var FormError $error */
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getOrigin()->getName().': '.$this->translator->trans(
                $error->getMessage(),
                $error->getMessageParameters(),
                'ArthemFileBundle'
            );
        }
        foreach ($form as $child) {
            $this->getErrors($child, $errors);
        }
    }
}
