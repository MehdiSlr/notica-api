<?php

namespace App\Http\Controllers;


use App\Models\Code;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\SendSMS;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Validator;

class AuthController extends Controller
{
    use SendSMS;
    public function checkPhone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|numeric|digits:11',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                User::create([
                    'phone' => $request->phone,
                    'uuid' => Str::orderedUuid()->toString(),
                ]);
            }

            $sent = $this->_sendCode($request->phone);

            if (!$sent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'verification code not sent',
                ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
            }

            $is_phone_exist = Code::where('phone', $request->phone)->first();
            if ($is_phone_exist) {
                $is_phone_exist->update([
                    'code' => $sent,
                ]);
            } else {
                Code::create([
                    'phone' => $request->phone,
                    'code' => $sent,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'verification code sent to phone number',
                'phone' => $request->phone,
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyPhone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|numeric|digits:11',
                'code' => 'required|numeric|digits:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $code = Code::where('phone', $request->phone)->where('code', $request->code)->first();
            if (!$code) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'wrong code',
                ], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $code->delete();

            $user = User::where('phone', $request->phone)->first();

            if ($user->phone_verified_at == null) {
                $user->update([
                    'phone_verified_at' => now(),
                ]);
            }

            $token = JWTAuth::fromUser($user);
            return response()->json([
                'status' => 'success',
                'message' => 'phone verified successfully',
                'auth' => [
                    'type' => 'bearer',
                    'token' => $token,
                    'expires_at' => now()->addMinutes(config('jwt.ttl'))->toDateTimeString(),
                ],
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function refresh()
    // {
    //     try{
    //         $token = JWTAuth::getToken();

    //         if (!$token) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'token not provided',
    //             ], 401);
    //         }

    //         try {
    //             $token = JWTAuth::refresh($token);
    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'token invalid',
    //             ], 401);
    //         }
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'token refreshed successfully',
    //             'token' => $token,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'server error 500.' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function login(Request $request)
    // {
    //     $credentials = $request->only('phone', 'password');

    //     if (!$token = Auth::attempt($credentials)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }

    //     return response()->json(['token' => $token]);
    // }

    public function logout()
    {
        try{
            //invalidate token
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ], ResponseCode::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'server error 500.' . $e->getMessage(),
            ], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function me()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'unauthorized.',
            ], ResponseCode::HTTP_UNAUTHORIZED);
        }
        return response()->json(Auth::user());
    }
}
