<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Illuminate\Database\Eloquent\Model;

class TelegramGroup extends Model
{
    protected $table = 'telegram_group';

    protected $guarded = [];
}