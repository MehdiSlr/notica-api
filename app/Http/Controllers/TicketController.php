<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Company;
use App\Traits\FileManager;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Resource;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    use FileManager;

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

            if ($requestedUser->role == 'admin') {
                $tickets = Ticket::query();
            } else if ($requestedUser->role == 'user') {
                $tickets = Ticket::query()->where('user_id', $requestedUser->id);
            } else if ($requestedUser->role == 'owner') {
                $tickets = Ticket::query()->where('company_id', Company::where('owner', $requestedUser->id)->first()->id);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $tickets = $this->_fetchData($request, $tickets);

            return Resource::collection($tickets);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Ticket $ticket)
    {
        try {
            if (!$ticket) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ticket not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->role == 'admin') {
                return new Resource($ticket);
            } else if ($requestedUser->role == 'user') {
                if ($ticket->user_id != $requestedUser->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'unauthorized.',
                    ], ResponseCode::HTTP_UNAUTHORIZED);
                }
            } else if ($requestedUser->role == 'owner') {
                if ($ticket->company_id != Company::where('owner', $requestedUser->id)->first()->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'unauthorized.',
                    ], ResponseCode::HTTP_UNAUTHORIZED);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }
            return new Resource($ticket);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'show', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload(Request $request)
    {
        $requestedUser = auth('api')->user();

        if ($requestedUser == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'unauthorized.',
            ], ResponseCode::HTTP_UNAUTHORIZED);
        }

        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf,zip,doc,docx,xls,xlsx,csv,txt|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $fileName = $this->_uploadFile($request->file('file'));
            _logger('success', 'Ticket', 'upload', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'file uploaded successfully.',
                'data' => [
                    'file_name' => $fileName
                ]
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'upload', $e->getMessage());

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

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['company_id', 'user_id', 'subject', 'body', 'reply_id', 'file'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'company_id' => 'required|exists:companies,id',
                'user_id' => 'required|exists:users,id',
                'subject' => 'required|string',
                'body' => 'required|string|max:1000|min:10',
                'reply_id' => 'exists:tickets,id',
                'file' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->has('file')) {
                if (strpos($request->file, '/')) {
                    $fileParts = explode('/', $request->file);
                    $fileName = end($fileParts);
                } else {
                    $fileName = $request->file;
                }
                $fileRealName = "ticket_{$requestedUser->id}_$fileName";
                $isMoved = Storage::move("tmp/$fileName", "files/tickets/$fileRealName");

                if (!$isMoved) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "move process failed. file $fileName not found.",
                    ], ResponseCode::HTTP_NOT_FOUND);
                }

                $request->file = $fileRealName;
            }

            if ($request->reply_id) {
                $ticket = Ticket::find($request->reply_id);
                if (!$ticket) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'reply ticket not found.',
                    ], ResponseCode::HTTP_NOT_FOUND);
                }

                $ticket->update([
                    'status' => 'checked'
                ]);
            }

            $request->merge(['status' => 'pending']);
            Ticket::create([$request->all()]);

            _logger('success', 'Ticket', 'store', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'ticket ' . Ticket::latest()->first()->id . ' created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Ticket $ticket)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['status'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'status' => 'required|in:pending,checked,closed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($ticket->user_id != $requestedUser->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $oldData = $ticket->toArray();
            $ticket->update($request->all());

            _logger('success', 'Ticket', 'update', $request->all(), $oldData);

            return response()->json([
                'status' => 'success',
                'message' => "ticket ($ticket->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'update', $e->getMessage());

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
