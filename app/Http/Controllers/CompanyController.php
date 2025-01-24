<?php

namespace App\Http\Controllers;

use App\Http\Resources\Resource;
use App\Models\Company;
use App\Models\User;
use App\Traits\FileManager;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;

class CompanyController extends Controller
{
    use FileManager;

    public function index()
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->role == 'admin'){
                $companies = Company::all();
            } else {
                $companies = Company::query()->whereHas('messages', function ($query) use ($requestedUser) {
                    $query->where('to', $requestedUser->id);
                })->get();
            }

            return response()->json([
                'status' => 'success',
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($company)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->role == 'admin'){
                $company = Company::withTrashed()
                    ->where('id', $company)
                    ->first();
            } else if ($requestedUser->role == 'user') {
                $company = Company::query()
                    ->whereHas('messages', function ($query) use ($requestedUser) {
                        $query->where('to', $requestedUser->id);
                    })
                    ->where('id', $company)
                    ->first();
            }

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'company not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($company);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'show', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logo(Request $request)
    {
        try{
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['logo'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'logo' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_BAD_REQUEST);
            }

            $fileName = $this->_uploadFile($request->file('logo'));
            _logger('success', 'Company', 'logo', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'logo uploaded successfully.',
                'data' => [
                    'file_name' => $fileName
                ]
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'logo', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.'. $e->getMessage(),
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

            if ($requestedUser->role == 'owner') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'you are already an owner of a company.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            //check nessesary fields are not null
            if ($requestedUser->first_name == null || $requestedUser->last_name == null || $requestedUser->national_id == null || $requestedUser->gender == null || $requestedUser->is_active == false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'please complete your profile first.',
                    'data' => $requestedUser
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $allowed = ['name', 'logo', 'description', 'website', 'slogan', 'phone', 'email', 'national_id', 'address', 'established_date', 'email_verified_at', 'settings'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'name' => 'required|string',
                'logo' => 'string',
                'description' => 'string',
                'website' => 'string',
                'slogan' => 'string',
                'phone' => 'numeric|unique:companies,phone',
                'email' => 'email|unique:companies,email',
                'national_id' => 'numeric|unique:companies,national_id',
                'address' => 'required|string',
                'established_date' => 'date',
                'email_verified_at' => 'date',
                'settings' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->has('logo')) {
                if (strpos($request->logo, '/')) {
                    $fileParts = explode('/', $request->logo);
                    $fileName = end($fileParts);
                } else {
                    $fileName = $request->logo;
                }

                $fileRealName ="images/logos/logo_{$request->name}_$fileName";
                $path = env('APP_URL')."/upload/$fileRealName";
                $isMoved = Storage::move("tmp/$fileName", $fileRealName);

                if (!$isMoved) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "move prosess failed. image $fileName not found.",
                    ], ResponseCode::HTTP_NOT_FOUND);
                }

                $request->request->replace(['logo' => $path]);
            }

            $owner = $requestedUser->id;
            $request->request->add([
                'owner' => $owner,
                'plan_id' => 1,
            ]);

            Company::create($request->all());

            $user = User::where('id', $owner)->first();
            $user->update([
                'role' => 'owner',
            ]);

            _logger('success', 'Company', 'store', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => "company ({$request->name}) created successfully.",
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'store', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request,Company $company)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->id != $company->owner && $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.',
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            $allowed = ['name', 'logo', 'description', 'website', 'slogan', 'phone', 'email', 'national_id', 'address', 'email_verified_at', 'is_active', 'plan_id', 'settings'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
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
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            //if request has "is_active"
            if (isset($request->is_active) && $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            //if request has "plan_id"
            if (isset($request->plan_id) && $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            if ($request->has('logo')) {
                if (strpos($request->logo, '/')) {
                    $fileParts = explode('/', $request->logo);
                    $fileName = end($fileParts);
                } else {
                    $fileName = $request->logo;
                }

                $fileRealName ="images/logos/logo_{$company->name}_$fileName";
                $path = env('APP_URL')."/upload/$fileRealName";
                $isMoved = Storage::move("tmp/$fileName", $fileRealName);

                if (!$isMoved) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "move prosess failed. image $fileName not found.",
                    ], ResponseCode::HTTP_NOT_FOUND);
                }

                $request->request->replace(['logo' => $path]);
            }

            $oldData = $company->toArray();
            $company->update($request->all());

            _logger('success', 'Company', 'update', $request->all(), $oldData);

            return response()->json([
                'status' => 'success',
                'message' => "company ($company->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'update', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Company $company)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $company->delete();
            _logger('success', 'Company', 'delete', $company);

            return response()->json([
                'status' => 'success',
                'message' => "company ($company->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'delete', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($company)
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $company = Company::withTrashed()->where('id', $company)->first();

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'company not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            $company->restore();
            _logger('success', 'Company', 'restore', $company);

            return response()->json([
                'status' => 'success',
                'message' => "company ($company->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'restore', $e->getMessage());

            return response()->json([
                'status' => 'error',
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
    //             'status' => 'success',
    //             'message' => "company ($company->id) destroyed successfully.",
    //         ], ResponseCode::HTTP_OK);
    //     } catch (\Exception $e) {
    //         _logger('error', 'Company', 'destroy', $e->getMessage());

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'server error 500.',
    //         ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function onlyTrash()
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $companies = Company::onlyTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function withTrash()
    {
        try {
            $requestedUser = auth('api')->user();

            if ($requestedUser == null || $requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            $companies = Company::withTrashed()->get();


            return response()->json([
                'status' => 'success',
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            _logger('error', 'Company', 'withTrashed', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
