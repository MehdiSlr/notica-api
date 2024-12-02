<?php

namespace App\Http\Controllers;

use App\Http\Resources\Resource;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        try {
            $messages = Message::query();

            $messages = $this->_fetchData($request, $messages);

            return Resource::collection($messages);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($message)
    {
        try {
            $message = Message::withTrashed()
            ->wheres('id', $message)
            // ->with()
            ->first();

            if (!$message) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'message not found.'
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($message);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'show', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),
            [
                'subject' => 'required|string',
                'message_text' => 'required|string',
                'from' => 'required|exist:companies,id',
                'to' => 'required|exist:users,id',
                'status' => 'in:sent,received,failed',
                'type' => 'required|in:auth,notification,advertise',
                'platform' => 'required|in:app,telegram',
                'is_read' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            Message::create($request->all());
            _logger('success', 'Message','store', $request->all());

            return response()->json([
                'type' =>'success',
                'message' =>'message created successfully.'
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' =>'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request,Message $message)
    {
        try {
            $validator = Validator::make($request->all(),
            [
                'subject' =>'string',
                'message_text' =>'string',
                'from' => 'exist:companies,id',
                'to' => 'exist:users,id',
                'status' => 'in:sent,received,failed',
                'type' => 'in:auth,notification,advertise',
                'platform' => 'in:app,telegram',
                'is_read' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldData = $message->toArray();
            $message->update($request->all());
            
            _logger('success', 'Message', 'update', $request->all(), $oldData);

            return response()->json([
                'type' =>'success',
                'message' => "message ($message->id) updated successfully."
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'update', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' =>'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Message $message)
    {
        try {
            $message->delete();
            _logger('success', 'Message', 'delete', $message);

            return response()->json([
                'type' => 'success',
                'message' => "message ($message->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'delete', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function onlyTrash(Request $request)
    {
        try {
            $messages = Message::onlyTrashed();

            $messages = $this->_fetchData($request, $messages);

            return Resource::collection($messages);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' =>'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function withTrash(Request $request)
    {
        try {
            $messages = Message::withTrashed();

            $messages = $this->_fetchData($request, $messages);

            return Resource::collection($messages);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'withTrash', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' =>'server error 500.',
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
