<?php

namespace Arthem\Bundle\FileBundle\Controller;

use Arthem\Bundle\FileBundle\LetterAvatar\AvatarGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlaceholderController extends AbstractController
{
    public function letterAvatarAction(string $text, Request $request)
    {
        $text = base64_decode(urldecode($text));

        $response = new Response(
            $this
                ->get(AvatarGenerator::class)
                ->generate($text, $request->get('c')),
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
