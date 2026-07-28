<?php

namespace App\Http\Controllers\Api;

use App\Enums\LegalDocumentType;
use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\LegalDocument\StoreLegalDocumentRequest;
use App\Http\Requests\LegalDocument\UpdateLegalDocumentRequest;
use App\Http\Resources\LegalDocument\LegalDocumentResource;
use App\Services\LegalDocument\Interface\LegalDocumentServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalDocumentController extends Controller
{
    public function __construct(
        private Handler $handler,
        private LegalDocumentServiceInterface $legalDocumentService,
    ) {}

    public function publicIndex(Request $request)
    {
        try {
            $filters = $request->validate([
                'type' => ['nullable', 'string', Rule::in(LegalDocumentType::values())],
            ]);

            $legalDocuments = $this->legalDocumentService->publicList($filters);

            return $this->handler->successResponse(
                ['legal_documents' => LegalDocumentResource::collection($legalDocuments)],
                true,
                'success get legal documents',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function publicShow(int $id)
    {
        try {
            $legalDocument = $this->legalDocumentService->publicShow($id);

            return $this->handler->successResponse(
                ['legal_document' => new LegalDocumentResource($legalDocument)],
                true,
                'success get legal document',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Legal document not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function ownerIndex(Request $request)
    {
        try {
            $filters = $request->validate([
                'type' => ['nullable', 'string', Rule::in(LegalDocumentType::values())],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $legalDocuments = $this->legalDocumentService->ownerList($filters);

            return $this->handler->successResponse(
                ['legal_documents' => LegalDocumentResource::collection($legalDocuments)],
                true,
                'success get legal documents',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function ownerShow(int $id)
    {
        try {
            $legalDocument = $this->legalDocumentService->ownerShow($id);

            return $this->handler->successResponse(
                ['legal_document' => new LegalDocumentResource($legalDocument)],
                true,
                'success get legal document',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Legal document not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function store(StoreLegalDocumentRequest $request)
    {
        try {
            $legalDocument = $this->legalDocumentService->store($request->validated());

            return $this->handler->successResponse(
                ['legal_document' => new LegalDocumentResource($legalDocument)],
                true,
                'success create legal document',
                201
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function update(UpdateLegalDocumentRequest $request, int $id)
    {
        try {
            $legalDocument = $this->legalDocumentService->update($id, $request->validated());

            return $this->handler->successResponse(
                ['legal_document' => new LegalDocumentResource($legalDocument)],
                true,
                'success update legal document',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Legal document not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->legalDocumentService->delete($id);

            return $this->handler->successResponse(
                null,
                true,
                'success delete legal document',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->handler->errorResponse(false, 'Legal document not found', null, 404);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }
}
