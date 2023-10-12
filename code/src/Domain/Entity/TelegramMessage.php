<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Art\Code\Domain\ValueObject\Id;

class TelegramMessage
{
    public function __construct(
        private readonly Id $id
    )
    {

    }

    public function getId(): Id
    {
        return $this->id;
    }
}