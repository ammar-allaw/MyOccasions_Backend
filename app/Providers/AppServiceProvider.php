<?php

namespace App\Providers;

use App\Repositories\Food\Implementation\FoodRepository;
use App\Repositories\Food\Interface\FoodRepositoryInterface;
use App\Repositories\Interaction\Implementation\InteractionRepository;
use App\Repositories\Interaction\Interface\InteractionRepositoryInterface;
use App\Repositories\LegalDocument\Implementation\LegalDocumentRepository;
use App\Repositories\LegalDocument\Interface\LegalDocumentRepositoryInterface;
use App\Repositories\PendingRegistration\Implementation\PendingRegistrationRepository;
use App\Repositories\PendingRegistration\Interface\PendingRegistrationRepositoryInterface;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Repositories\Role\Implementation\RoleRepository;
use App\Repositories\Role\Interface\RoleRepositoryInterface;
use App\Repositories\Room\Implementation\RoomRepository;
use App\Repositories\Room\Interface\RoomRepositoryInterface;
use App\Repositories\Service\Implementation\ServiceRepository;
use App\Repositories\Service\Interface\ServiceRepositoryInterface;
use App\Repositories\ServiceProvider\Implementation\ServiceProviderRepository;
use App\Repositories\ServiceProvider\Interface\ServiceProviderRepositoryInterface;
use App\Repositories\Status\Implementation\StatusRepository;
use App\Repositories\Status\Interface\StatusRepositoryInterface;
use App\Repositories\User\Implementation\UserRepository;
use App\Repositories\User\Interface\UserRepositoryInterface;
use App\Services\Food\Implementation\FoodService;
use App\Services\Food\Interface\FoodServiceInterface;
use App\Services\Interaction\Implementation\InteractionService;
use App\Services\Interaction\Interface\InteractionServiceInterface;
use App\Services\LegalDocument\Implementation\LegalDocumentService;
use App\Services\LegalDocument\Interface\LegalDocumentServiceInterface;
use App\Services\Otp\Implementation\AmanGateOtpSender;
use App\Services\Otp\Implementation\RegistrationOtpService;
use App\Services\Otp\Interface\OtpSenderInterface;
use App\Services\Otp\Interface\RegistrationOtpServiceInterface;
use App\Services\Owner\Permission\PermissionService;
use App\Services\Owner\Permission\PermissionServiceInterface;
use App\Services\Owner\Role\RoleService as OwnerRoleService;
use App\Services\Owner\Role\RoleServiceInterface as OwnerRoleServiceInterface;
use App\Services\Role\Implementation\RoleService as AppRoleService;
use App\Services\Role\Interface\RoleServiceInterface;
use App\Services\Room\Implementation\RoomService;
use App\Services\Room\Interface\RoomServiceInterface;
use App\Services\Service\Implementation\ServiceService;
use App\Services\Service\Interface\ServiceServiceInterface;
use App\Services\ServiceProvider\Implementation\ServiceProviderService;
use App\Services\ServiceProvider\Interface\ServiceProviderServiceInterface;
use App\Services\Status\Implementation\StatusService;
use App\Services\Status\Interface\StatusServiceInterface;
use App\Services\User\Implementation\UserService;
use App\Services\User\Interface\UserServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ServiceProviderRepositoryInterface::class, ServiceProviderRepository::class);
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
        $this->app->bind(RoomServiceInterface::class, RoomService::class);
        $this->app->bind(ServiceProviderServiceInterface::class, ServiceProviderService::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(ServiceServiceInterface::class, ServiceService::class);
        $this->app->bind(StatusRepositoryInterface::class, StatusRepository::class);
        $this->app->bind(StatusServiceInterface::class, StatusService::class);
        $this->app->bind(FoodRepositoryInterface::class, FoodRepository::class);
        $this->app->bind(FoodServiceInterface::class, FoodService::class);
        $this->app->bind(InteractionRepositoryInterface::class, InteractionRepository::class);
        $this->app->bind(InteractionServiceInterface::class, InteractionService::class);
        $this->app->bind(LegalDocumentRepositoryInterface::class, LegalDocumentRepository::class);
        $this->app->bind(LegalDocumentServiceInterface::class, LegalDocumentService::class);
        $this->app->bind(PendingRegistrationRepositoryInterface::class, PendingRegistrationRepository::class);
        $this->app->bind(OtpSenderInterface::class, AmanGateOtpSender::class);
        $this->app->bind(RegistrationOtpServiceInterface::class, RegistrationOtpService::class);

        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(OwnerRoleServiceInterface::class, OwnerRoleService::class);
        $this->app->bind(RoleServiceInterface::class, AppRoleService::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(PermissionServiceInterface::class, PermissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
