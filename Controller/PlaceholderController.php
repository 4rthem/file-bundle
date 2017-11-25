<?php

namespace Arthem\Bundle\FileBundle\Controller;

use Arthem\Bundle\FileBundle\LetterAvatar\AvatarGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PlaceholderController extends Controller
{
    public function letterAvatarAction(string $text)
    {
        $response = new Response(
            $this
                ->get(AvatarGenerator::class)
                ->generate($text),
            200,
            [
                'Content-Type' => 'image/svg+xml',
            ]
        );

        $response->setSharedMaxAge(31536000);
        $response->setMaxAge(31536000);

        return $response;
    }
}
