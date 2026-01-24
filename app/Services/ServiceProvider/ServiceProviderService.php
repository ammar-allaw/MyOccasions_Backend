<?php

namespace App\Services\ServiceProvider;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Service;
use App\Models\Status;
use App\Repositories\ServiceProvider\ServiceProviderRepositoryInterface;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use Exception;

class ServiceProviderService implements ServiceProviderServiceInterface
{
    protected $serviceProviderRepo;

    public function __construct(ServiceProviderRepositoryInterface $serviceProviderRepo)
    {
        $this->serviceProviderRepo = $serviceProviderRepo;
    }
    //for hall (الصالات)
    public function addRoom($data,$hall)
    {
        $room=$this->serviceProviderRepo->addRoom($data);
        $room->service_provider_id=$hall->id;
        $room->save();
        
        // إنشاء OrderStatus بحالة pending (under_review)
        $pendingStatus = Status::where('name_en', 'under_review')->first();
        if ($pendingStatus) {
            $room->orderStatusAble()->create([
                'status_id' => $pendingStatus->id
            ]);
        }
        
        return $room;
    }

    public function updateRoom($data,$room)
    {
        $room=$this->serviceProviderRepo->updateRoom($data,$room);
        return $room;
    }
     public function getRoom($hall)
    {
        $room=$this->serviceProviderRepo->getRoom($hall);
        return $room;
        
    }
    public function createServiceProvider($data)
    {
        $serviceProvider=$this->serviceProviderRepo->createServiceProvider($data);
        return $serviceProvider;
    }

 

    public function findRoom($roomId)
    {
        $room=$this->serviceProviderRepo->findRoom($roomId);
        if(!$room)
        {
            throw new Exception('the room not found',404);
        }
        else{
            return $room;
        }
    }


    public function getRoomsByHallId($hall)
    {
        $rooms=$hall->rooms;
        return $rooms;
    }

    public function findRoomForHall($hall,$roomId)
    {
        $room=$this->findRoom($roomId);
        $roomForHall=$hall->rooms()->where('id',$room->id)->first();
        if($roomForHall)
        {
            return $roomForHall;
        }else{
            throw new Exception('This room does not belong to your hall');
        }
    }
    public function deleteService($service)
    {
        $this->serviceProviderRepo->deleteService($service);
    }

    public function deleteRoom($room)
    {
        $this->serviceProviderRepo->deleteRoom($room);

    }


    public function addService($data,$hall)
    {
        $services = [];

        if (!empty($data['room_id'])) {
            foreach ($data['room_id'] as $roomId) {
                $room = $this->findRoomForHall($hall,$roomId);

                $service = $this->serviceProviderRepo->addService($data);
                $service->serviceable_id = $room->id;
                $service->serviceable_type = get_class($room);
                $service->save();
                $pendingStatus = Status::where('name_en', 'under_review')->first();
                if ($pendingStatus) {
                            $service->orderStatusAble()->create([
                                'status_id' => $pendingStatus->id
                            ]);
                }
                $services[] = $service;
            }

            return $services; // return array of services
        }
        
        // service belongs to hall
        $service = $this->serviceProviderRepo->addService($data);
        $service->serviceable_id = $hall->id;
        $service->serviceable_type = get_class($hall);
        $service->save();

        $pendingStatus = Status::where('name_en', 'under_review')->first();
                if ($pendingStatus) {
                            $service->orderStatusAble()->create([
                                'status_id' => $pendingStatus->id
                    ]);
        }

        return $service;
    }

    public function findService($serviceId)
    {
        $service=$this->serviceProviderRepo->findService($serviceId);
        if(!$service)
        {
            throw new Exception('the service not found'); 
        }
        return $service;
    }

    public function checkServiceable($service,$hall)
    {
        $type = $service->serviceable_type;
        
        if ($type == 'App\Models\ServiceProvider') {
            // الخدمة تابعة للـ hall مباشرة
            $serviceHall = $hall->services()->where('id', $service->id)->first();
        } else {
            // الخدمة تابعة لـ room معين في الـ hall
            $serviceHall = \App\Models\Service::where('id', $service->id)
                ->whereHasMorph('serviceable', [\App\Models\Room::class], function ($query) use ($hall) {
                    $query->where('service_provider_id', $hall->id);
                })
                ->first();
        }
        
        if (!$serviceHall) {
            throw new Exception('This service does not belong to your hall');
        }
        
        return $serviceHall;
    }

    public function updateService($data,$service)
    {
        $service=$this->serviceProviderRepo->updateService($data,$service);
        return $service;
    }

    public function getAllRoomsWithStatus()
    {
        // استخدام Eloquent للحصول على جميع الـ rooms مع العلاقات
        $rooms = \App\Models\Room::with([
            'orderStatusAble.status',
            'serviceProvider.user',
            'media'
        ])->get();
        
        return $rooms;
    }









}