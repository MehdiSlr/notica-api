<?php

namespace App\Http\Controllers;

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
            $apiKeys = ApiKey::query();

            $apiKeys = $this->_fetchData($request, $apiKeys);

            return Resource::collection($apiKeys);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($apiKey)
    {
        try {
            $apiKey = ApiKey::withTrashed()
            ->where('id', $apiKey)
            ->first();

            return new Resource($apiKey);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'show', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'company_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $key = bin2hex(random_bytes(16));
            
            $apiKey = ApiKey::create([
                'title' => $request->input('title'),
                'key' => $key,
                'company_id' => $request->input('company_id'),
            ]);

            return response()->json([
                'type' => 'success',
                'message' => 'api key created successfully.',
                'data' => $apiKey
            ], ResponseCode::HTTP_CREATED);

        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($apiKey)
    {
        try {
            $apiKey->delete();
            _logger('success', 'ApiKey', 'delete', $apiKey);

            return response()->json([
                'type' => 'success',
                'message' => 'api key deleted successfully.',
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'delete', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($apiKey)
    {
        try {
            $apiKey->restore();
            _logger('success', 'ApiKey', 'restore', $apiKey);

            return response()->json([
                'type' => 'success',
                'message' => 'api key restored successfully.',
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'restore', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($apiKey)
    {
        try {
            $apiKey->forceDelete();
            _logger('success', 'ApiKey', 'destroy', $apiKey);

            return response()->json([
                'type' => 'success',
                'message' => 'api key destroyed successfully.',
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'ApiKey', 'destroy', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
