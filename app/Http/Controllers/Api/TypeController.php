<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Resources\Type\TypeResource;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypeController extends Controller
{

    public $handler;

    public function __construct(
        Handler $handler,
    ) {
        $this->handler = $handler;
    }

    public function getTypes()
    {
        $filters = request()->all();
        $query = Type::query()->orderBy('name_en');

        $authUser = auth()->guard('api')->user();
        $providerTypes = collect();

        if ($authUser && $authUser->is_provider == true) {
            if ($authUser->role_id) {
                $query->where('role_id', $authUser->role_id);
            }

            if ($authUser->userable && method_exists($authUser->userable, 'types')) {
                $providerTypes = $authUser->userable->types;
            }
        } elseif (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        $types = $query->get();

        $response = [
            'types' => TypeResource::collection($types),
        ];

        if ($authUser && $authUser->is_provider == true) {
            $response['provider_types'] = TypeResource::collection($providerTypes);
        }

        return $this->handler->successResponse(
            $response,
            true,
            'success get types',
            200
        );
    }

    public function storeType(Request $request)
    {
        if (!Auth::guard('owner')->check()) {
            return $this->handler->errorResponse(false, 'Unauthorized. Owner access only.', null, 401);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:types,name,except,id',
            'name_en' => 'required|string|max:255|unique:types,name_en,except,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $type = Type::create($data);

        return $this->handler->successResponse(
            ['type' => new TypeResource($type)],
            true,
            'success create type',
            201
        );
    }

    public function updateType(Request $request, Type $type)
    {
        if (!Auth::guard('owner')->check()) {
            return $this->handler->errorResponse(false, 'Unauthorized. Owner access only.', null, 401);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255|unique:types,name,except,id',
            'name_en' => 'nullable|string|max:255|unique:types,name_en,except,id',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $type->update(array_filter($data, fn($value) => $value !== null));

        return $this->handler->successResponse(
            ['type' => new TypeResource($type)],
            true,
            'success update type',
            200
        );
    }

    public function destroyType(Type $type)
    {
        if (!Auth::guard('owner')->check()) {
            return $this->handler->errorResponse(false, 'Unauthorized. Owner access only.', null, 401);
        }

        $type->delete();

        return $this->handler->successResponse(
            ['type_id' => $type->id],
            true,
            'success delete type',
            200
        );
    }
}
