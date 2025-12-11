<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function login(Request $request)
    {

        if (!Auth::guard('pegawai')->attempt(['nip' => $request->email, 'password' => $request->password]) && !Auth::guard('pegawai')->attempt(['email' => $request->email, 'password' => $request->password]) && !Auth::guard('pegawai')->attempt(['nuptk' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = Pegawai::where('email', $request->email)->orWhere('nip', $request->email)->orWhere('nuptk', $request->email)->firstOrFail();

        if ($user->active === 2) {
            return response()->json([
                'message' => 'Akun Anda Non Aktif'
            ], 401);
        }
        if ($user->imei != null && $user->imei != $request->imei && $user->id != '1212321') {
            return response()->json([
                'message' => 'ID Device Tidak Terdaftar'
            ], 401);
        }

        if ($user->id != '1212321') {
            $cek_imei_ = Pegawai::where(
                'imei',
                $request->imei,
            )->where('id', '!=', $user->id)->first();
            if ($cek_imei_ != null) {
                return response()->json([
                    'message' => 'Imei Sudah Terdaftar Atas Nama ' . $cek_imei_->name . "\n",
                ], 401);
            }
            $user->imei = $request->imei;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $versi = DB::select('select versi from versi');
        $user->versi = $versi[0]->versi;

        return response()->json([
            'status' => 1,
            'message' => 'Login success',
            'results' => ['data' => [$user], 'access_token' => $token, 'token_type' => 'Bearer',]
        ]);
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json([
            'message' => 'logout success'
        ]);
    }
}
