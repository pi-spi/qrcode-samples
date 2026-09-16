<?php

declare(strict_types=1);

namespace PiSpi\Sample;

/**
 * Une entree refusee, avec le code HTTP et l'etape a renvoyer au navigateur.
 *
 * L'etape reprend celle de la demo Django, encode, decode ou svg, pour que les
 * deux implementations affichent la meme chose quand la meme saisie echoue.
 */
final class InvalidInput extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly ?string $step = null,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function step(): ?string
    {
        return $this->step;
    }
}
