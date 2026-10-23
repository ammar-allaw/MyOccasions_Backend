<?php

namespace App\Repositories\LegalDocument\Interface;

use App\Models\LegalDocument;
use Illuminate\Database\Eloquent\Builder;

interface LegalDocumentRepositoryInterface
{
    public function query(array $filters = []): Builder;

    public function findById(int $id): LegalDocument;

    public function create(array $data): LegalDocument;

    public function update(LegalDocument $legalDocument, array $data): LegalDocument;

    public function delete(LegalDocument $legalDocument): bool;
}
