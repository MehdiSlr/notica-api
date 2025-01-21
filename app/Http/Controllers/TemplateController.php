<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Routing\Controller;
use App\Http\Resources\Resource;
use App\Models\Template;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class TemplateController extends Controller
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

            if ($requestedUser->role == 'admin') {
                $temlates = Template::query();
            } else if ($requestedUser->role == 'owner') {
                $temlates = Template::query()->where('company_id', Company::where('owner', $requestedUser->id)->first()->id);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $temlates = $this->_fetchData($request, $temlates);

            return Resource::collection($temlates);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($template)
    {
        try {
            if (!is_numeric($template)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'template id must be numeric.',
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->role == 'admin') {
                $template = Template::query()
                ->where('id', $template)
                ->first();
            } else if ($requestedUser->role == 'owner') {
                $template = Template::query()
                ->where('id', $template)
                ->where('company_id', Company::where('owner', $requestedUser->id)->first()->id)
                ->first();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if (!$template) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'template not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($template);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'show', $e->getMessage());

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

            $allowed = ['title', 'text', 'type'];
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
                'title' => 'required|string',
                'text' => 'required|string',
                'type' => 'required|exists:message_types,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $company = Company::where('owner', $requestedUser->id)->first();

            if ($company == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }


            Template::create([
                'title' => $request->title,
                'text' => $request->text,
                'type' => $request->type,
                'company_id' => $company->id,
                'status' => 'pending',
                'is_active' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'template created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Template $template)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['title', 'text', 'type', 'status', 'is_active'];
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
                'title' => 'string',
                'text' => 'string',
                'type' => 'exists:message_types,id',
                'status' => 'in:pending,accept,reject',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->status && $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $oldData = $template->toArray();
            $template->update($request->all());

            _logger('success', 'Template', 'update', $request->all(), $oldData);

            return response()->json([
                'status' => 'success',
                'message' => "template ($template->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'update', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Template $template)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $template->company_id != $requestedUser->company_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $template->delete();
            _logger('success', 'Template', 'delete', $template);

            return response()->json([
                'status' => 'success',
                'message' => "template ($template->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'delete', $e->getMessage());

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

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $templates = Template::onlyTrashed();
            $company = Company::where('owner', $requestedUser->id)->first();

            if ($requestedUser->role != 'admin' || $company->id != $templates->first()->company_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            $templates = $this->_fetchData($request, $templates);

            return Resource::collection($templates);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'onlyTrashed', $e->getMessage());

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
            $templates = Template::withTrashed();

            $templates = $this->_fetchData($request, $templates);

            return Resource::collection($templates);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'withTrash', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(Template $template)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $template->company_id != $requestedUser->company_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $template->restore();
            _logger('success', 'Template', 'restore', $template);

            return response()->json([
                'status' => 'success',
                'message' => "template ($template->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'restore', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Template $template)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $template->company_id != $requestedUser->company_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $template->forceDelete();
            _logger('success', 'Template', 'destroy', $template);

            return response()->json([
                'status' => 'success',
                'message' => "template ($template->id) destroyed successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Template', 'destroy', $e->getMessage());

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
