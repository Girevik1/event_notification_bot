<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class DataEditMessageDto
{
    public string $chat_id;
    public int $message_id;
    public string $text;
    public string $keyboard;
    public array $keyboardData = [];

}