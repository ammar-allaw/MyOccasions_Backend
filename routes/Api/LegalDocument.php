<?php

use App\Http\Controllers\Api\LegalDocumentController;
use Illuminate\Support\Facades\Route;

Route::controller(LegalDocumentController::class)
    ->prefix('legal-documents')
    ->group(function () {
        Route::get('/', 'publicIndex')->name('legal-documents.public.index');
        Route::get('/{id}', 'publicShow')->name('legal-documents.public.show');
    });

Route::controller(LegalDocumentController::class)
    ->prefix('owner/legal-documents')
    ->middleware(['auth:owner'])
    ->group(function () {
        Route::get('/', 'ownerIndex')->name('owner.legal-documents.index');
        Route::get('/{id}', 'ownerShow')->name('owner.legal-documents.show');
        Route::post('/', 'store')->name('owner.legal-documents.store');
        Route::put('/{id}', 'update')->name('owner.legal-documents.update');
        Route::delete('/{id}', 'destroy')->name('owner.legal-documents.destroy');
    });
