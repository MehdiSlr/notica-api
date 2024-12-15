<?php

namespace App\Http\Controllers;

use App\Http\Resources\Resource;
use App\Models\ApiKey;
use App\Models\Message;
use App\Models\Template;
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
            ->where('id', $message)
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
                'message_text' => 'required|exists:templates,id',
                'variables' => 'array',
                'from' => 'exists:companies,id',
                'to' => 'required|exists:users,id',
                'status' => 'in:sent,received,failed',
                'platform' => 'required|in:app,telegram',
                'is_read' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $apiKey = ApiKey::where('key', $request->header('x-api-key'))->first();

            if (!$apiKey) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'invalid api key.'
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $from = $apiKey->company_id;

            if (!$from) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'invalid api key.'
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $template = Template::find($request->message_text);

            if (!$template) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'message template not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            $messageText = $template->text; 

            if (strpos($messageText, '{{') !== false) {
                if (!$request->variables) {
                    return response()->json([
                        'type' => 'error',
                        'message' => 'variables not found.',
                    ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
                }
    
                foreach ($request->variables as $key => $value) {
                    if (strpos($messageText, "{{$key}}") === false) {
                        return response()->json([
                            'type' => 'error',
                            'message' => 'invalid variables.',
                        ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $messageText = str_replace("{{$key}}", $value, $messageText);
                }
            }

            $message = Message::create([
                'subject' => $request->subject,
                'message_text' => $messageText,
                'from' => $from,
                'to' => $request->to,
                'status' => $request->status ?? 'sent',
                'platform' => $request->platform,
                'is_read' => $request->is_read ?? 0
            ]);

            _logger('success', 'Message','store', $request->all());

            return response()->json([
                'type' =>'success',
                'message' =>'message created successfully.'
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' =>'server error 500.' . $e->getMessage()
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
                'variables' => 'json',
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
