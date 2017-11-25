<?php


namespace Arthem\Bundle\FileBundle\LetterAvatar;


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
     * @var \Twig_Environment
     */
    private $renderer;

    public function __construct(\Twig_Environment $renderer, array $colors, string $font)
    {
        $this->colors = $colors;
        $this->font = $font;
        $this->renderer = $renderer;
    }

    public function generate(string $name): string
    {
        $initials = $this->getInitials($name);
        $index = 0;
        foreach ($initials as $initial) {
            $index += ord($initial) - 65;
        }

        $color = (int) floor($index % count($this->colors));

        return $this->renderer->render('@ArthemFile/Placeholder/letter_avatar.svg.twig', [
            'text' => implode('', $initials),
            'color' => $this->colors[$color],
            'font' => $this->font,
        ]);
    }

    private function getInitials(string $str): array
    {
        $initials = [];
        foreach (preg_split("/\s+/", $str) as $word) {
            $initials[] = strtoupper($word[0]);
            if (count($initials) === 2) {
                break;
            }
        }

        return $initials;
    }
}
