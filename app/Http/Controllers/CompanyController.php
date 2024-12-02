<?php

namespace App\Http\Controllers;

use App\Http\Resources\Resource;
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

            return Resource::collection($comapnies);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($company)
    {
        try {
            $company = Company::withTrashed()
                ->where('id', $company)
                // ->with('companies')
                ->first();

            if (!$company) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'company not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($company);
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
            _logger('success', 'Company', 'store', $request->all());

            return response()->json([
                'type' => 'success',
                'message' => 'company created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request,Company $company)
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

            $oldData = $company->toArray();
            $company->update($request->all());

            _logger('success', 'Company', 'update', $request->all(), $oldData);

            return response()->json([
                'type' => 'success',
                'message' => "company ($request->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'update', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Company $company)
    {
        try {
            $company->delete();
            _logger('success', 'Company', 'delete', $company);

            return response()->json([
                'type' => 'success',
                'message' => "company ($company->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'delete', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($company)
    {
        try {
            $company = Company::withTrashed()->where('id', $company)->first();

            if (!$company) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'company not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            $company->restore();
            _logger('success', 'Company', 'restore', $company);

            return response()->json([
                'type' => 'success',
                'message' => "company ($company->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'restore', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }   
    }

    // public function destroy($company)
    // {
    //     try {
    //         $company = Company::withTrashed()->where('id', $company)->first();
    //         $company->forceDelete();
    //         _logger('success', 'Company', 'destroy', $company);

    //         return response()->json([
    //             'type' => 'success',
    //             'message' => "company ($company->id) destroyed successfully.",
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
            $companies = Company::onlyTrashed();

            $companies = $this->_fetchData($request, $companies);

            return Resource::collection($companies);
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
            $companies = Company::withTrashed();
            
            $companies = $this->_fetchData($request, $companies);

            return Resource::collection($companies);
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
