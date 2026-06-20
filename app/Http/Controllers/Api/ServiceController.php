<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiResponseException;
use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hall\AddServiceRequest;
use App\Http\Requests\Hall\UpdateServiceRequest;
use App\Http\Requests\Service\AddMainKeyRequest;
use App\Http\Requests\Service\UpdateMainKeyRequest;
use App\Http\Resources\App\MainKeyResource;
use App\Http\Resources\Hall\ServiceResource;
use App\Services\Auth\AuthService;
use App\Services\Service\Interface\ServiceServiceInterface;
use App\Services\User\Interface\UserServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function __construct(
        private Handler $handler,
        private UserServiceInterface $userService,
        private AuthService $authService,
        private ServiceServiceInterface $serviceService,
    ) {}
    public function getServiceForServiceProvider($serviceProviderId=null)
    {
        try {
            $user = $this->authService->authUser();
            
            $canViewAll = false;
            $serviceProvider = null;

            // Check if user is the service provider (owner of the services)
            if($user && $user->is_provider == true && $user->role->name_en != 'client')
            {
                $serviceProvider = $user->userable;
                $canViewAll = true;
            } 
            // Check if Authorised Owner (Admin) or explicit query
            else {
                if($serviceProviderId == null) {
                    return response()->json(['message' => 'Service provider ID is required'], 400);
                }
                $serviceProvider = $this->userService->getServiceProviderById($serviceProviderId);
                
                // Check if Requesting User is Owner (Admin)
                if (Auth::guard('owner')->check() || ($user instanceof \App\Models\Owner)) {
                    $canViewAll = true;
                }
                // Check if Requesting User is the Provider itself (by ID)
                elseif ($user->is_provider && $user->userable && $user->userable->id == $serviceProvider->id) {
                    $canViewAll = true;
                }
            }

            if (!$serviceProvider) {
                return response()->json(['message' => 'Service Provider not found'], 404);
            }

            if ($canViewAll) {
                $services = $this->serviceService->getServicesForServiceProvider($serviceProvider);
            } else {
                // Client Logic: 
                // 1. Service Provider Must be Accepted
                $spStatus = $serviceProvider->orderStatusAble->status->name_en ?? null;
                if ($spStatus !== 'accepted') {
                    // Hide completely if SP is not accepted
                    return response()->json(['message' => 'Service provider not found'], 404);
                }

                // 2. Services Must be Accepted
                $services = $serviceProvider->services()
                    ->whereHas('orderStatusAble.status', function($query) {
                        $query->where('name_en', 'accepted');
                    })->with(['media', 'mainKeys', 'orderStatusAble.status'])->get();
            }
            
            return ServiceResource::collection($services);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getDetailsOfService($serviceId)
    {   
        try {
            $service = $this->serviceService->findService($serviceId);
            $service->load(['orderStatusAble.status', 'serviceable','media']);

            $canViewAll = false;
            $user = $this->authService->authUser();

            // Check if Owner (Admin)
            if (Auth::guard('owner')->check()) {
                $canViewAll = true;
            } 
            // Check if Provider (Owner of the service)
            elseif ($user && $user->is_provider && $user->userable) {
                $provider = $user->userable;
                $checkService = $this->serviceService->checkServiceable($service, $provider);
                if ($checkService) {
                    $canViewAll = true;
                }
            }

            // If not admin or owner of service, only show accepted services
            if (!$canViewAll) {
                $status = $service->orderStatusAble->status->name_en ?? null;
                if ($status !== 'accepted') {
                    return response()->json(['message' => 'Service not found'], 404);
                }
            }
            return new ServiceResource($service);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addService(AddServiceRequest $request, $serviceProviderId = null)
    {
        try {
            $services = $this->serviceService->addService(
                $request->validated(),
                $request,
                $serviceProviderId
            );

            return $this->handler->successResponse(
                ['services' => new ServiceResource($services)],
                true,
                'success add service',
                201
            );
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function updateService(UpdateServiceRequest $request, $serviceId)
    {
        try {
            $service = $this->serviceService->updateServiceFromRequest(
                $request->validated(),
                $request,
                $serviceId
            );

            return $this->handler->successResponse(
                ['service' => new ServiceResource($service)],
                true,
                'success update service',
                201
            );
        } catch (ApiResponseException $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), $e->data, $e->statusCode);
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function deleteService($serviceId)
    {
        try {
            $this->serviceService->deleteServiceById($serviceId);

            return $this->handler->successResponse(
                null,
                true,
                'success delete service',
                200
            );
        } catch (Exception $e) {
            return $this->handler->errorResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function addMainKey(AddMainKeyRequest $request)
    {
        try
        {
            $data=$request->validated();
            $mainKey=$this->serviceService->addMainKey($data);
            return $this->handler->successResponse($mainKey, true, 'Main Key added successfully');
        }catch(\Exception $e){
            $status = $e->getCode() ?: 400;
            return $this->handler->errorResponse(false, $e->getMessage(), null, $status);
        }
    }

    public function getMainKeys(Request $request)
    {
        try
        {
            $lang=$request->header('Accept-Language', 'ar');
            $data=$request->all();
            $data['lang']=$lang;
            $mainKeys=$this->serviceService->getMainKeys($data);
            return $this->handler->successResponse(
                MainKeyResource::collection($mainKeys),
                true,
                $this->message('main_keys_retrieved')
            );
        }catch(\Exception $e){
            // $status = $e->getCode() ?: 400;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } else {
                $status = $e->getCode() ?: 400;
            }
            return $this->handler->errorResponse(false, $this->messageFromException($e), null, $status);
        }
    }

    public function updateMainKey(UpdateMainKeyRequest $request, $id)
    {
        try
        {
            $data=$request->validated();
            $mainKey=$this->serviceService->findMainKeyById($id);
            $updateMainKey=$this->serviceService->updateMainKey($data,$mainKey);
            return $this->handler->successResponse($updateMainKey, true, 'Main Key updated successfully');
        }catch(\Exception $e){
            $status = $e->getCode() ?: 400;
            return $this->handler->errorResponse(false, $e->getMessage(), null, $status);
        }
    }

    public function deleteMainKey($id)
    {
        try
        {
            $mainKey=$this->serviceService->findMainKeyById($id);
            $this->serviceService->deleteMainKey($mainKey);
            return $this->handler->successResponse(null, true, 'Main Key deleted successfully');
        }catch(\Exception $e){
            $status = $e->getCode() ?: 400;
            return $this->handler->errorResponse(false, $e->getMessage(),null, $status);
        }
    }

    private function message(string $key): string
    {
        $locale = request()->header('Accept-Language', 'ar');

        $messages = [
            'ar' => [
                'main_keys_retrieved' => 'تم جلب المفاتيح الرئيسية بنجاح',
                'role_id_required' => 'حقل الدور مطلوب',
            ],
            'en' => [
                'main_keys_retrieved' => 'Main Keys retrieved successfully',
                'role_id_required' => 'role_id is required',
            ],
        ];

        return $messages[$locale === 'en' ? 'en' : 'ar'][$key] ?? $key;
    }

    private function messageFromException(\Exception $e): string
    {
        return match ($e->getMessage()) {
            'role_id is required' => $this->message('role_id_required'),
            default => $e->getMessage(),
        };
    }
}
