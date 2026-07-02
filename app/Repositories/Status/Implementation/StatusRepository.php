<?php

namespace App\Repositories\Status\Implementation;

use App\Models\Status;
use App\Repositories\Status\Interface\StatusRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StatusRepository implements StatusRepositoryInterface
{
    public function all(): Collection
    {
        return Status::query()
            ->orderBy('id')
            ->get();
    }
}
