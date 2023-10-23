<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Dto\ListEventDto;
use Art\Code\Domain\Entity\ListEvent;

interface listEventRepositoryInterface
{
    public function create(ListEventDto $listEventDto): ListEvent;
}