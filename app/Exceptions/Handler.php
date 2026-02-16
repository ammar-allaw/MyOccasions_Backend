<?php

namespace App\Exceptions;

use Dotenv\Exception\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

use function PHPUnit\Framework\isNull;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
        public function render($request, Throwable $exception)
        {
            if ($exception instanceof NotFoundHttpException) {
                $errorMessage= $exception->getMessage();
                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage,
                    'error'=>'NotFoundHttpException'
                ], 404);
            }
    
            if ($exception instanceof QueryException) {
                $errorMessage= $exception->getMessage();
                return response()->json([
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'error' => 'QueryException',
                ], 500);
            }
    
    
            if ($exception instanceof ValidationException) {
                $errorMessage= Arr::flatten($this->getErrorMessage($exception));
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid request parameter',
                    'errors' => $errorMessage,
                ],$this->getStatuesOfException($exception));
            }
        
    
            // if ($exception instanceof ValidationException) {
            //     $errorMessage= $this->getErrorMessage($exception);
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => $errorMessage,
            //         // 'errors' => $exception->errors(),
            //         'errors' => 'ValidationException',
    
            //         // $exception->status
            //     ],$this->getStatuesOfException($exception));
            // }
    
            if ($exception instanceof AuthenticationException) {
                $errorMessage= $exception->getMessage();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated, '.$errorMessage,
                    'error' => 'AuthenticationException',
                ], 401);
            }
    
            if ($exception instanceof AuthorizationException) {
                $errorMessage= $exception->getMessage();
                return response()->json([
                    'status' => 'error',
                    'message' => 'This action is unauthorized, '.$errorMessage,
                    'error' => 'AuthorizationException',
                ], 403);
            }
    
            if ($exception instanceof HttpException) {
                return response()->json([
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'error' => 'HttpException',
                ], 404);
            }
    
            return parent::render($request, $exception);
        }
    
    
        public function successResponse($success=true,$message = null,$data,$statusCode = 200)
        {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'data' => $data,
            ], $statusCode);
        }
    
        
        public function errorResponse($success=false,$message = null,$data=null,$statusCode = 400)
        {
            return response()->json([
                'success'=>$success,
                // 'status' => 'error',
                'message' => $message,
                'data'=>$data,
            ], $statusCode);
        }
    
        protected function getErrorMessage(Throwable $exception)
        {
            // Customize the error message key here
            return $exception->getMessage() ? $exception->getMessage() : 'Unknown Error';
        }
    
        protected function getStatuesOfException(Throwable $exception)
        {
            // Customize the error message key here
            return  method_exists($exception, 'getStatusCode');
        }
    
        public function attachImagesToModel(
            Model $model,
            $images,
            string $collectionName,
            int $currentImageCount = 0,   // عدد الصور الموجودة في DB
            int $maxAllowedImages = 5     // العدد المسموح به
        ): array {
            $savedImages = [];
            // Ensure $images is iterable
            $images = is_array($images) ? $images : [$images];

            // تحقق إذا كان العدد الإجمالي سيتجاوز العدد المسموح
            $totalAfterInsert = $currentImageCount + count($images);
            if ($totalAfterInsert > $maxAllowedImages) {
                throw new \Exception("You can only upload a maximum of {$maxAllowedImages} images. Currently stored: {$currentImageCount}");
            }

            foreach ($images as $image) {
                if ($image instanceof UploadedFile) {
                    /** @var Media $media */
                    $media = $model->addMedia($image)
                        ->usingFileName($image->getClientOriginalName())
                        ->toMediaCollection($collectionName);

                    $media->save();
                    $savedImages[] = $media;
                }
            }

            return $savedImages;
        }


        public function manageImagesOnModel(
            Model $model,
            string $collectionName,
            array|UploadedFile|null $images = null,
            int $maxAllowedImages = 5,
            bool $replaceAll = false,
            $replaceIds = null
        ): array {
            $savedImages = [];
            
            // Case 1: Delete images by ID (image_id provided without images)
            if (!empty($replaceIds) && empty($images)) {
                $replaceIds = is_array($replaceIds) ? $replaceIds : [$replaceIds];
                
                foreach ($replaceIds as $id) {
                    $oldMedia = $model->media()
                        ->where('collection_name', $collectionName)
                        ->where('id', $id)
                        ->first();

                    if (!$oldMedia) {
                        throw new \Exception("Image with ID {$id} not found in collection {$collectionName}");
                    }

                    $oldMedia->delete();
                }

                return $savedImages;
            }
            
            // Case 2: Delete and add images (image_id provided with images)
            if (!empty($replaceIds) && !empty($images)) {
                $replaceIds = is_array($replaceIds) ? $replaceIds : [$replaceIds];
                $images = is_array($images) ? $images : [$images];

                // حذف الصور المحددة
                foreach ($replaceIds as $id) {
                    $oldMedia = $model->media()
                        ->where('collection_name', $collectionName)
                        ->where('id', $id)
                        ->first();

                    if (!$oldMedia) {
                        throw new \Exception("Image with ID {$id} not found in collection {$collectionName}");
                    }

                    $oldMedia->delete();
                    $oldMedia->refresh(); // تحديث الحالة بعد الحذف
                }

                // التحقق من الحد الأقصى بعد الحذف
                $currentCount = $model->getMedia($collectionName)->count();
                $totalAfterInsert = $currentCount + count($images);

                if ($totalAfterInsert > $maxAllowedImages) {
                    throw new \Exception(
                        "You can only upload a maximum of {$maxAllowedImages} images. Currently stored: {$currentCount}, trying to add: " . count($images)
                    );
                }

                // إضافة الصور الجديدة
                foreach ($images as $image) {
                    if ($image instanceof UploadedFile) {
                        $media = $model->addMedia($image)
                            ->usingFileName($image->getClientOriginalName())
                            ->toMediaCollection($collectionName);

                        $savedImages[] = $media;
                    }
                }

                return $savedImages;
            }
            
            // Case 3 & 4: Replace all or append
            if (empty($replaceIds)) {
                $images = $images ? (is_array($images) ? $images : [$images]) : [];
                
                // Case 3: Replace all images
                if ($replaceAll == true) {
                    $model->clearMediaCollection($collectionName);
                }
                
                // Case 4: Append images (or add after replace all)
                $currentCount = $model->getMedia($collectionName)->count();
                $totalAfterInsert = $currentCount + count($images);

                if ($totalAfterInsert > $maxAllowedImages) {
                    throw new \Exception(
                        "You can only upload a maximum of {$maxAllowedImages} images. Currently stored: {$currentCount}"
                    );
                }
                
                foreach ($images as $image) {
                    if ($image instanceof UploadedFile) {
                        $media = $model->addMedia($image)
                            ->usingFileName($image->getClientOriginalName())
                            ->toMediaCollection($collectionName);

                        $savedImages[] = $media;
                    }
                }
            }

            return $savedImages;
        }
    }
    
