<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Resource;
use App\Models\MessageType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class MessageTypeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $messageTypes = MessageType::query();

            $messageTypes = $this->_fetchData($request, $messageTypes);

            return Resource::collection($messageTypes);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($messageType)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }
            $messageType = ($requestedUser->role == 'admin') ?
                MessageType::withTrashed()
                    ->where('id', $messageType)
                    ->first() :
                MessageType::where('id', $messageType)
                    ->first();

            if (!$messageType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'message type not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($messageType);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'show', $e->getMessage());

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

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            MessageType::create($request->all());
            _logger('success', 'MessageType', 'store', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'message type created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $messageType)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'string',
                'description' => 'string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $messageType = MessageType::withTrashed()
            ->where('id', $messageType)
            ->first();

            $messageType->update($request->all());
            _logger('success', 'MessageType', 'update', $messageType);

            return response()->json([
                'status' => 'success',
                'message' => "message type ($messageType->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'update', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(MessageType $messageType)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $messageType->delete();
            _logger('success', 'MessageType', 'delete', $messageType);

            return response()->json([
                'status' => 'success',
                'message' => "message type ($messageType->title) and ($messageType->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'delete', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(MessageType $messageType)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $messageType->forceDelete();
            _logger('success', 'MessageType', 'destroy', $messageType);

            return response()->json([
                'status' => 'success',
                'message' => "message type ($messageType->id) destroyed successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'destroy', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(MessageType $messageType)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unautorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($messageType->deleted_at == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'message type not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            $messageType->restore();
            _logger('success', 'MessageType', 'restore', $messageType);

            return response()->json([
                'status' => 'success',
                'message' => "message type ($messageType->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'MessageType', 'restore', $e->getMessage());

            return response()->json([
                'status' => 'error',
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
