<?php

namespace App\Http\Controllers\api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Providers\AppServiceProvider as AppSP;

class AuthController extends Controller
{
    /**
     * Create User
     * @param Request $request
     */
    public function createUser(Request $request)
    {
        try {
            //Validated
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'phone' => 'required|digits:10|unique:users,phone',
                    'fcm_token'=>'string',
                    'password' => 'required|min:8|confirmed'

                ]
            );

            if ($validateUser->fails()) {


                return AppSP::apiResponse('validation error', $validateUser->errors(), 'errors', false, 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'fcm_token'=>$request->fcm_token
            ]);

            return AppSP::apiResponse('User Created Successfully', $user->createToken("API TOKEN", ['user'])->plainTextToken, 'token', true);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Login The User
     * @param Request $request
     */
    public function loginUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'phone' => 'required|digits:10',
                    'password' => 'required|min:8',
                    'fcm_token'=>'required|string'
                ]
            );

            if ($validateUser->fails()) {

                return AppSP::apiResponse('validaion error', $validateUser->errors(), 'errors', false, 422);
            }

            if (!Auth::attempt($request->only(['phone', 'password']), true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Phone Number & Password does not match with our record.',
                ], 401);
            }


            $user = User::where('phone', $request->phone)->first();
            $user->update(['fcm_token'=>$request->fcm_token]);
            return AppSP::apiResponse('User Logged In Successfully', $user->createToken("API TOKEN", [$user->role])->plainTextToken, 'token');
            } catch (\Throwable $th) {
                return response()->json([
                'status' => false,
                'message' => $th->getMessage()
                ], 500);
                }
    }
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logged out',
        ]);
    }
}
