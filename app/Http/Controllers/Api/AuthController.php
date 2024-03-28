<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            "name" => "required|string",
            'email' => "required|email",
            'password' => "required|string|min:6",
            'pin' => "required|digits:6",
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $user = User::where('email', $request->email)->exists();

        if ($user) {
            return response()->json(['message' => 'Email already taken'], 409);
        }

        DB::beginTransaction();

        try {
            $profilePicture = null;
            $ktp = null;

            if ($request->profile_picture) {
                $profilePicture = $this->uploadBase64Image($request->profile_picture);
            }


            if ($request->ktp) {
                $ktp = $this->uploadBase64Image($request->ktp);
            }

            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "username" => $request->username,
                "password" => bcrypt($request->password),
                "profile_picture" => $profilePicture,
                "ktp" => $ktp,
                "verified" => ($ktp) ? true : false,
            ]);

            Wallet::create([
                "user_id" => $user->id,
                "balance" => 0,
                "pin" => $request->pin,
                "card_number" => $this->generateCardNumber(16),
            ]);
            DB::commit();

            $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);
            $userResponse = getUser($request->email);
            $userResponse->token = $token;
            $userResponse->token_expires_in = auth()->factory()->getTTl() * 60;
            $userResponse->token_type = 'bearer';

            return response()->json($userResponse);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["message" => $th->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only(["email", "password"]);

        $validator = Validator::make($credentials, [
            "email" => "required|email",
            "password" => "required|string|min:6",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->messages()], 400);
        }

        try {
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                return response()->json(["message" => "Login credentials are invalid"], 400);
            }

            $userResponse = getUser($request->email);
            $userResponse->token = $token;
            $userResponse->token_expires_in = auth()->factory()->getTTl() * 60;
            $userResponse->token_type = 'bearer';

            return response()->json($userResponse);
        } catch (JWTException $th) {
            return response()->json(["message" => $th->getMessage()], 500);
        }
    }

    private function uploadBase64Image($base64Image)
    {
        $decoder = new Base64ImageDecoder($base64Image, $allowedFormats = ['jpeg', 'png', 'gif']);
        $decodedContent = $decoder->getDecodedContent();
        $format = $decoder->getFormat();
        $image = Str::random(10) . '.' . $format;
        Storage::disk('public')->put($image, $decodedContent);

        return $image;
    }

    private function generateCardNumber($length)
    {
        $result = 0;
        for ($i = 1; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
        $wallet = Wallet::where('card_number', $result)->exists();

        if ($wallet) {
            return $this->generateCardNumber($length);
        };

        return $result;
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Logout Succes'], 200);
    }

    // public function refresh()
    // {
    //     $token = JWTAuth::getToken();
    //     return JWTAuth::refresh($token);
    // }
}
