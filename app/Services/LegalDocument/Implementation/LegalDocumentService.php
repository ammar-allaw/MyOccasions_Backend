<?php

namespace App\Services\LegalDocument\Implementation;

use App\Models\LegalDocument;
use App\Repositories\LegalDocument\Interface\LegalDocumentRepositoryInterface;
use App\Services\LegalDocument\Interface\LegalDocumentServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class LegalDocumentService implements LegalDocumentServiceInterface
{
    public function __construct(
        private LegalDocumentRepositoryInterface $legalDocumentRepository,
    ) {}

    public function publicList(array $filters = []): Collection
    {
        $filters['is_active'] = true;

        return $this->legalDocumentRepository->query($filters)->get();
    }

    public function ownerList(array $filters = []): Collection
    {
        return $this->legalDocumentRepository->query($filters)->get();
    }

    public function publicShow(int $id): LegalDocument
    {
        $legalDocument = $this->legalDocumentRepository->findById($id);

        if (! $legalDocument->is_active) {
            throw (new ModelNotFoundException())->setModel(LegalDocument::class, [$id]);
        }

        return $legalDocument;
    }

    public function ownerShow(int $id): LegalDocument
    {
        return $this->legalDocumentRepository->findById($id);
    }

    public function store(array $data): LegalDocument
    {
        return DB::transaction(fn () => $this->legalDocumentRepository->create($data));
    }

    public function update(int $id, array $data): LegalDocument
    {
        return DB::transaction(function () use ($id, $data) {
            $legalDocument = $this->legalDocumentRepository->findById($id);

            return $this->legalDocumentRepository->update($legalDocument, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $legalDocument = $this->legalDocumentRepository->findById($id);

            return $this->legalDocumentRepository->delete($legalDocument);
        });
    }
}
