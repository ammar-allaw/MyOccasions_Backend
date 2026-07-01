<?php

namespace App\Services\Room\Implementation;

use App\Exceptions\ApiResponseException;
use App\Exceptions\Handler;
use App\Models\Room;
use App\Models\ServiceProvider;
use App\Models\Status;
use App\Repositories\Room\Interface\RoomRepositoryInterface;
use App\Repositories\User\Interface\UserRepositoryInterface;
use App\Services\Auth\AuthService;
use App\Services\Room\Interface\RoomServiceInterface;
use App\Traits\TracksChanges;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomService implements RoomServiceInterface
{
    use TracksChanges;

    public function __construct(
        private RoomRepositoryInterface $roomRepo,
        private Handler $handler,
        private AuthService $authService,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function addRoom(array $data, Request $request, $serviceProviderId = null): Room
    {
        DB::beginTransaction();
        try {
            $isOwner = Auth::guard('owner')->check();
            if ($isOwner && $serviceProviderId) {
                $user = $this->userRepository->findUserById($serviceProviderId);
                $hall = $user->userable;
            } else {
                $authUser = $this->authService->authUser();
                $hall = $this->authService->userable($authUser);
            }

            $room = $this->roomRepo->addRoom($data);
            $room->service_provider_id = $hall->id;
            $room->save();

            $pendingStatus = Status::where('name_en', 'under_review')->first();
            if ($pendingStatus) {
                $room->orderStatusAble()->create([
                    'status_id' => $pendingStatus->id,
                ]);
            }

            $room->load('orderStatusAble');

            $currentImageCount = $room->getMedia('room_image')->count();
            $maxAllowedImages = 5;

            $this->handler->attachImagesToModel(
                $room,
                $request->file('image'),
                'room_image',
                $currentImageCount,
                $maxAllowedImages
            );

            $room->refresh();

            DB::commit();

            return $room;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getRooms()
    {
        $user = $this->authService->authUser();
        $hall = $this->authService->userable($user);

        return $this->roomRepo->getRoomsByHall($hall);
    }

    public function updateRoom(array $data, Request $request, $roomId): Room
    {
        $isOwner = Auth::guard('owner')->check();

        if (isset($data['status_id'])) {
            if (! $isOwner) {
                throw new ApiResponseException(
                    'Only owner can update room status',
                    403,
                    null
                );
            }
            $room = $this->findRoom($roomId);
            $room->load('orderStatusAble');
            if ($room && $room->orderStatusAble) {
                $room->orderStatusAble->status_id = $data['status_id'];

                if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
                    $room->orderStatusAble->rejection_reason = $data['rejection_reason'];
                } else {
                    $room->orderStatusAble->rejection_reason = null;
                }

                $room->orderStatusAble->save();
            }
        }

        if ($isOwner) {
            $room = $this->findRoom($roomId);
        } else {
            $authUser = $this->authService->authUser();
            $hall = $this->authService->userable($authUser);
            $room = $this->findRoomForHall($hall, $roomId);

            $originalRoom = $this->roomRepo->findRoomModelById((int) $roomId);
            $this->trackChangesAndUpdateStatus($originalRoom, $data, $request, [
                'name' => 'الاسم',
                'name_en' => 'الاسم الإنجليزي',
                'description' => 'الوصف',
                'description_en' => 'الوصف الإنجليزي',
                'capacity' => 'السعة',
            ]);
        }

        DB::beginTransaction();
        try {
            $this->roomRepo->updateRoom($data, $room);
            $room->refresh();

            $this->handler->manageImagesOnModel(
                $room,
                'room_image',
                $request->file('image'),
                5,
                $data['replace_all'] ?? false,
                $data['image_id'] ?? null
            );
            $room->refresh();
            $room->load('orderStatusAble.status', 'media');

            DB::commit();

            return $room;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getRoomsByHallId($hallId)
    {
        $user = $this->userRepository->findUserById($hallId);
        $user->load('userable');
        $hall = $user->userable;

        return $hall->rooms;
    }

    public function getHallDetails($userId = null): ServiceProvider
    {
        $authUser = $this->authService->authUser();
        if (! $authUser) {
            if (Auth::guard('owner')->check()) {
                $authUser = Auth::guard('owner')->user();
            }
        }

        if (! $authUser) {
            throw new ApiResponseException('Unauthorized', 401, null);
        }

        $authUser->load('role');
        $isClient = $authUser->role->name_en === 'client';

        if ($authUser->role->name_en === 'halls') {
            $user = $authUser;
        } else {
            if (! $userId) {
                throw new ApiResponseException('Hall ID is required', 400, null);
            }
            $user = $this->userRepository->findUserById($userId);
            if ($user->role->name_en != 'halls') {
                throw new ApiResponseException('The specified user is not a hall', 400, null);
            }
        }

        $user->load('userable');

        $hall = $user->userable;

        if ($isClient) {
            $acceptedStatus = Status::where('name_en', 'accepted')->first();

            if ($acceptedStatus) {
                $hall->load([
                    'orderStatusAble.status',
                    'rooms' => function ($query) use ($acceptedStatus) {
                        $query->whereHas('orderStatusAble', function ($q) use ($acceptedStatus) {
                            $q->where('status_id', $acceptedStatus->id);
                        });
                    },
                    'rooms.orderStatusAble.status',
                    'rooms.services' => function ($query) use ($acceptedStatus) {
                        $query->whereHas('orderStatusAble', function ($q) use ($acceptedStatus) {
                            $q->where('status_id', $acceptedStatus->id);
                        });
                    },
                    'rooms.services.orderStatusAble.status',
                    'rooms.services.media',
                    'rooms.media',
                    'services' => function ($query) use ($acceptedStatus) {
                        $query->whereHas('orderStatusAble', function ($q) use ($acceptedStatus) {
                            $q->where('status_id', $acceptedStatus->id);
                        });
                    },
                    'services.orderStatusAble.status',
                    'services.media',
                    'media',
                    'user',
                    'types',
                    'government',
                    'region',
                ]);

                if (! $hall->orderStatusAble || $hall->orderStatusAble->status_id != $acceptedStatus->id) {
                    throw new ApiResponseException('This hall is not accepted yet', 403, null);
                }
            }
        } else {
            $hall->load([
                'orderStatusAble.status',
                'rooms.orderStatusAble.status',
                'rooms.services.orderStatusAble.status',
                'rooms.services.media',
                'rooms.media',
                'services.orderStatusAble.status',
                'services.media',
                'media',
                'user',
                'types',
                'government',
                'region',
            ]);
        }

        return $hall;
    }

    public function findRoom($roomId)
    {
        $room = $this->roomRepo->findRoom($roomId);
        if (! $room) {
            throw new Exception('the room not found', 404);
        }

        return $room;
    }

    public function findRoomForHall($hall, $roomId)
    {
        $room = $this->findRoom($roomId);
        $roomForHall = $hall->rooms()->where('id', $room->id)->first();
        if ($roomForHall) {
            return $roomForHall;
        }

        throw new Exception('This room does not belong to your hall');
    }

    public function deleteRoomById($roomId): void
    {
        DB::beginTransaction();
        try {
            if (Auth::guard('owner')->check()) {
                $room = $this->findRoom($roomId);
            } else {
                $authUser = $this->authService->authUser();
                $hall = $this->authService->userable($authUser);
                $room = $this->findRoomForHall($hall, $roomId);
            }
            $room->load(['media']);

            $this->roomRepo->deleteRoom($room);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function getAllRoomsWithStatus()
    {
        return $this->roomRepo->getAllWithStatus();
    }
}
