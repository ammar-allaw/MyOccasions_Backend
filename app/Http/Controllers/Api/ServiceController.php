<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Services\ServicesResource;
use App\Services\Auth\AuthService;
use App\Http\Resources\Hall\ServiceResource;
use App\Services\Service\ServiceServiceInterface;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\User\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    private $userService;
    private $authService;
    private $serviceService;
    private $serviceProviderService;
    public function __construct(UserServiceInterface $userService,
    ServiceProviderServiceInterface $serviceProviderService,
    AuthService $authService, ServiceServiceInterface $serviceService)
    {
        $this->userService = $userService;
        $this->authService=$authService;
        $this->serviceService=$serviceService;
        $this->serviceProviderService=$serviceProviderService;
    }
    public function getServiceForServiceProvider($serviceProviderId=null)
    {
        try {
            $user = $this->authService->authUser();
            
            $canViewAll = false;
            $serviceProvider = null;

            // Check if user is the service provider (owner of the services)
            if($user &&$user->is_provider == true && $user->role->name_en != 'client' && $serviceProviderId == null)
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
                    })->get();
            }
            
            return ServicesResource::collection($services);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getDetailsOfService($serviceId)
    {   
        try {
            $service = $this->serviceProviderService->findService($serviceId);
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
                $checkService = $this->serviceProviderService->checkServiceable($service, $provider);
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
}
