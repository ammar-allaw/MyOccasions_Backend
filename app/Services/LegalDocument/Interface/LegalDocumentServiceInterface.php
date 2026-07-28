<?php

namespace App\Services\LegalDocument\Interface;

use App\Models\LegalDocument;
use Illuminate\Database\Eloquent\Collection;

interface LegalDocumentServiceInterface
{
    public function publicList(array $filters = []): Collection;

    public function ownerList(array $filters = []): Collection;

    public function publicShow(int $id): LegalDocument;

    public function ownerShow(int $id): LegalDocument;

    public function store(array $data): LegalDocument;

    public function update(int $id, array $data): LegalDocument;

    public function delete(int $id): bool;
}
