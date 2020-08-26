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

        $color = (int) floor(crc32($name) % count($this->colors));

        return $this->renderer->render('@ArthemFile/Placeholder/letter_avatar.svg.twig', [
            'text' => implode('', $initials),
            'color' => $this->colors[$color],
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
