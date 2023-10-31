<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class ListEventDto
{
    public int $telegram_user_id;
    public string $name;
    public $date_event_at;
    public string $notification_type;
    public string $notification_time_at;
    public string $type;
    public int $group_id;
    public string $period;

}