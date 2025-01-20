<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Resources\Resource;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            switch ($requestedUser->role) {
                case 'admin':
                    $apiKeys = ApiKey::query();
                    break;
                case 'owner':
                    $apiKeys = ApiKey::query()->where('company_id', Company::where('owner', $requestedUser->id)->first()->id);
                    break;
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'unauthorized.',
                    ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $apiKeys = $this->_fetchData($request, $apiKeys);

            return Resource::collection($apiKeys);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($apiKey)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            switch ($requestedUser->role) {
                case 'admin':
                    $apiKey = ApiKey::withTrashed()
                        ->where('id', $apiKey)
                        ->first();
                    break;
                case 'owner':
                    $apiKey = ApiKey::query()->where('company_id', Company::where('owner', $requestedUser->id)->first()->id)
                    ->where('id', $apiKey)
                    ->first();
                    break;
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'unauthorized.',
                    ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if (!$apiKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'api key not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($apiKey);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'show', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'owner') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['title'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'title' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $key = bin2hex(random_bytes(32));

            $apiKey = ApiKey::create([
                'title' => $request->input('title'),
                'key' => $key,
                'company_id' => $requestedUser->company_id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'api key created successfully.',
                'data' => $apiKey
            ], ResponseCode::HTTP_CREATED);

        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($apiKey)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'owner' || $apiKey->company_id != Company::where('owner', $requestedUser->id)->first()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $apiKey = ApiKey::where('id', $apiKey)->first();

            if (!$apiKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'api key not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            $apiKey->delete();
            _logger('success', 'ApiKey', 'delete', $apiKey);

            return response()->json([
                'status' => 'success',
                'message' => 'api key deleted successfully.',
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'delete', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($apiKey)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $apiKey = ApiKey::withTrashed()->where('id', $apiKey)->first();

            if (!$apiKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'api key not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            $apiKey->restore();
            _logger('success', 'ApiKey', 'restore', $apiKey);

            return response()->json([
                'status' => 'success',
                'message' => 'api key restored successfully.',
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'restore', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function destroy($apiKey)
    // {
    //     try {
    //         $requestedUser = auth('api')->user();

    //         if ($requestedUser == null || $requestedUser->role != 'admin') {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'unauthorized.',
    //             ], ResponseCode::HTTP_UNAUTHORIZED);
    //         }

    //         $apiKey = ApiKey::withTrashed()->where('id', $apiKey)->first();

    //         if (!$apiKey) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'api key not found.',
    //             ], ResponseCode::HTTP_NOT_FOUND);
    //         }

    //         $apiKey->forceDelete();
    //         _logger('success', 'ApiKey', 'destroy', $apiKey);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'api key destroyed successfully.',
    //         ], ResponseCode::HTTP_OK);
    //     } catch (\Exception $e) {
    //         _logger('error', 'ApiKey', 'destroy', $e->getMessage());

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'server error 500.',
    //         ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    private function _fetchData(Request $request, $query)
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
