<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Traits\SendSMS;
use Illuminate\Routing\Controller;
use App\Http\Resources\Resource;
use App\Models\ApiKey;
use App\Models\Message;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class MessageController extends Controller
{
    use SendSMS;
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

            $messages = ($requestedUser->role == 'admin') ? Message::query() : Message::query()->where('to', $requestedUser->id);

            $messages = $this->_fetchData($request, $messages);

            return Resource::collection($messages);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($message)
    {
        try {

            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $message = ($requestedUser->role == 'admin') ? Message::where('id', $message)->first() : Message::withTrashed()
                ->where('id', $message)
                ->where('to', $requestedUser->id)
                ->first();

            if (!$message) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'message not found.'
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($message);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'show', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $apiKey = ApiKey::where('key', $request->header('x-api-key'))->first();

            if (!$apiKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['subject', 'template_id', 'variables', 'to', 'platform'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest,
            [
                'subject' => 'required|string',
                'to' => 'required|numeric',
                'template_id' => 'required|exists:templates,id',
                'variables' => 'array',
                'platform' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $from = $apiKey->company_id;

            if (!$from) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'invalid api key.'
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $template = Template::find($request->template_id);

            // check if template exists and belongs to the company
            if (!$template || $template->company_id != $from) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'message template not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            //check if template is active or accepted
            if ($template->status != true && $template->is_active != 'accept') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'message template is not active or accepted.',
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $messageText = $template->text;

            if (strpos($messageText, '{') !== false) {
                if (!$request->variables) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'variables not found.',
                    ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
                }

                foreach ($request->variables as $key => $value) {
                    if (strpos($messageText, "{{$key}}") === false) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'invalid variables.',
                        ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $messageText = str_replace("{{$key}}", $value, $messageText);
                }
            }

            $to = User::query()->where('phone', $request->to)->first();
            $comapany = Company::query()->where('id', $from)->first();

            if (!$to) {
                $invited = $this->_inviteUser($request->to, $comapany->name);

                if (!$invited) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'message not sent.',
                        'data' => [
                            'phone' => $request->to,
                            'reason' => 'the user does not have the Notica account and invite message was not sent.',
                        ]
                    ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
                }

                User::create([
                    'phone' => $request->to,
                    'uuid' => Str::orderedUuid()->toString(),
                ]);

                $to = User::query()->where('phone', $request->to)->first();
            }

            $platform = $request->platform;

            //check platform for values, allows 'app' or 'telegram' or both of them. if other retuen error
            foreach ($platform as $key => $value) {
                if (!in_array($value, ['app', 'telegram'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'invalid platform.',
                    ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            $message = Message::create([
                'subject' => $request->subject,
                'message_text' => $messageText,
                'from' => $from,
                'to' => $to->id,
                'status' => $request->status ?? 'sent',
                'platform' => $request->platform,
                'is_read' => $request->is_read ?? 0
            ]);

            _logger('success', 'Message','store', $request->all());

            return response()->json([
                'status' =>'success',
                'message' =>'message sent successfully.',
                'message id' => $message->id
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' =>'server error 500.' . $e->getMessage()
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request,Message $message)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->id != $message->to) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['status', 'is_read'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest,
            [
                'status' => 'in:sent,received,failed',
                'is_read' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldData = $message->toArray();
            $message->update($request->all());

            _logger('success', 'Message', 'update', $request->all(), $oldData);

            return response()->json([
                'status' =>'success',
                'message' => "message ($message->id) updated successfully."
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'update', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' =>'server error 500.'
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Message $message)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->id != $message->to) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $message->delete();
            _logger('success', 'Message', 'delete', $message);

            return response()->json([
                'status' => 'success',
                'message' => "message ($message->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'delete', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(Message $message)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->id != $message->to) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $message->restore();
            _logger('success', 'Message', 'restore', $message);

            return response()->json([
                'status' => 'success',
                'message' => "message ($message->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'restore', $e->getMessage());

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

            $messages = Message::onlyTrashed();

            $messages = $this->_fetchData($request, $messages);

            return Resource::collection($messages);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' =>'server error 500.',
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
            $messages = Message::withTrashed();

            $messages = $this->_fetchData($request, $messages);

            return Resource::collection($messages);
        } catch (\Exception $e) {
            _logger('error', 'Message', 'withTrash', $e->getMessage());

            return response()->json([
                'status' => 'error',
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
