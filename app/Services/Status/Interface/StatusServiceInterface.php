<?php

namespace App\Services\Status\Interface;

use Illuminate\Database\Eloquent\Collection;

interface StatusServiceInterface
{
    public function list(): Collection;
}
