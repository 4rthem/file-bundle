<?php

namespace Arthem\Bundle\FileBundle\Model;

use Arthem\Bundle\FileBundle\Form\Type\FileType;
use Arthem\Bundle\FileBundle\ImageManager;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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
    )
    {
        $this->om = $om;
        $this->formFactory = $formFactory;
        $this->imageManager = $imageManager;
        $this->assetsHelper = $assetsHelper;
        $this->translator = $translator;
    }

    /**
     * @param array $fileOptions
     *
     * @return FormInterface
     */
    public function getForm(array $fileOptions = [])
    {
        $formBuilder = $this->formFactory->createNamedBuilder('file', FormType::class);
        $formBuilder->add('file', FileType::class, $fileOptions);

        return $formBuilder->getForm();
    }

    /**
     * @param Request $request
     * @param \Closure|null $callback
     * @param array $fileOptions
     * @return JsonResponse
     */
    public function handleForm(Request $request, \Closure $callback = null, array $fileOptions = [])
    {
        $form = $this->getForm($fileOptions);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var FileInterface $data */
            $data = $form->get('file')->getData();
            if ($callback) {
                call_user_func($callback, $data, $this->om);
            }
            $this->om->persist($data);
            $this->om->flush();

            $file = $this->getFileResponse($data, $request);

            $response = new JsonResponse([
                'file' => $file,
            ]);

            return $response;
        }

        $errors = [];
        $this->getErrors($form, $errors);

        $response = new JsonResponse([
            'errors' => $errors,
        ], 400);

        return $response;
    }

    public function getFileResponse(FileInterface $file, Request $request)
    {
        if (strpos($file->getMimeType(), 'image/') === 0) {
            if ($originFilterName = $request->get('origin_filter_name')) {
                $fileUrl = $this->imageManager->getImagePath($file, $originFilterName);
            } else {
                $fileUrl = $this->assetsHelper->getUrl($file->getPath());
            }
            if ($filterName = $request->get('filter_name')) {
                $thumbnailUrl = $this->imageManager->getImagePath($file, $filterName);
            }
        } else {
            $fileUrl = $this->assetsHelper->getUrl($file->getPath());
        }

        $file = [
            'id' => $file->getId(),
            'url' => $fileUrl,
        ];
        if (isset($thumbnailUrl)) {
            $file['thumbnail_url'] = $thumbnailUrl;
        }

        return $file;
    }

    private function getErrors(FormInterface $form, array &$errors)
    {
        foreach ($form->getErrors() as $error) {
            $errors[] = $this->translator->trans(
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
