<?php

namespace App\Jobs;

use App\Events\SendGlobalNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\CauserResolver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PresensiExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;


    public function __construct($data, $periode, $start, $end)
    {
        $this->data = $data;
        $this->periode = $periode;
        $this->start = $start;
        $this->end = $end;
    }

    public $data;
    public $periode;
    public $start;
    public $end;

    public function handle()
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

        $spreadsheet->setActiveSheetIndex(0)->mergeCells("A2:K2");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A2', $this->periode);

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
        foreach ($this->data as $val) {
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

        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $protection = $spreadsheet->getActiveSheet()->getProtection();
        $protection->setPassword("ABCDEFGHIJKLMNOPQRSTUVWX");
        $protection->setSheet(true);

        foreach ($this->data as $val) {
            //create tab sheet
            $data_pegawai = $this->initData($this->start, $this->end, $val->dinas_id, $val->pegawai_id);
            $this->addSheet($spreadsheet, $val->nama, $data_pegawai, $this->periode);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="laporan-presensi-pegawai_' . $this->start . '-' . $this->end . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        // $writer->save('php://output');

        $writer->save(storage_path('laporan-presensi-pegawai_' . $this->start . '-' . $this->end . '.xlsx'));
        event(new SendGlobalNotification(url('/storage/') . '/' . 'laporan-presensi-pegawai_' . $this->start . '-' . $this->end . '.xlsx'));
        // exit;
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

        return $data->get();
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
            ->setCellValue('A3', 'NO')
            ->setCellValue('B3', 'TANGGAL')
            ->setCellValue('C3', 'TUNJANGAN/HARI')
            ->setCellValue('D3', 'DINAS')
            ->setCellValue('E3', 'NIP')
            ->setCellValue('F3', 'NAMA')
            ->setCellValue('G3', 'JAM MASUK')
            ->setCellValue('H3', 'JAM PULANG')
            ->setCellValue('I3', 'STATUS')
            ->setCellValue('J3', 'PEMOTONGAN KARENA TERLAMBAT')
            ->setCellValue('J4', '%')
            ->setCellValue('K4', 'KETERANGAN')
            ->setCellValue('L3', 'PEMOTONGAN KARENA TIDAK PRESENSI SORE')
            ->setCellValue('L4', '%')
            ->setCellValue('M3', 'PEMOTONGAN KARENA TIDAK MASUK KERJA')
            ->setCellValue('M4', '%')
            ->setCellValue('N4', 'KETERANGAN')
            ->setCellValue('O3', 'CUTI')
            ->setCellValue('O4', '%')
            ->setCellValue('P4', 'KETERANGAN')
            ->setCellValue('Q3', 'PEMOTONGAN KARENA TIDAK APEL')
            ->setCellValue('R3', 'POTONGAN /HARI')
            ->setCellValue('S3', 'TPP YANG DITERIMA');

        $sheet = $spreadsheet->setActiveSheetIndex($sheetIndex);

        $sheet->getStyle('A3:S4')->applyFromArray($styleArray);

        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('D3:D4');
        $sheet->mergeCells('E3:E4');
        $sheet->mergeCells('F3:F4');
        $sheet->mergeCells('G3:G4');
        $sheet->mergeCells('H3:H4');
        $sheet->mergeCells('I3:I3');
        $sheet->mergeCells('J3:K3');
        $sheet->mergeCells('M3:N3');
        $sheet->mergeCells('O3:P3');
        $sheet->mergeCells('Q3:Q4');
        $sheet->mergeCells('R3:R4');
        $sheet->mergeCells('S3:S4');

        $spreadsheet->getActiveSheet()->getStyle('A3:S4')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A3:S4')
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
        $i = 4;
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

        $sheet->getStyle('A5:S' . $i)->applyFromArray($styleArray);

        $sum_range_tpp_perhari = 'C5:C' . $i - 1;
        $sum_range_pot_terlambat = 'J5:J' . $i - 1;
        $sum_range_pot_psw = 'L5:L' . $i - 1;
        $sum_range_pot_tidak_masuk_kerja = 'M5:M' . $i - 1;
        $sum_range_pot_cuti = 'O5:O' . $i - 1;
        $sum_range_pot_tidak_apel = 'Q5:Q' . $i - 1;
        $sum_range_pot_per_hari = 'R5:R' . $i - 1;
        $sum_range_tpp_diterima = 'S5:S' . $i - 1;

        $spreadsheet->getActiveSheet()->getStyle('C5:C' . $i)->getNumberFormat()
            ->setFormatCode(
                '#,##0'
            );
        $spreadsheet->getActiveSheet()->getStyle('J5:J' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('L5:L' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('M5:M' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('O5:O' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('Q5:Q' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('R5:R' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getStyle('S5:S' . $i)->getNumberFormat()
            ->setFormatCode('#,##0');

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

        // dd($spreadsheet->getActiveSheet());
        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $protection = $spreadsheet->getActiveSheet()->getProtection();
        $protection->setPassword("ABCDEFGHIJKLMNOPQRSTUVWX");
        $protection->setSheet(true);
    }
}
