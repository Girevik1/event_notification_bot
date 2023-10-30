<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\ListEventRepositoryInterface;
use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Entity\ListEvent;

class ListEventRepository implements ListEventRepositoryInterface
{
    public function create(ListEventDto $listEventDto): ListEvent
    {
        return ListEvent::create($listEventDto);
//        ListEvent::create([
//            'telegram_user_id' => new TelegramUserId(1),
//            'name' => 0,
//            'date_event' => 'Developer text',
//            'type' => 0,
//            'group_id' => 'Developer',
//            'notification_time' => 0,
//            'period' => 0,
//        ]);
    }
}