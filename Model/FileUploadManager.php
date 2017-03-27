<?php


namespace Arthem\Bundle\FileBundle\Model;

use Arthem\Bundle\FileBundle\ImageManager;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
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

    protected $requestStack;

    function __construct(
        ObjectManager $om,
        FormFactoryInterface $formFactory,
        ImageManager $imageManager,
        PackageInterface $assetsHelper,
        TranslatorInterface $translator,
        RequestStack $requestStack)
    {
        $this->om = $om;
        $this->formFactory = $formFactory;
        $this->imageManager = $imageManager;
        $this->assetsHelper = $assetsHelper;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * @param array $fileOptions
     * @return Form
     */
    public function getForm(array $fileOptions = [])
    {
        $formBuilder = $this->formFactory->createNamedBuilder('file', 'form', null, [
            'intention' => 'file',
        ]);
        $formBuilder->add('file', 'arthem_file', $fileOptions);

        return $formBuilder->getForm();
    }

    /**
     * @param \Closure|null $callback
     * @param array $fileOptions
     * @return JsonResponse
     */
    public function handleForm(\Closure $callback = null, array $fileOptions = [])
    {
        $form = $this->getForm($fileOptions);
        $form->handleRequest($this->requestStack->getCurrentRequest());
        if ($form->isValid()) {
            /** @var FileInterface $data */
            $data = $form->get('file')->getData();
            if ($callback) {
                call_user_func($callback, $data, $this->om);
            }
            $this->om->persist($data);
            $this->om->flush();

            $file = $this->getFileResponse($data);

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

    public function getFileResponse(FileInterface $file)
    {
        if (strpos($file->getMimeType(), 'image/') === 0) {
            if ($originFilterName = $this->requestStack->getCurrentRequest()->get('origin_filter_name')) {
                $fileUrl = $this->imageManager->getImagePath($file, $originFilterName);
            } else {
                $fileUrl = $this->assetsHelper->getUrl($file->getPath());
            }
            if ($filterName = $this->requestStack->get('filter_name')) {
                $thumbnailUrl = $this->imageManager->getImagePath($file, $filterName);
            }
        } else {
            $fileUrl = $this->assetsHelper->getUrl($file->getPath());
        }

        $file = [
            'id' => $file->getId(),
            'url' => $fileUrl
        ];
        if (isset($thumbnailUrl)) {
            $file['thumbnail_url'] = $thumbnailUrl;
        }

        return $file;
    }

    private function getErrors(FormInterface $form, array &$errors)
    {
        foreach ($form->getErrors() as $error) {
            $errors[] = $this->translator->trans($error->getMessage(), $error->getMessageParameters(), 'ArthemFileBundle');
        }
        foreach ($form as $child) {
            $this->getErrors($child, $errors);
        }
    }
}
