<?php

namespace App\Http\Controllers;

use App\Http\Resources\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $users = User::query();

            $users = $this->_fetchData($request, $users);

            return Resource::collection($users);
        } catch (\Exception $e) {
            _logger('error', 'User', 'index', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(User $user)
    {
        try {
            if (!$user) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'user not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($user);
        } catch (\Exception $e) {
            _logger('error', 'User', 'show', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->merge([
                'is_active' => $request->has('is_active') && !!$request->input('is_active'),
                'can_buying' => $request->has('can_buying') && !!$request->input('can_buying'),
            ]);
            
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'numeric|unique:users,phone',
                'email' => 'email|unique:users',
                'job_title' => 'string',
                'national_id' => 'numeric|unique:users,national_id',
                'is_active' => 'boolean',
                'gender' => 'in:male,female',
                'role' => 'in:admin,owener,user',
                'phone_verified_at' => 'date',
                'email_verified_at' => 'date',
                'birthday' => 'date',
                'password' => 'required',
                'settings' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }
            User::create([
                'uuid' => Str::orderedUuid()->toString(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'job_title' => $request->job_title,
                'national_id' => $request->national_id,
                'is_active' => $request->is_active,
                'gender' => $request->gender,
                'role' => $request->role,
                'phone_verified_at' => $request->phone_verified_at,
                'email_verified_at' => $request->email_verified_at,
                'birthday' => $request->birthday,
                'password' => bcrypt($request->password),
                'settings' => json_encode($request->settings),
            ]); 

            _logger('success', 'User', 'store', $request->all());

            return response()->json([
                'type' => 'success',
                'message' => 'user created successfully.',
            ], ResponseCode::HTTP_CREATED);
        } catch (\Exception $e) {
            _logger('error', 'User', 'store', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'string',
                'last_name' => 'string',
                'phone' => 'numeric',
                'email' => 'email',
                'job_title' => 'string',
                'national_id' => 'numeric',
                'is_active' => 'boolean',
                'gender' => 'in:male,female',
                'role' => 'in:admin,owener,user',
                'phone_verified_at' => 'date',
                'email_verified_at' => 'date',
                'birthday' => 'date',
                'settings' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $oldData = $user->toArray();
            $user->update($request->all());
            
            _logger('success', 'User', 'update', $request->all(), $oldData);

            return response()->json([
                'type' => 'success',
                'message' => "user ($request->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'update', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(User $user)
    {
        try {
            $user->delete();
            _logger('success', 'User', 'delete', $user);

            return response()->json([
                'type' => 'success',
                'message' => "user ($user->id) deleted successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'delete', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore($user)
    {
        try {
            $user = User::withTrashed()->where('id', $user)->first();

            if (!$user) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'user not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }
            $user->restore();
            _logger('success', 'User', 'restore', $user);

            return response()->json([
                'type' => 'success',
                'message' => "user ($user->id) restored successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'restore', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }   
    }

    public function onlyTrash(Request $request)
    {
        try {
            $users = User::onlyTrashed();

            $users = $this->_fetchData($request, $users);

            return response()->json([
                'type' => 'success',
                'message' => 'users',
                'data' => $users,
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'onlyTrashed', $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function withTrash(Request $request)
    {
        try {
            $users = User::withTrashed();

            $users = $this->_fetchData($request, $users);

            return response()->json([
                'type' => 'success',
                'message' => 'users',
                'data' => $users,
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'withTrashed', $e->getMessage());

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
