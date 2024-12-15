<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Resource;
use App\Models\Template;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        try {
            $temlates = Template::query();

            $temlates = $this->_fetchData($request, $temlates);

            return Resource::collection($temlates);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($template)
    {
        try {
            $template = Template::query()
            ->where('id', $template)
            ->first();

            if (!$template) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'template not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($template);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'show', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),
            [
                'title' => 'required|string',
                'text' => 'required|string',
                'type' => 'required|exists:message_types,id',
                'company_id' => 'required|exists:companies,id',
                'status' => 'in:pending,accept,reject',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            Template::create([
                'title' => $request->title,
                'text' => $request->text,
                'type' => $request->type,
                'company_id' => $request->company_id,
                'status' => 'pending',
                'is_active' => $request->is_active ?? 0,
            ]);

            return response()->json([
                'type' => 'success',
                'message' => 'template created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Template $template)
    {
        try {
            $validator = Validator::make($request->all(),
            [
                'title' => 'required|string',
                'text' => 'required|string',
                'type' => 'required|exists:message_types,id',
                'company_id' => 'required|exists:companies,id',
                'status' => 'in:pending,accept,reject',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldData = $template->toArray();
            $template->update($request->all());

            _logger('success', 'Template', 'update', $request->all(), $oldData);

            return response()->json([
                'type' => 'success',
                'message' => "template ($template->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'update', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Template $template)
    {
        try {
            $template->delete();
            _logger('success', 'Template', 'delete', $template);

            return response()->json([
                'type' => 'success',
                'message' => "template ($template->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'delete', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function onlyTrash(Request $request)
    {
        try {
            $templates = Template::onlyTrashed();

            $templates = $this->_fetchData($request, $templates);

            return Resource::collection($templates);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function withTrash(Request $request)
    {
        try {
            $templates = Template::withTrashed();

            $templates = $this->_fetchData($request, $templates);

            return Resource::collection($templates);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'withTrash', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(Template $template)
    {
        try {
            $template->restore();
            _logger('success', 'Template', 'restore', $template);

            return response()->json([
                'type' => 'success',
                'message' => "template ($template->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'restore', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Template $template)
    {
        try {
            $template->forceDelete();
            _logger('success', 'Template', 'destroy', $template);

            return response()->json([
                'type' => 'success',
                'message' => "template ($template->id) destroyed successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'destroy', $e->getMessage());

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
