<?php

namespace App\Repositories\Status\Interface;

use Illuminate\Database\Eloquent\Collection;

interface StatusRepositoryInterface
{
    public function all(): Collection;
}
