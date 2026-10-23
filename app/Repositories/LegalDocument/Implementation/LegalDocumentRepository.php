<?php

namespace App\Repositories\LegalDocument\Implementation;

use App\Models\LegalDocument;
use App\Repositories\LegalDocument\Interface\LegalDocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class LegalDocumentRepository implements LegalDocumentRepositoryInterface
{
    public function query(array $filters = []): Builder
    {
        return LegalDocument::query()
            ->when(array_key_exists('is_active', $filters), function (Builder $query) use ($filters) {
                $query->where('is_active', (bool) $filters['is_active']);
            })
            ->when(! empty($filters['type']), function (Builder $query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->latest();
    }

    public function findById(int $id): LegalDocument
    {
        return LegalDocument::query()->findOrFail($id);
    }

    public function create(array $data): LegalDocument
    {
        return LegalDocument::query()->create($data);
    }

    public function update(LegalDocument $legalDocument, array $data): LegalDocument
    {
        $legalDocument->update($data);

        return $legalDocument->refresh();
    }

    public function delete(LegalDocument $legalDocument): bool
    {
        return (bool) $legalDocument->delete();
    }
}
