<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    public int $telegram_chat_id;
    protected $table = 'telegram_user';
}