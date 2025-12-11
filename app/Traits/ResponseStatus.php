<?php

namespace App\Traits;


trait ResponseStatus
{

    public function responseLogin($status, $message = NULL, $redirect = 'reload'): array
    {
        if ($status == true) {
            return [
                'status' => 'success',
                'message' => 'Login Sukses',
                'redirect' => $redirect
            ];
        }
        return [
            'status' => 'error',
            'message' => $message ?? 'Login Gagal',
        ];
    }

    public function responseStore($status, $message = NULL, $redirect = 'reload'): array
    {
        if ($status == true) {
            return [
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'redirect' => $redirect
            ];
        }
        return [
            'status' => 'error',
            'message' => $message ?? 'Data gagal dibuat',
        ];
    }

    public function responseUpdate($status, $redirect = 'reload'): array
    {
        if ($status == true) {
            return [
                'status' => 'success',
                'message' => 'Data berhasil diubah',
                'redirect' => $redirect
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Data gagal diubah'
        ];
    }

    public function responseDelete($status, $redirect = 'reload'): array
    {
        if ($status == true) {
            return [
                'status' => 'success',
                'message' => 'Data berhasil dihapus',
                'redirect' => $redirect
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Data gagal dihapus'
        ];
    }
}
