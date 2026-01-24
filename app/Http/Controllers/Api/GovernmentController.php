<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Government\AddRegionRequest;
use App\Models\Government;
use App\Services\Government\GovernmentService;
use Illuminate\Http\Request;

class GovernmentController extends Controller
{

    public $handler;
    private $ServiceProviderService;
    private $governmentService;
    private $userService;
    public function __construct(Handler $handler, GovernmentService $governmentService)
    {
        $this->handler=$handler;
        $this->governmentService=$governmentService;
    }


    public function getGovernments()
    {
        $governments = $this->governmentService->getGovernments();
        return $this->handler->successResponse(
                    true,
                    'success get governments',
                    $governments,
                    200);
    }


    public function addRegion(AddRegionRequest $request)
    {
        $data=$request->validated();
        $government = Government::find($data['government_id']);
        if (!$government) {
            return $this->handler->errorResponse(
                false,
                'Government not found',
                null,
                404
            );
        }
        $region = $government->regions()->create([
            'name' => $data['name'],
            'name_en' => $data['name_en'],
        ]);
        return $this->handler->successResponse(
            true,
            'success add region',   
            $region,
            201
        );
    }


    public function getRegionsByGovernment($governmentId)
    {
        $government = Government::find($governmentId);
        if (!$government) {
            return $this->handler->errorResponse(
                false,
                'Government not found',
                null,
                404
            );
        }
        $regions = $government->regions;
        return $this->handler->successResponse(
            true,
            'success get regions by government',
            $regions,
            200
        );
    }
}
