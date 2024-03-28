<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function show()
    {
        $user = getUser(auth()->user()->id);

        return response()->json($user, 200);
    }

    public function getByUsername($username)
    {
        $user = User::select(
            'id',
            'name',
            'username',
            'verified',
            'profile_picture'
        )
            ->where('username', 'LIKE', "%" . $username . '%')
            ->where('id', '<>', auth()->user()->id)->get();

        $user->map(function ($item) {
            $item->profile_picture = $item->profile_picture ? url('storage/' . $item->profile_picture) : '';
            return $item;
        });

        return response()->json($user, 200);
    }

    public function update(Request $request)
    {

        try {
            $user = User::find(auth()->user()->id);

            $data = $request->only(['name', 'username', 'ktp', 'email', 'password']);

            if ($request->name != $user->name) {
                $isExistname = User::where('name', $request->name)->exists();
                if ($isExistname) {
                    return response()->json(['message' => 'Name Already Taken'], 409);
                }
            }

            if ($request->username != $user->username) {
                $isExistUsername = User::where('username', $request->username)->exists();
                if ($isExistUsername) {
                    return response()->json(['message' => 'Username Already Taken'], 409);
                }
            }


            if ($request->email != $user->email) {
                $isExistEmail = User::where('email', $request->email)->exists();
                if ($isExistEmail) {
                    return response()->json(['message' => 'Email Already Taken'], 409);
                }
            }

            if ($request->password) {
                $data['password'] = bcrypt($request->password);
            }

            if ($request->profile_picture) {
                $profile_picture = uploadBase64Image($request->profile_picture);
                $data['profile_picture'] = $profile_picture;
                if ($user->profile_picture) {
                    Storage::delete('public/' . $user->profile_picture);
                }
            }

            if ($request->ktp) {
                $ktp = uploadBase64Image($request->ktp);
                $data['ktp'] = $ktp;
                $data['verified'] = true;
                if ($user->ktp) {
                    Storage::delete('public/' . $user->ktp);
                }
            }

            $user->update($data);

            return response()->json(['message' => 'User Updated'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function isEmailExist(Request $request)
    {
        $validator = Validator::make($request->only('email'), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $isExist = User::where('email', $request->email)->exists();

        return response()->json(['is_email_exist' => $isExist], 200);
    }
}
