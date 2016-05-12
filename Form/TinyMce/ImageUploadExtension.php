<?php


namespace Arthem\Bundle\FileBundle\Form\TinyMce;

use Arthem\Bundle\BaseBundle\Form\TinyMce\AbstractTinyMceExtension;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ImageUploadExtension extends AbstractTinyMceExtension
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    function __construct(RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->router           = $router;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    function getPlugins()
    {
        return [
            'arthem_image_upload' => 'bundles/esfileupload/tinymce/plugins/arthem_image_upload/plugin.min.js',
        ];
    }

    function getConfigurations()
    {
        return [
            'image_upload_path' => $this->router->generate('arthem_file_file_upload'),
            'session_token'     => $this->csrfTokenManager->getToken('file')->getValue(),
        ];
    }

    /**
     * Extension name
     *
     * @return string
     */
    function getName()
    {
        return 'arthem_image_upload';
    }
}
