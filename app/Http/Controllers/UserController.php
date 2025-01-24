<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Http\Resources\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;

class UserController extends Controller
{
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

            if ($requestedUser->role == 'admin') {
                $users = User::all();
            }
            else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            return response()->json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            _logger('error', 'User', 'index', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(User $user)
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
                $user = User::where('id', $user->id)->first();
            }
            else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'user not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            return new Resource($user);
        } catch (\Exception $e) {
            _logger('error', 'User', 'show', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $request->merge([
    //             'is_active' => $request->has('is_active') && !!$request->input('is_active'),
    //             'can_buying' => $request->has('can_buying') && !!$request->input('can_buying'),
    //         ]);

    //         $validator = Validator::make($request->all(), [
    //             'first_name' => 'required|string',
    //             'last_name' => 'required|string',
    //             'phone' => 'numeric|unique:users,phone',
    //             'email' => 'email|unique:users',
    //             'job_title' => 'string',
    //             'national_id' => 'numeric|unique:users,national_id',
    //             'is_active' => 'boolean',
    //             'gender' => 'in:male,female',
    //             'role' => 'in:admin,owener,user',
    //             'phone_verified_at' => 'date',
    //             'email_verified_at' => 'date',
    //             'birthday' => 'date',
    //             'password' => 'required',
    //             'settings' => 'array',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $validator->errors(),
    //             ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
    //         }
    //         User::create([
    //             'uuid' => Str::orderedUuid()->toString(),
    //             'first_name' => $request->first_name,
    //             'last_name' => $request->last_name,
    //             'phone' => $request->phone,
    //             'email' => $request->email,
    //             'job_title' => $request->job_title,
    //             'national_id' => $request->national_id,
    //             'is_active' => $request->is_active,
    //             'gender' => $request->gender,
    //             'role' => $request->role,
    //             'phone_verified_at' => $request->phone_verified_at,
    //             'email_verified_at' => $request->email_verified_at,
    //             'birthday' => $request->birthday,
    //             'password' => bcrypt($request->password),
    //             'settings' => json_encode($request->settings),
    //         ]);

    //         _logger('success', 'User', 'store', $request->all());

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'user created successfully.',
    //         ], ResponseCode::HTTP_CREATED);
    //     } catch (\Exception $e) {
    //         _logger('error', 'User', 'store', $e->getMessage());

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'server error 500.',
    //         ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function update(Request $request, User $user)
    {
        try {
            $requsetedUser = auth('api')->user();

            if ($requsetedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requsetedUser != $user && $requsetedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            $allowed = ['first_name', 'last_name', 'email', 'job_title', 'national_id', 'is_active', 'gender', 'email_verified_at', 'phone_verified_at', 'birthdate', 'settings'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'first_name' => 'string',
                'last_name' => 'string',
                'email' => 'email',
                'job_title' => 'string',
                'national_id' => 'numeric',
                'is_active' => 'boolean',
                'gender' => 'in:male,female',
                'email_verified_at' => 'date',
                'birthdate' => 'date',
                'settings' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->has('is_active') && $requsetedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            $oldData = $user->toArray();
            $user->update($request->all());

            _logger('success', 'User', 'update', $request->all(), $oldData);

            return response()->json([
                'status' => 'success',
                'message' => "user ($request->id) updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'update', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function telegramId(Request $request)
    {
        try {
            $allowed = ['uuid', 'telegram_id'];
            $filterRequest = $request->only($allowed);
            $extra = array_diff(array_keys($request->all()), $allowed);

            if (!empty($extra)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The following fields are not allowed: ' . implode(', ', $extra),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validator = Validator::make($filterRequest, [
                'uuid' => 'required|string',
                'telegram_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::where('uuid', $request->uuid)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'user not found.',
                ], ResponseCode::HTTP_NOT_FOUND);
            }

            $telegram_id = ($request->telegram_id == 0) ? null : $request->telegram_id;
            $user->telegram_id = $telegram_id;
            $user->save();

            _logger('success', 'User', 'setTelegramId', $request->all());

            return response()->json([
                'status' => 'success',
                'message' => "user ($user->id) telegram id updated successfully.",
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'setTelegramId', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function delete(User $user)
    // {
    //     try {
    //         $user->delete();
    //         _logger('success', 'User', 'delete', $user);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "user ($user->id) deleted successfully.",
    //         ], ResponseCode::HTTP_OK);
    //     } catch (\Exception $e) {
    //         _logger('error', 'User', 'delete', $e->getMessage());

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'server error 500.',
    //         ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function restore($user)
    // {
    //     try {
    //         $user = User::withTrashed()->where('id', $user)->first();

    //         if (!$user) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'user not found.',
    //             ], ResponseCode::HTTP_NOT_FOUND);
    //         }
    //         $user->restore();
    //         _logger('success', 'User', 'restore', $user);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "user ($user->id) restored successfully.",
    //         ], ResponseCode::HTTP_OK);
    //     } catch (\Exception $e) {
    //         _logger('error', 'User', 'restore', $e->getMessage());

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

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }
            $users = User::onlyTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            _logger('error', 'User', 'onlyTrashed', $e->getMessage());

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

            if ($requestedUser == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'unauthorized.',
                ], ResponseCode::HTTP_UNAUTHORIZED);
            }

            if ($requestedUser->role != 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'permission denied.'
                ], ResponseCode::HTTP_FORBIDDEN);
            }

            $users = User::withTrashed()->get();

            return response()->json([
                'status' => 'success',
                'data' => $users
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            _logger('error', 'User', 'withTrashed', $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.',
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
