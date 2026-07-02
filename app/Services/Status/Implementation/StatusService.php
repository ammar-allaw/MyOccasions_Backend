<?php

namespace App\Services\Status\Implementation;

use App\Repositories\Status\Interface\StatusRepositoryInterface;
use App\Services\Status\Interface\StatusServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class StatusService implements StatusServiceInterface
{
    public function __construct(
        private StatusRepositoryInterface $statusRepository,
    ) {}

    public function list(): Collection
    {
        return $this->statusRepository->all();
    }
}
