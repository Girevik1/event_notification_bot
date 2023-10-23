<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Illuminate\Database\Eloquent\Model;

class ListEvent extends Model
{
    protected $table = 'list_event';

    protected $guarded = [];
}