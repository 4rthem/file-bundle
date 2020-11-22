<?php

namespace Arthem\Bundle\FileBundle\LetterAvatar;

use Cocur\Slugify\Slugify;
use Twig\Environment;

class AvatarGenerator
{
    /**
     * @var array
     */
    private $colors;

    /**
     * @var string
     */
    private $font;

    /**
     * @var Environment
     */
    private $renderer;

    public function __construct(Environment $renderer, array $colors, string $font)
    {
        $this->colors = $colors;
        $this->font = $font;
        $this->renderer = $renderer;
    }

    public function generate(string $name): string
    {
        $initials = $this->getInitials($name);

        $color1 = (int) floor(crc32($name) % count($this->colors));
        $color2 = (int) floor(crc32(strrev($name)) % count($this->colors));
        if ($color2 === $color1) {
            $color2 = (int) floor((crc32(strrev($name)) + 1) % count($this->colors));
        }

        return $this->renderer->render('@ArthemFile/Placeholder/letter_avatar.svg.twig', [
            'text' => implode('', $initials),
            'color1' => $this->colors[$color1],
            'color2' => $this->colors[$color2],
            'percent' => round((crc32($name) % 100) / 100),
            'font' => $this->font,
        ]);
    }

    private function getInitials(string $str): array
    {
        $str = trim($str);

        if (class_exists(Slugify::class)) {
            $slugify = new Slugify(['separator' => ' ']);
            $str = $slugify->slugify($str);
        }

        $initials = [];
        if (empty(trim($str))) {
            $str = '--';
        }
        foreach (preg_split('/\s+/', $str) as $word) {
            $initial = strtoupper($word[0]);
            $initials[] = $initial;
            if (2 === count($initials)) {
                break;
            }
        }

        if (1 === count($initials)) {
            if (strlen($str) > 1) {
                $initials[] = strtoupper($str[1]);
            } else {
                $initials = [$initials[0], $initials[0]];
            }
        }

        return $initials;
    }
}
