<?php

namespace App\Http\Controllers\Api;


use App\Models\IzinPegawai;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AttendancesTeacher;
use App\Models\JenisIzin;
use Illuminate\Support\Facades\Validator;
use App\Traits\Upload;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class IzinController extends Controller
{
    use Upload;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = new IzinPegawai();
        if ($request->has('filter')) {
            $data = $data->where($request->input('filter'));
        }
        if ($request->has('search')) {
            // $data = $data->where('judul', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $data->paginate(25)->withQueryString();
        } else {
            $result = $data->with('jenis_izin')->where('pegawai_id', $request->user()->id)->orderBy('id', 'DESC')->get();
        }

        unset($request, $data);

        return response()->json([
            'message' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        $validator = Validator::make($request->all(), [
            'tgl'    => 'required',
            'tgl_sampai'    => 'required',
            'jenis_izin_id' => 'required'
        ], [
            'required' => ':attribute harus terisi.',
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, $code);
        }

        $cek_izin = IzinPegawai::where(['pegawai_id' => $request->user()->id,])
            ->whereMonth('tgl', date('m', strtotime($request->tgl)))
            ->orderBy('tgl', 'asc')
            ->get();

        $tgl_terdaftar = [];
        foreach ($cek_izin as $data) {
            $begin = new DateTime($data->tgl);
            $end = new DateTime($data->sampai_tgl);
            $end->modify('+1 day');
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);
            foreach ($period as $dt) {
                array_push($tgl_terdaftar, $dt->format("Y-m-d"));
            }
        }

        $tgl_pengajuan = [];
        $begin = new DateTime($request->tgl);
        $end = new DateTime($request->tgl_sampai);
        $end->modify('+1 day');
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ($period as $dt) {
            array_push($tgl_pengajuan, $dt->format("Y-m-d"));
        }

        $cek_tgl_pengajuan = array_intersect($tgl_terdaftar, $tgl_pengajuan);

        if (count($cek_tgl_pengajuan) > 0) {
            return response()->json([
                'message' => 'Izin Sudah di Ajukan'
            ]);
        }

        $path = null;

        if ($request->hasFile('file')) {
            $path = $this->UploadFile($request->file('file'), 'lampiran_izin_pegawai'); //use the method in the trait
        }

        IzinPegawai::create([
            'pegawai_id' => $request->user()->id,
            'tgl'     => $request->tgl,
            'sampai_tgl'     => $request->tgl_sampai,
            'desc'     => $request->desc,
            'jenis_izin_id' => $request->jenis_izin_id,
            'attachment'   => url('/storage/') . '/' . $path,
            'path' => $path,
            'dinas_id' =>
            $request->user()->dinas_id,
        ]);

        return response()->json([
            'message' => 'Izin Berhasil di Ajukan'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(IzinPegawai $izin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IzinPegawai $izin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IzinPegawai $izin)
    {
        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        $validator = Validator::make($request->all(), [
            'id'    => 'required',
            // 'file' => 'max:2000',
        ], [
            'required' => ':attribute harus terisi.',
            // 'uploaded' => ':attribute maksimal 2MB'
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, $code);
        }

        if ($request->hasFile('file')) {
            $path = $this->UploadFile($request->file('file'), 'lampiran_izin_pegawai'); //use the method in the trait
            if (!is_null($izin->attachment)) {
                $this->deleteFile($izin->path);
            }

            $izin->update([
                'pegawai_id' => $request->user()->id,
                'tgl'     => $request->tgl,
                'desc'     => $request->desc,
                'jenis_izin_id' => $request->jenis_izin_id,
                'attachment'   => url('/storage/') . '/' . $path,
                'path' => $path
            ]);
        } else {
            $izin->update([
                'pegawai_id' => $request->user()->id,
                'tgl'     => $request->tgl,
                'desc'     => $request->desc,
                'jenis_izin_id' => $request->jenis_izin_id
            ]);
        }

        return response()->json([
            'message' => 'Izin Berhasil di Update'
        ]);
    }

    public function updateIzin(Request $request)
    {
        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        $validator = Validator::make($request->all(), [
            'id'    => 'required',
            // 'file' => 'max:2000',
        ], [
            'required' => ':attribute harus terisi.',
            // 'uploaded' => ':attribute maksimal 2MB'
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, $code);
        }

        $izin = IzinPegawai::where('id', $request->id)->first();

        if ($request->hasFile('file')) {
            $path = $this->UploadFile($request->file('file'), 'lampiran_izin_pegawai'); //use the method in the trait
            if (!is_null($izin->path)) {
                $this->deleteFile($izin->path);
            }

            $izin->update([
                'desc'     => $request->desc,
                'attachment'   => url('/storage/') . '/' . $path,
                'path' => $path
            ]);
        } else {
            $izin->update([
                'desc'     => $request->desc
            ]);
        }

        return response()->json([
            'message' => 'Izin Berhasil di Update'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IzinPegawai $izin)
    {

        if (!is_null($izin->path)) {
            $this->deleteFile($izin->path);
        }

        $izin->delete();
        return response()->json([
            'message' => 'success',
            'data' => []
        ]);
    }
}
