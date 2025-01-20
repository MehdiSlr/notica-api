<?php

namespace App\Http\Controllers;

use App\Http\Resources\Resource;
use App\Models\Plan;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        try {
            $plans = Plan::query();

            $plans = $this->_fetchData($request, $plans);

            return Resource::collection($plans);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($plan)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $plan = ($requestedUser->role == 'admin') ?
                Plan::withTrashed()
                    ->where('id', $plan)
                    ->first() :
                Plan::where('id', $plan)
                    ->first();

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'plan not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($plan);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'show', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'string',
                'price' => 'required|numeric',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            Plan::create($request->all());
            _logger('success', 'Plan', 'store', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'plan created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Plan $plan)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'string',
                'description' => 'string',
                'price' => 'numeric',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldData = $plan->toArray();
            $plan->update($request->all());

            _logger('success', 'Plan', 'update', $request->all(), $oldData);

            return response()->json([
                'status' => 'success',
                'message' => "plan ($request->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'update', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Plan $plan)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $plan->delete();
            _logger('success', 'Plan', 'delete', $plan);

            return response()->json([
                'status' => 'success',
                'message' => "plan ($plan->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'delete', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($plan)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $plan = Plan::withTrashed()->where('id', $plan)->first();

            if (!$plan || $plan->deleted_at == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'plan not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            $plan->restore();
            _logger('success', 'Plan', 'restore', $plan);

            return response()->json([
                'status' => 'success',
                'message' => "plan ($plan->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'restore', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($plan)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $plan = Plan::withTrashed()->where('id', $plan)->first();
            $plan->forceDelete();
            _logger('success', 'Plan', 'destroy', $plan);

            return response()->json([
                'status' => 'success',
                'message' => "plan ($plan->id) destroyed successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'destroy', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function onlyTrash(Request $request)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $plans = Plan::onlyTrashed();

            $plans = $this->_fetchData($request, $plans);

            return Resource::collection($plans);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function withTrash(Request $request)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }
            $plans = Plan::withTrashed();

            $plans = $this->_fetchData($request, $plans);

            return Resource::collection($plans);
        } catch (\Exception $e) {
            _logger('error', 'Plan', 'withTrashed', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function _fetchData(Request $request, $query)
    {
        if (!$request->has('per') || $request->input('per') === null) {
            $request->merge(['per' => 25]);
        }

        if ($request->has('search') && !empty($request->input('search'))) {
            $query->where('title', 'LIKE', "%{$request->input('search')}%");
        }

        if ($request->has('sort_by') && $request->has('sort_direction') && !empty($request->input('sort_by')) && !empty($request->input('sort_direction'))) {
            $query->orderBy($request->input('sort_by'), $request->input('sort_direction'));
        }

        return $query->paginate($request->input('per'));
    }
}
