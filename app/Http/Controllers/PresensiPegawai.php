<?php

namespace App\Http\Controllers;

use App\Models\AttendancesPegawai;
use App\Models\Dinas;
use App\Traits\ResponseStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Jobs\PresensiExport;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Dompdf\Options;
use Dompdf\Dompdf;

class PresensiPegawai extends Controller
{
    use ResponseStatus;

    public function Index(Request $request)
    {
        $start = ($request->dari) ?: date('Y-m' . '-01');
        $end = ($request->hingga) ?: date('Y-m-t');
        $dinas = ($request->dinas) ?: null;

        $periode = " ";
        if ($request->dari) {
            $periode .= "PERIODE " . date('d/m/Y', strtotime($start));
        }
        if ($request->hingga) {
            $periode .= " s.d " . date('d/m/Y', strtotime($end));
        }
        if ($request->act == "excel1") {

            if ($request->user()->role_id != 1) {
                $data = $this->initData($start, $end, $request->user()->dinas_id);
            } else {
                $data = $this->initDataRekap($start, $end, $dinas);
            }

            if ($dinas == null) {
                // $job = (new PresensiExport($data, $periode, $start, $end));
                PresensiExport::dispatch($data, $periode, $start, $end);
                // dispatch($job);
                // $this->generatePrensensiAll($data, $periode, $request, $start, $end);
            } else {
                $this->generatePrensensiDinas($dinas, $data, $periode, $request, $start, $end);
            }
        }

        if ($request->status) {
            $status = $request['status'];
        } else {
            $status = null;
        }

        if ($request->ajax()) {
            if ($request->user()->role_id != 1) {
                $data = AttendancesPegawai::where('attendances_pegawai.dinas_id', $request->user()->dinas_id)->with(['pegawai', 'pegawai.dinas']);
            } else {
                $data = AttendancesPegawai::with(['pegawai', 'pegawai.dinas']);
            }

            if ($request->filled('tgl_awal')) {
                $data->whereDate('date_attendance', '>=', $request['tgl_awal']);
            }
            if ($request->filled('tgl_akhir')) {
                $data->whereDate('date_attendance', '<=', $request['tgl_akhir']);
            }

            if ($request->filled('dinas')) {
                if ($request->user()->role_id == 1) {
                    $data->where('attendances_pegawai.dinas_id', '=', $request['dinas']);
                }
            }

            if ($request->filled('status')) {
                if ($request['status'] === "Masuk") {
                    $data->where('status', '=', $request['status']);
                } else {
                    $data->where('status', '!=', 'Masuk');
                }
            }

            if ($request->user()->role_id == 1) {
                return DataTables::of($data)
                    ->addColumn('action', function ($row) {
                        $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Update Tidak Masuk</a></li>
                            </ul>
                          </div>';
                        return $actionBtn;
                    })->make();
            } else {
                return DataTables::of($data)
                    ->addColumn('action', function ($row) {
                        $actionBtn = '';

                        return $actionBtn;
                    })->make();
            }
        }

        return view("contents.presensi-pegawai.index")->with([
            'title' => "DATA PRESENSI PEGAWAI" . $periode,
            'start' => $start,
            'end' => $end,
            'status' => $status,
        ]);
    }

    protected function initData($start = null, $end = null, $id = null, $pegawai_id = null)
    {
        $data = DB::table('attendances_pegawai')
            ->select([
                "attendances_pegawai.*",
                "pegawai.nip as nip",
                "pegawai.name as nama",
                "attendances_pegawai.incoming_time as masuk",
                "attendances_pegawai.outgoing_time as pulang",
                "attendances_pegawai.status as status",
                "dinas.name as dinas"
            ])
            ->leftJoin('pegawai', 'pegawai.id', '=', 'attendances_pegawai.pegawai_id')
            ->leftJoin('dinas', 'dinas.id', '=', 'attendances_pegawai.dinas_id')
            ->whereBetween("attendances_pegawai.date_attendance", [$start, $end]);

        if ($id != null) {
            $data->where('attendances_pegawai.dinas_id', $id);
        }

        if ($pegawai_id != null) {
            $data->where('attendances_pegawai.pegawai_id', $pegawai_id);
        }

        // $data = DB::table('attendances_pegawai');
        //      $data = AttendancesPegawai::all();
        //  $users = DB::table('attendances_pegawai')->get();
        //     dd($users);
        return $data->get();
    }

    protected function initDataRekap($start = null, $end = null, $id = null)
    {

        if ($id != null) {
            $results = DB::select(
                'SELECT *,dinas.name as dinas,pegawai.nip as nip,  pegawai.name as nama, sum(potongan_absen_masuk) as potongan_absen_masuk, sum(potongan_absen_pulang) as potongan_absen_pulang, sum(potongan_tidak_masuk_kerja) as potongan_tidak_masuk_kerja, sum(potongan_cuti) as potongan_cuti , sum(potongan_tidak_apel) as potongan_tidak_apel, sum(total_potongan_tpp) as total_potongan_tpp , sum(tpp_diterima) as tpp_diterima FROM `attendances_pegawai`
                LEFT JOIN `pegawai` ON `pegawai`.`id` = `attendances_pegawai`.`pegawai_id`
                LEFT JOIN `dinas` ON `dinas`.`id` = `attendances_pegawai`.`dinas_id`
                WHERE
                `attendances_pegawai`.`date_attendance` >= "' . $start . '" AND `attendances_pegawai`.`date_attendance` <= "' . $end . '" AND `attendances_pegawai`.`dinas_id` = ' . $id . ' GROUP BY pegawai_id'
            );
        } else {
            $results = DB::select(
                'SELECT *,dinas.name as dinas,pegawai.nip as nip,  pegawai.name as nama, sum(potongan_absen_masuk) as potongan_absen_masuk, sum(potongan_absen_pulang) as potongan_absen_pulang, sum(potongan_tidak_masuk_kerja) as potongan_tidak_masuk_kerja, sum(potongan_cuti) as potongan_cuti , sum(potongan_tidak_apel) as potongan_tidak_apel, sum(total_potongan_tpp) as total_potongan_tpp , sum(tpp_diterima) as tpp_diterima 
                FROM `attendances_pegawai`
                LEFT JOIN `pegawai` ON `pegawai`.`id` = `attendances_pegawai`.`pegawai_id`
                LEFT JOIN `dinas` ON `dinas`.`id` = `attendances_pegawai`.`dinas_id`
                WHERE
                `attendances_pegawai`.`date_attendance` >= "' . $start . '" AND `attendances_pegawai`.`date_attendance` <= "' . $end . '" GROUP BY pegawai_id'

            );
        }

        return $results;
    }

    public function updateTidakMasuk(Request $request)
    {
        // dd($request->id);
        DB::beginTransaction();
        try {
            $data = AttendancesPegawai::findOrFail($request->id);

            $data->update([
                'status' => 'Tidak Masuk',
                'incoming_time' => '00:00:00',
                'outgoing_time ' => '00:00:00',
                'menit_telat_masuk' => null,
                'total_potongan_tpp' => $data->tunjangan_per_hari,
                'potongan_absen_masuk' => 0,
                'potongan_absen_pulang' => 0,
                'potongan_tidak_masuk_kerja' => $data->tunjangan_per_hari,
                'potongan_tidak_apel' => 0,
                'status_masuk' => null,
                'status_pulang' => null,
                'status_apel' => null,
                'potongan_absen_masuk_persen' => 0,
                'potongan_absen_pulang_persen' => 0,
                'potongan_tidak_apel_persen' => 0,
                'potongan_tidak_masuk_kerja_persen' => 100,
                'ket_tidak_masuk_kerja' => 'Tanpa Keterangan',
                'potongan_cuti' => 0,
                'potongan_cuti_persen' => 0,
                'ket_cuti' => null,
                'config_potongan_tpp_id' => 14,
                'tpp_diterima' => 0,
                'anulir' => 1,
                'ket_anulir' => $request->keterangan

            ]);
            DB::commit();
            $response = response()->json($this->responseStore(true, "Data Berhasil Di Prosess", route('presensi-pegawai.index')));
        } catch (\Throwable $throw) {
            DB::rollBack();
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }

    public function export(Request $request)
    {


        $start = ($request->dari) ?: date('Y-m' . '-01');
        $end = ($request->hingga) ?: date('Y-m-t');
        $dinas = ($request->dinas) ?: null;

        $periode = " ";
        if ($request->dari) {
            $periode .= "PERIODE " . date('d/m/Y', strtotime($start));
        }
        if ($request->hingga) {
            $periode .= " s.d " . date('d/m/Y', strtotime($end));
        }

        if ($request->user()->role_id != 1) {
            $data = $this->initData($start, $end, $request->user()->dinas_id);
        } else {
            $data = $this->initDataRekap($start, $end, $dinas);
        }

        if ($dinas == null && $request->user()->role_id == 1) {
            $url =  $this->generatePrensensiAll($data, $periode, $request, $start, $end);
        } else if ($dinas == null && $request->user()->role_id == 2) {
            $url =  $this->generatePrensensiDinas($request->user()->dinas_id, $data, $periode, $request, $start, $end);
        } else {
            $url =  $this->generatePrensensiDinas($dinas, $data, $periode, $request, $start, $end);
        }
        $attachment = url('/storage/') . '/export_presensi/' . $url;

        return response()->json(['status' => 'success', 'message' => 'Data berhasil diexport', 'url' => $attachment]);
    }
    
     public function exportPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pegawai_id' => 'required',
            'dari' => 'required',
            'hingga' => 'required',
        ], [
            'dari.required' => 'Filter Tanggal harus terisi.',
            'hingga.required' => 'Filter Tanggal harus terisi.',
            'pegawai_id.required' => 'Pegawai Wajib dipilih.',
        ]);
        if ($validator->passes()) {
            try {

                $data['periode'] = "PERIODE " . date('d-m-Y', strtotime($request->dari)) . " s.d " . date('d-m-Y', strtotime($request->hingga));
                $data['pegawai'] = Pegawai::with('dinas')->find($request->pegawai_id);

                $presensi = AttendancesPegawai::where('attendances_pegawai.pegawai_id', $request->pegawai_id)->with(['pegawai', 'pegawai.dinas']);
                $presensi->whereDate('date_attendance', '>=', $request->dari);
                $presensi->whereDate('date_attendance', '<=', $request->hingga);

                $data['presensi'] = $presensi->orderBy('date_attendance', 'ASC')->get();
                $options['isHtml5ParserEnabled']= true;
                $options['isRemoteEnabled']= true;  // jika gambar diambil dari URL ekstern;
                // $options['debugPng']= false;

                $pdf = PDF::loadView('contents.presensi-pegawai.pdf', $data)->setPaper('a4', 'landscape');
                $pdf->setWarnings(true);

                $filePath = storage_path('app/public/temp_pdf/presensi.pdf');
                File::put($filePath, $pdf->output());
                $publicUrl = asset('storage/temp_pdf/presensi.pdf');
                return response()->json(['status' => 'success', 'message' => 'Data berhasil diexport', 'url' => $publicUrl]);
            } catch (\Throwable $throw) {
                $response = response()->json(['error' => $throw->getMessage(), 'message' => $throw->getMessage()]);
            }
        } else {
            $response = response()->json(['error' => $validator->errors()->all(), 'message' => $validator->errors()->all()]);
        }
        return $response;
    }

    private function generatePrensensiAll($data, $periode, $request, $start, $end)
    {
        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('Pesisirbaratkab')
            ->setLastModifiedBy('Pesisirbaratkab')
            ->setTitle('Office 2007 XLSX Report Document')
            ->setSubject('Office 2007 XLSX Report Document')
            ->setDescription('Report generator')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Report File');

        $spreadsheet->getActiveSheet()->setTitle("REKAP");

        $spreadsheet->setActiveSheetIndex(0)->mergeCells("A1:K1");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', 'LAPORAN PRESENSI PEGAWAI');
        // $spreadsheet->getActiveSheet()->getRowDimension('2')->setRowHeight(26);

        $spreadsheet->setActiveSheetIndex(0)->mergeCells("A2:K2");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A2', $periode);

        $spreadsheet->getActiveSheet()->getStyle('A1:K2')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:K2')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A3', 'NO')
            ->setCellValue('B3', 'NIP')
            ->setCellValue('C3', 'NAMA')
            ->setCellValue('D3', 'DINAS')
            ->setCellValue('E3', 'PEMOTONGAN KARENA TERLAMBAT')
            ->setCellValue('F3', 'PEMOTONGAN KARENA TIDAK PRESENSI SORE')
            ->setCellValue('G3', 'PEMOTONGAN KARENA TIDAK MASUK KERJA')
            ->setCellValue('H3', 'CUTI')
            ->setCellValue('I3', 'PEMOTONGAN KARENA TIDAK APEL')
            ->setCellValue('J3', 'POTONGAN TPP')
            ->setCellValue('K3', 'TPP YANG DITERIMA');

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getStyle('A3:K3')->applyFromArray($styleArray);

        $spreadsheet->getActiveSheet()->getStyle('A3:K3')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A3:K3')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('K')->setWidth(23);
        $sheet->getColumnDimension('N')->setWidth(31);
        foreach (range('A', 'J') as $columnID) {
            if ($columnID != 'K' && $columnID != 'N') {
                $sheet->getColumnDimension($columnID)
                    ->setAutoSize(true);
            }
        }

        $col_start = 5;
        $count = 0;
        $i = 3;
        foreach ($data as $val) {
            $i++;
            $count++;
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $count)
                ->setCellValue('B' . $i, "'" . $val->nip)
                ->setCellValue('C' . $i, $val->nama)
                ->setCellValue('D' . $i, $val->dinas)
                ->setCellValue('E' . $i, $val->potongan_absen_masuk)
                ->setCellValue('F' . $i, $val->potongan_absen_pulang)
                ->setCellValue('G' . $i, $val->potongan_tidak_masuk_kerja)
                ->setCellValue('H' . $i, $val->potongan_cuti)
                ->setCellValue('I' . $i, $val->potongan_tidak_apel)
                ->setCellValue('J' . $i, $val->total_potongan_tpp)
                ->setCellValue('K' . $i, $val->tpp_diterima);
        }
        $i += 2;

        $sheet->getStyle('A4:K' . $i)->applyFromArray($styleArray);

        $sum_range_pot_terlambat = 'E4:E' . $i - 1;
        $sum_range_pot_psw = 'F4:F' . $i - 1;
        $sum_range_pot_tidak_masuk_kerja = 'G4:G' . $i - 1;
        $sum_range_pot_cuti = 'H4:H' . $i - 1;
        $sum_range_pot_tidak_apel = 'I4:I' . $i - 1;
        $sum_range_pot_per_hari = 'J4:J' . $i - 1;
        $sum_range_tpp_diterima = 'K4:K' . $i - 1;


        $spreadsheet->getActiveSheet()->getStyle('E4:E' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('F4:F' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('G4:G' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('H4:H' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('I4:I' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('J4:J' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('K4:K' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A' . $i, "TOTAL")
            ->setCellValue('E' . $i, "=SUM($sum_range_pot_terlambat)")
            ->setCellValue('F' . $i, "=SUM($sum_range_pot_psw)")
            ->setCellValue('G' . $i, "=SUM($sum_range_pot_tidak_masuk_kerja)")
            ->setCellValue('H' . $i, "=SUM($sum_range_pot_cuti)")
            ->setCellValue('I' . $i, "=SUM($sum_range_pot_tidak_apel)")
            ->setCellValue('J' . $i, "=SUM($sum_range_pot_per_hari)")
            ->setCellValue('K' . $i, "=SUM($sum_range_tpp_diterima)");

        $sheet->mergeCells('A' . $i . ':D' . $i);

        $spreadsheet->getActiveSheet()->getStyle('A' . $i . ':C' . $i)
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A' . $i . ':C' . $i)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->insertNewRowBefore(3);

        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $protection = $spreadsheet->getActiveSheet()->getProtection();
        $protection->setPassword("ABCDEFGHIJKLMNOPQRSTUVWX");
        $protection->setSheet(true);

        foreach ($data as $val) {
            //create tab sheet
            $data_pegawai = $this->initData($start, $end, $val->dinas_id, $val->pegawai_id);
            $this->addSheet($spreadsheet, $val->nama . ' ' . $val->pegawai_id, $data_pegawai, $periode);
        }

        $filename = 'laporan-presensi-pegawai_' . $request->user()->id . '_' . $start . '-' . $end . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="' . $start . '-' . $end . '.xlsx"');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        // $url = null;
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        // $    writer->save('php://output');
        // $filename = 'laporan-presensi-pegawai_' . $start . '-' . $end . '.xlsx';

        $writer->save(storage_path('app/public/export_presensi/' . $filename));
        return $filename;
        // exit;
        // return response()->json(['status' => 'success', 'message' => 'Data berhasil diexport', 'url' => $url]);
    }

    private function generatePrensensiDinas($dinas_id, $data, $periode, $request, $start, $end)
    {
        $dinas = Dinas::where('id', $dinas_id)->first();

        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('Pesisirbaratkab')
            ->setLastModifiedBy('Pesisirbaratkab')
            ->setTitle('Office 2007 XLSX Report Document')
            ->setSubject('Office 2007 XLSX Report Document')
            ->setDescription('Report generator')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Report File');

        $spreadsheet->setActiveSheetIndex(0)->mergeCells("A1:J1");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', 'LAPORAN PRESENSI PEGAWAI DINAS ' . $dinas->name);

        $spreadsheet->setActiveSheetIndex(0)->mergeCells("A2:J2");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A2', $periode);
        $spreadsheet->getActiveSheet()->setTitle("REKAP");
        $spreadsheet->getActiveSheet()->getStyle('A1:J2')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:J2')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A3', 'NO')
            ->setCellValue('B3', 'NIP')
            ->setCellValue('C3', 'NAMA')
            ->setCellValue('D3', 'PEMOTONGAN KARENA TERLAMBAT')
            ->setCellValue('E3', 'PEMOTONGAN KARENA TIDAK PRESENSI SORE')
            ->setCellValue('F3', 'PEMOTONGAN KARENA TIDAK MASUK KERJA')
            ->setCellValue('G3', 'CUTI')
            ->setCellValue('H3', 'PEMOTONGAN KARENA TIDAK APEL')
            ->setCellValue('I3', 'POTONGAN TPP')
            ->setCellValue('J3', 'TPP YANG DITERIMA');

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getStyle('A3:J3')->applyFromArray($styleArray);

        $spreadsheet->getActiveSheet()->getStyle('A3:J3')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A3:J3')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('K')->setWidth(23);
        $sheet->getColumnDimension('N')->setWidth(31);
        foreach (range('A', 'J') as $columnID) {
            if ($columnID != 'K' && $columnID != 'N') {
                $sheet->getColumnDimension($columnID)
                    ->setAutoSize(true);
            }
        }

        $data = $this->initDataRekap($start, $end, $dinas_id);

        $col_start = 5;
        $count = 0;
        $i = 3;
        foreach ($data as $val) {
            $i++;
            $count++;
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $count)
                ->setCellValue('B' . $i, "'" . $val->nip)
                ->setCellValue('C' . $i, $val->nama)
                ->setCellValue('D' . $i, $val->potongan_absen_masuk)
                ->setCellValue('E' . $i, $val->potongan_absen_pulang)
                ->setCellValue('F' . $i, $val->potongan_tidak_masuk_kerja)
                ->setCellValue('G' . $i, $val->potongan_cuti)
                ->setCellValue('H' . $i, $val->potongan_tidak_apel)
                ->setCellValue('I' . $i, $val->total_potongan_tpp)
                ->setCellValue('J' . $i, $val->tpp_diterima);
        }
        $i += 2;

        $sheet->getStyle('A4:J' . $i)->applyFromArray($styleArray);

        $sum_range_pot_terlambat = 'D4:D' . $i - 1;
        $sum_range_pot_psw = 'E4:E' . $i - 1;
        $sum_range_pot_tidak_masuk_kerja = 'F4:F' . $i - 1;
        $sum_range_pot_cuti = 'G4:G' . $i - 1;
        $sum_range_pot_tidak_apel = 'H4:H' . $i - 1;
        $sum_range_pot_per_hari = 'I4:I' . $i - 1;
        $sum_range_tpp_diterima = 'J4:J' . $i - 1;


        $spreadsheet->getActiveSheet()->getStyle('D4:D' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('E4:E' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('F4:F' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('G4:G' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('H4:H' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('I4:I' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('J4:J' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A' . $i, "TOTAL")
            ->setCellValue('D' . $i, "=SUM($sum_range_pot_terlambat)")
            ->setCellValue('E' . $i, "=SUM($sum_range_pot_psw)")
            ->setCellValue('F' . $i, "=SUM($sum_range_pot_tidak_masuk_kerja)")
            ->setCellValue('G' . $i, "=SUM($sum_range_pot_cuti)")
            ->setCellValue('H' . $i, "=SUM($sum_range_pot_tidak_apel)")
            ->setCellValue('I' . $i, "=SUM($sum_range_pot_per_hari)")
            ->setCellValue('J' . $i, "=SUM($sum_range_tpp_diterima)");

        $sheet->mergeCells('A' . $i . ':C' . $i);

        $spreadsheet->getActiveSheet()->getStyle('A' . $i . ':C' . $i)
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A' . $i . ':C' . $i)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->insertNewRowBefore(3);

        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $protection = $spreadsheet->getActiveSheet()->getProtection();
        $protection->setPassword("ABCDEFGHIJKLMNOPQRSTUVWX");
        $protection->setSheet(true);

        for ($i = 0; $i < sizeof($data); $i++) {
            //create tab sheet
            $data_pegawai = $this->initData($start, $end, $data[$i]->dinas_id, $data[$i]->pegawai_id);
            $this->addSheet($spreadsheet, $data[$i]->nama . ' ' . $data[$i]->pegawai_id, $data_pegawai, $periode);
        }
        // foreach ($data as $val) {
        //     //create tab sheet
        //     $data_pegawai = $this->initData($start, $end, $val->dinas_id, $val->pegawai_id);
        //     $this->addSheet($spreadsheet, $val->nama, $data_pegawai, $periode);
        // }
        $filename = 'LAPORAN_PRESENSI_PEGAWAI_DINAS_' . $request->user()->id . '_' . $start . '-' . $end . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="' . $start . '-' . $end . '.xlsx"');
        // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="LAPORAN_PRESENSI_PEGAWAI_DINAS_' . $dinas->name . '_' . $start . '-' . $end . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        // $    writer->save('php://output');
        $writer->save(storage_path('app/public/export_presensi/' . $filename));
        return $filename;
    }

    private function addSheet($spreadsheet, $title, $data, $periode)
    {

        $title = substr($title, 0, 30);

        $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $title);
        $spreadsheet->addSheet($myWorkSheet);

        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );

        $sheetIndex = $spreadsheet->getIndex(
            $spreadsheet->getSheetByName($title)
        );

        $spreadsheet->setActiveSheetIndex($sheetIndex)->mergeCells("A1:S1");
        $spreadsheet->setActiveSheetIndex($sheetIndex)->setCellValue('A1', 'LAPORAN PRESENSI PEGAWAI');
        $spreadsheet->setActiveSheetIndex($sheetIndex)->mergeCells("A2:S2");
        $spreadsheet->setActiveSheetIndex($sheetIndex)->setCellValue('A2', $periode);

        $spreadsheet->getActiveSheet()->getStyle('A1:S2')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:S2')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex($sheetIndex)
            ->setCellValue('A4', 'NO')
            ->setCellValue('B4', 'TANGGAL')
            ->setCellValue('C4', 'TUNJANGAN/HARI')
            ->setCellValue('D4', 'DINAS')
            ->setCellValue('E4', 'NIP')
            ->setCellValue('F4', 'NAMA')
            ->setCellValue('G4', 'JAM MASUK')
            ->setCellValue('H4', 'JAM PULANG')
            ->setCellValue('I4', 'STATUS')
            ->setCellValue('J4', 'PEMOTONGAN KARENA TERLAMBAT')
            ->setCellValue('J5', '%')
            ->setCellValue('K5', 'KETERANGAN')
            ->setCellValue('L4', 'PEMOTONGAN KARENA TIDAK PRESENSI SORE')
            ->setCellValue('L5', '%')
            ->setCellValue('M4', 'PEMOTONGAN KARENA TIDAK MASUK KERJA')
            ->setCellValue('M5', '%')
            ->setCellValue('N5', 'KETERANGAN')
            ->setCellValue('O4', 'CUTI')
            ->setCellValue('O5', '%')
            ->setCellValue('P5', 'KETERANGAN')
            ->setCellValue('Q4', 'PEMOTONGAN KARENA TIDAK APEL')
            ->setCellValue('R4', 'POTONGAN /HARI')
            ->setCellValue('S4', 'TPP YANG DITERIMA');

        $sheet = $spreadsheet->setActiveSheetIndex($sheetIndex);

        $sheet->getStyle('A4:S5')->applyFromArray($styleArray);

        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('C4:C5');
        $sheet->mergeCells('D4:D5');
        $sheet->mergeCells('E4:E5');
        $sheet->mergeCells('F4:F5');
        $sheet->mergeCells('G4:G5');
        $sheet->mergeCells('H4:H5');
        $sheet->mergeCells('I4:I5');
        $sheet->mergeCells('J4:K4');
        $sheet->mergeCells('M4:N4');
        $sheet->mergeCells('O4:P4');
        $sheet->mergeCells('Q4:Q5');
        $sheet->mergeCells('R4:R5');
        $sheet->mergeCells('S4:S5');

        $spreadsheet->getActiveSheet()->getStyle('A4:S5')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A4:S5')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('K')->setWidth(23);
        $sheet->getColumnDimension('N')->setWidth(31);
        foreach (range('A', 'S') as $columnID) {
            if ($columnID != 'K' && $columnID != 'N') {
                $sheet->getColumnDimension($columnID)
                    ->setAutoSize(true);
            }
        }

        $col_start = 5;
        $count = 0;
        $i = 5;

        foreach ($data as $val) {

            $i++;
            $count++;
            $spreadsheet->setActiveSheetIndex($sheetIndex)
                ->setCellValue('A' . $i, $count)
                ->setCellValue('B' . $i, $val->date_attendance)
                ->setCellValue('C' . $i, $val->tunjangan_per_hari)
                ->setCellValue('D' . $i, $val->dinas)
                ->setCellValue('E' . $i, "'" . $val->nip)
                ->setCellValue('F' . $i, $val->nama)
                ->setCellValue('G' . $i, $val->incoming_time)
                ->setCellValue('H' . $i, $val->outgoing_time)
                ->setCellValue('I' . $i, $val->status)
                ->setCellValue('J' . $i, $val->potongan_absen_masuk)
                ->setCellValue('K' . $i, $val->status_masuk)
                ->setCellValue('L' . $i, $val->potongan_absen_pulang)
                ->setCellValue('M' . $i, $val->potongan_tidak_masuk_kerja)
                ->setCellValue('N' . $i, $val->ket_tidak_masuk_kerja)
                ->setCellValue('O' . $i, $val->potongan_cuti)
                ->setCellValue('P' . $i, $val->ket_cuti)
                ->setCellValue('Q' . $i, $val->potongan_tidak_apel)
                ->setCellValue('R' . $i, $val->total_potongan_tpp)
                ->setCellValue('S' . $i, $val->tpp_diterima);
        }
        $i += 2;

        $sheet->getStyle('A6:S' . $i)->applyFromArray($styleArray);

        $sum_range_tpp_perhari = 'C6:C' . $i - 1;
        $sum_range_pot_terlambat = 'J6:J' . $i - 1;
        $sum_range_pot_psw = 'L6:L' . $i - 1;
        $sum_range_pot_tidak_masuk_kerja = 'M6:M' . $i - 1;
        $sum_range_pot_cuti = 'O6:O' . $i - 1;
        $sum_range_pot_tidak_apel = 'Q6:Q' . $i - 1;
        $sum_range_pot_per_hari = 'R6:R' . $i - 1;
        $sum_range_tpp_diterima = 'S6:S' . $i - 1;

        $spreadsheet->getActiveSheet()->getStyle('C6:C' . $i)->getNumberFormat()
            ->setFormatCode(
                '#,##0'
            );
        $spreadsheet->getActiveSheet()->getStyle('J6:J' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('L6:L' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('M6:M' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('O6:O' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('Q6:Q' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('R6:R' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('S6:S' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');

        if (sizeof($data) > 0) {
            $spreadsheet->setActiveSheetIndex($sheetIndex)
                ->setCellValue('A' . $i, "TOTAL")
                ->setCellValue('C' . $i, "=SUM($sum_range_tpp_perhari)")
                ->setCellValue('J' . $i, "=SUM($sum_range_pot_terlambat)")
                ->setCellValue('L' . $i, "=SUM($sum_range_pot_psw)")
                ->setCellValue('M' . $i, "=SUM($sum_range_pot_tidak_masuk_kerja)")
                ->setCellValue('O' . $i, "=SUM($sum_range_pot_cuti)")
                ->setCellValue('Q' . $i, "=SUM($sum_range_pot_tidak_apel)")
                ->setCellValue('R' . $i, "=SUM($sum_range_pot_per_hari)")
                ->setCellValue('S' . $i, "=SUM($sum_range_tpp_diterima)");
        } else {
            $spreadsheet->setActiveSheetIndex($sheetIndex)->setCellValue('A' . $i, "TOTAL");
        }

        // $spreadsheet->getActiveSheet()->insertNewRowBefore(3);



        // dd($spreadsheet->getActiveSheet());
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $protection = $spreadsheet->getActiveSheet()->getProtection();
        $protection->setPassword("ABCDEFGHIJKLMNOPQRSTUVWX");
        $protection->setSheet(true);
    }
}
