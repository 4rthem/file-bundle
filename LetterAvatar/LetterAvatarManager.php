<?php

namespace Arthem\Bundle\FileBundle\LetterAvatar;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LetterAvatarManager
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function generatePath(string $text): string
    {
        return $this->router->generate('arthem_file_letter_avatar', [
            'text' => $text,
        ]);
    }
}
