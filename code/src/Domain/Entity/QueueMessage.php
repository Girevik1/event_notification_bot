<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Illuminate\Database\Eloquent\Model;

class QueueMessage extends Model
{
    protected $table = 'queue_message';

    protected $guarded = [];
}