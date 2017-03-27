<?php

namespace Arthem\Bundle\FileBundle\Controller;

use Arthem\Bundle\FileBundle\Doctrine\ImageCropManager;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileController extends Controller
{
    public function uploadAction()
    {
        $fileManager = $this->get('arthem_file.file_upload_manager');

        return $fileManager->handleForm();
    }

    public function deleteAction(Request $request)
    {
        $id = $request->get('id');
        $class = $this->container->getParameter('arthem_file.model.file.class');

        /** @var ObjectManager $om */
        $om = $this->get('arthem_file.manager_registry')->getManagerForClass($class);

        $file = $om->getRepository($class)->find($id);
        if (!$file) {
            throw $this->createNotFoundException('File not found');
        }

        $om->remove($file);
        $om->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([]);
        }

        $translator = $this->get('translator');
        $this->get('session')->getFlashBag()->add('success', $translator->trans('flashes.delete.success', [], 'ArthemFileBundle'));

        return $this->redirect($request->headers->get('referer'));
    }

    public function imageCropAction(Request $request)
    {
        /** @var ImageCropManager $imageCropManager */
        $imageCropManager = $this->get('arthem_file.image_crop_manager');
        $image = $imageCropManager->getImage($request->get('id'));

        $r = $request->request;
        $filter = $r->get('filter');
        $crop = $imageCropManager->crop($image, $r->get('origin_filter'), $filter, $r->get('crop'));

        $imageManager = $this->get('arthem_file.image_manager');

        return new JsonResponse([
            'url' => $imageManager->getImagePath($image, $filter),
            'crop' => [
                'l' => (float) $crop->getLeft(),
                't' => (float) $crop->getTop(),
                'w' => (float) $crop->getWidth(),
                'h' => (float) $crop->getHeight(),
            ],
        ]);
    }
}
