<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Resources\Resource;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;
use App\Http\Controllers\Controller;
use App\Traits\UploaderFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Module\Ticket\app\Http\Resources\TicketResource;

class TicketController extends Controller
{
    use UploaderFile;
    
    public function index(Request $request)
    {

        try {
            $tickets = Ticket::query();

            $tickets = $this->_fetchData($request, $tickets);

            return Resource::collection($tickets);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Ticket $ticket)
    {
        try {
            if (!$ticket) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'ticket not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            return new Resource($ticket);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'show', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,zip,doc,docx,xls,xlsx,csv,txt|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_BAD_REQUEST);
            }

            $fileName = $this->uploadFile($request->file('file'));
            _logger('success', 'Ticket', 'upload', $request->all());

            return response()->json([
                'type' => 'success',
                'message' => 'file uploaded successfully.',
                'data' => [
                    'file_name' => $fileName
                ]
            ]);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'upload', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'user_id' => 'required|exists:users,id',
                'subject' => 'required|string',
                'body' => 'required|string|max:1000|min:10',
                'reply_id' => 'exists:tickets,id',
                'file' => 'string',
                // 'status' => 'in:pending,checked,closed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
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
                $isMoved = Storage::move("tmp/$fileName", "files/tickets/ticket_$fileName");

                if (!$isMoved) {
                    return response()->json([
                        'type' => 'error',
                        'message' => "move process failed. file $fileName not found.",
                    ], ResponseCode::HTTP_NOT_FOUND);
                }
            }

            if ($request->reply_id) {
                $ticket = Ticket::find($request->reply_id);
                if (!$ticket) {
                    return response()->json([
                        'type' => 'error',
                        'message' => 'ticket not found.',
                    ], ResponseCode::HTTP_NOT_FOUND);
                }

                $ticket->update([
                    'status' => 'checked'
                ]);
            }

            $fileName = isset($fileName) ? "ticket_$fileName" : null;
            Ticket::create([
                'company_id' => $request->company_id,
                'user_id' => $request->user_id,
                'subject' => $request->subject,
                'body' => $request->body,
                'reply_id' => $request->reply_id,
                'file' => $fileName,
                'status' => 'pending'
            ]);

            _logger('success', 'Ticket', 'store', $request->all());

            return response()->json([
                'type' => 'success',
                'message' => 'ticket ' . Ticket::latest()->first()->id . ' created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Ticket $ticket)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,checked,closed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_BAD_REQUEST);
            }

            $oldData = $ticket->toArray();
            $ticket->update($request->all());

            _logger('success', 'Ticket', 'update', $request->all(), $oldData);

            return response()->json([
                'type' => 'success',
                'message' => "ticket ($ticket->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Ticket', 'update', $e->getMessage());

            return response()->json([
                'type' => 'error',
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
