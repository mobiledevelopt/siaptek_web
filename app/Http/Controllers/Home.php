<?php

namespace App\Http\Controllers;

use App\Models\AttendancesPegawai;
use App\Models\IzinPegawai;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;


class Home extends Controller
{
    public function Index(Request $request): View
    {

        if ($request->user()->role_id != 1) {
            // $absen = AttendancesPegawai::where('dinas_id', $request->user()->dinas_id)->whereYear('date_attendance', date('Y'))->whereMonth('date_attendance', date('m'))->get();
            $absen = AttendancesPegawai::where('dinas_id', $request->user()->dinas_id)->whereBetween('date_attendance', [date('Y-m-01'), date('Y-m-d')])->get(['status','tpp_diterima']);
            $total_absen = $absen->count();
            $a_izin = $absen->where('status', 'izin')->count();
            $a_cuti = $absen->where('status', 'cuti')->count();
            $a_masuk = $absen->where('status', 'Masuk')->count();
            $a_tidak_masuk = $absen->where('status', '!=', 'Masuk')->count();
            $total_tpp = $absen->sum('tpp_diterima');
            $izin = IzinPegawai::where('dinas_id', $request->user()->dinas_id)->whereYear('tgl', date('Y'))->whereMonth('tgl', date('m'))->get();
            $total_izin = $izin->count();
            $izin_aproved = $izin->where('status', 'Di Terima')->count();
            $izin_not_aproved = $izin->where('status', 'Di Tolak')->count();

            $dash = [
                'izin' => DB::table('izin_pegawai')->select(DB::raw('COUNT(*) as num'))->where('dinas_id', $request->user()->dinas_id)->first(),
                'pegawai' => DB::table('pegawai')->select(DB::raw('COUNT(*) as num'))->where('dinas_id', $request->user()->dinas_id)->first(),
                'pengumuman' => DB::table('pengumuman')->select(DB::raw('COUNT(*) as num'))->where('dinas_id', $request->user()->dinas_id)->where('tgl', '>=', date('Y-m-d'))->first(),
                'absen' => DB::table('attendances_pegawai')->select(DB::raw('COUNT(*) as num'))->where('dinas_id', $request->user()->dinas_id)->first(),
                'a_izin' => $a_izin,
                'a_cuti' => $a_cuti,
                'a_masuk' => $a_masuk,
                'a_tidak_masuk' => $a_tidak_masuk,
                'total_absen' => $total_absen,
                'total_izin' => $total_izin,
                'izin_aproved' => $izin_aproved,
                'izin_not_aproved' => $izin_not_aproved,
                'total_tpp' => $total_tpp
            ];
        } else {
            // $absen = AttendancesPegawai::whereYear('date_attendance', date('Y'))->whereMonth('date_attendance', date('m'))->get();
            $absen = AttendancesPegawai::whereBetween('date_attendance', [date('Y-m-01'), date('Y-m-d')])->get(['status','tpp_diterima']);
            $total_absen = $absen->count();
            $a_izin = $absen->where('status', 'izin')->count();
            $a_cuti = $absen->where('status', 'cuti')->count();
            $a_masuk = $absen->where('status', 'Masuk')->count();
            $a_tidak_masuk = $absen->where('status', '!=', 'Masuk')->count();
            $total_tpp = $absen->sum('tpp_diterima');
            $izin = IzinPegawai::whereYear('tgl', date('Y'))->whereMonth('tgl', date('m'))->get();
            $total_izin = $izin->count();
            $izin_aproved = $izin->where('status', 'Di Terima')->count();
            $izin_not_aproved = $izin->where('status', 'Di Tolak')->count();

            $dash = [
                'izin' => DB::table('izin_pegawai')->select(DB::raw('COUNT(*) as num'))->first(),
                'pegawai' => DB::table('pegawai')->select(DB::raw('COUNT(*) as num'))->first(),
                'pengumuman' => DB::table('pengumuman')->select(DB::raw('COUNT(*) as num'))->where('tgl', '>=', date('Y-m-d'))->first(),
                'absen' => DB::table('attendances_pegawai')->select(DB::raw('COUNT(*) as num'))->first(),
                'a_izin' => $a_izin,
                'a_cuti' => $a_cuti,
                'a_masuk' => $a_masuk,
                'a_tidak_masuk' => $a_tidak_masuk,
                'total_absen' => $total_absen,
                'total_izin' => $total_izin,
                'izin_aproved' => $izin_aproved,
                'izin_not_aproved' => $izin_not_aproved,
                'total_tpp' => $total_tpp
            ];
        }
        return view('contents.dashboard.index')->with([
            'title' => 'Dashboard',
            'dash' => $dash,
            'pengumuman' => DB::table('pengumuman')->where('tgl', '>=', date('Y-m-d'))->orderBy('created_at', 'desc')->skip(0)->take(5)->get()
        ]);
    }
}
