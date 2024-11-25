<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $comapnies = Company::query();

            $comapnies = $this->_fetchData($request, $comapnies);

            return PlanResource::collection($comapnies);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($plan)
    {
        try {
            $plan = Company::withTrashed()
                ->where('id', $plan)
                ->with('companies')
                ->first();

            if (!$plan) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'plan not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new PlanResource($plan);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'show', $e->getMessage());

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
                'name' => 'required|string',
                'logo' => 'string',
                'description' => 'string',
                'website' => 'string',
                'slogan' => 'string',
                'owner' => 'required|exists:users,id',
                'phone' => 'numeric|unique:companies,phone',
                'email' => 'email|unique:companies,email',
                'national_id' => 'numeric|unique:companies,national_id',
                'address' => 'string',
                'established_date' => 'date',
                'email_verified_at' => 'date',
                'is_active' => 'boolean',
                'plan_id' => 'exists:plans,id',
                'settings' => 'array',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            Company::create($request->all());
            // dd($request->all());
            _logger('success', 'Company', 'store', $request->all());

            return response()->json([
                'type' => 'success',
                'message' => 'plan created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $plan)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string',
                'logo' => 'string',
                'description' => 'string',
                'website' => 'string',
                'slogan' => 'string',
                'phone' => 'numeric',
                'email' => 'email',
                'address' => 'string',
                'email_verified_at' => 'date',
                'is_active' => 'boolean',
                'plan_id' => 'exists:plans,id',
                'settings' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldData = $plan->toArray();
            $plan->update($request->all());

            _logger('success', 'Company', 'update', $request->all(), $oldData);

            return response()->json([
                'type' => 'success',
                'message' => "plan ($request->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'update', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Company $plan)
    {
        try {
            $plan->delete();
            _logger('success', 'Company', 'delete', $plan);

            return response()->json([
                'type' => 'success',
                'message' => "plan ($plan->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'delete', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($plan)
    {
        try {
            $plan = Company::withTrashed()->where('id', $plan)->first();

            if (!$plan) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'plan not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            $plan->restore();
            _logger('success', 'Company', 'restore', $plan);

            return response()->json([
                'type' => 'success',
                'message' => "plan ($plan->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'restore', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }   
    }

    // public function destroy($plan)
    // {
    //     try {
    //         $plan = Company::withTrashed()->where('id', $plan)->first();
    //         $plan->forceDelete();
    //         _logger('success', 'Company', 'destroy', $plan);

    //         return response()->json([
    //             'type' => 'success',
    //             'message' => "plan ($plan->id) destroyed successfully.",
    //         ], ResponseCode::HTTP_OK);
    //     } catch (\Exception $e) {
    //         _logger('error', 'Company', 'destroy', $e->getMessage());

    //         return response()->json([
    //             'type' => 'error',
    //             'message' => 'server error 500.',
    //         ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function onlyTrash(Request $request)
    {
        try {
            $companie = Company::onlyTrashed();

            $companie = $this->_fetchData($request, $companie);

            return PlanResource::collection($companie);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function withTrash(Request $request)
    {
        try {
            $companie = Company::withTrashed();
            
            $companie = $this->_fetchData($request, $companie);

            return PlanResource::collection($companie);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'withTrashed', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function _fetchData(Request $request, $query)
    {
        if (!$request->has('per') || is_null($request->input('per'))) {
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
