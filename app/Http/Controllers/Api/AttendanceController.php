<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function clockIn(Request $request, AttendanceService $service)
    {
        try {
            $absen = $service->clockIn($request->user());
            return response()->json(['message' => 'Presensi berhasil', 'id_absen' => $absen->id]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function clockOut(Request $request, AttendanceService $service)
    {
        try {
            $service->clockOut($request->user());
            return response()->json(['message' => 'Presensi pulang berhasil']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
