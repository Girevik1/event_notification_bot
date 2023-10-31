<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\ListEventRepositoryInterface;
use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Entity\ListEvent;
use Illuminate\Database\Eloquent\Collection;

class ListEventRepository implements ListEventRepositoryInterface
{
    /**
     * @param ListEventDto $listEventDto
     * @return ListEvent
     */
    public function create(ListEventDto $listEventDto): ListEvent
    {
        return ListEvent::create([
            'name' => $listEventDto->name,
            'date_event_at' => $listEventDto->date_event_at,
            'type' => $listEventDto->type,
            'group_id' => $listEventDto->group_id,
            'telegram_user_id' => $listEventDto->telegram_user_id,
            'notification_time_at' => $listEventDto->notification_time_at,
            'period' => $listEventDto->period
        ]);
    }

    /**
     * @param int $userId
     * @return Collection
     */
    public function getListByUser(int $userId): Collection
    {
        return ListEvent::where('telegram_user_id', '=', $userId)->get();
    }

    /**
     * @param int $id
     * @param int $userId
     * @return mixed
     */
    public function deleteEventById(int $id, int $userId): mixed
    {
        return ListEvent::where('id', $id)
            ->where('telegram_user_id', $userId)
            ->delete();
    }
}