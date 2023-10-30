<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\ListEventRepositoryInterface;
use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Entity\ListEvent;
use Illuminate\Database\Eloquent\Collection;

class ListEventRepository implements ListEventRepositoryInterface
{
    public function create(ListEventDto $listEventDto): ListEvent
    {
        return ListEvent::create($listEventDto);
    }

    public function getListByUser(int $id): Collection
    {
        return ListEvent::where('telegram_user_id', '=', $id)->get();
    }
}