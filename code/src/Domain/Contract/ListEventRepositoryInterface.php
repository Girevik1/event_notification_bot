<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Entity\ListEvent;
use Illuminate\Database\Eloquent\Collection;

interface ListEventRepositoryInterface
{
    public function create(ListEventDto $listEventDto): ListEvent;

    public function getListByUser(int $userId): Collection;

    public function deleteEventById(int $id, int $userId): int;

    public function updateAllByGroup(int $groupId, int $userId, string $field, mixed $value): int;
}