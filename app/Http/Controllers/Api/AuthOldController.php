<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthOldController extends Controller
{
    public function login(Request $request)
    {
        // Cari user berdasarkan email/nip/nuptk
        $user = Pegawai::where('email', $request->email)
            ->orWhere('nip', $request->email)
            ->orWhere('nuptk', $request->email)
            ->first();

        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Cek status aktif
        if ($user->active === 2) {
            return response()->json(['message' => 'Akun Anda Non Aktif'], 401);
        }

        // Cek IMEI
        if ($user->imei != null && $user->imei != $request->imei && $user->id != '1212321') {
            return response()->json(['message' => 'ID Device Tidak Terdaftar'], 401);
        }

        if ($user->id != '1212321') {
            $cek_imei_ = Pegawai::where('imei', $request->imei)
                ->where('id', '!=', $user->id)
                ->first();

            if ($cek_imei_) {
                return response()->json([
                    'message' => 'Imei Sudah Terdaftar Atas Nama ' . $cek_imei_->name
                ], 401);
            }

            $user->imei = $request->imei;
            $user->save();
        }

        // LOGIN JWT
        $credentials = ['email' => $user->email, 'password' => $request->password];
        $token = auth('api')->attempt($credentials);

        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Tambah versi
        $versi = DB::select('select versi from versi');
        $user->versi = $versi[0]->versi;

        return response()->json([
            'status' => 1,
            'message' => 'Login success',
            'results' => [
                'data' => [$user],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Logout success']);
    }
}
