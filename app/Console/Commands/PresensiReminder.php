<?php

namespace App\Console\Commands;

use App\Models\JadwalApel;
use App\Models\JamAbsen;
use App\Models\KalendarLibur;
use App\Models\Pegawai;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PresensiReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:presensi-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder notifikasi presensi pegawai';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // skip sabtu minggu
        if (in_array(date('w'), [0, 6])) {
            return;
        }

        // cek hari libur
        if (KalendarLibur::where('tgl', date('Y-m-d'))->exists()) {
            return;
        }
        
        $topicNow = "hadir_" . date('Y_m_d');
        
        // hari senin-kamis
        if (date('w') >= 1 && date('w') <= 4) {

            // jadwal presensi masuk, apel pagi dan pulang
            $jadwalPresensi = JamAbsen::find(1);
            $jadwalApel = JadwalApel::where('hari', date('w'))->first();

            $this->checkAndSendTopik($jadwalPresensi->jam_masuk, 'presensi', 'Waktunya Presensi Masuk', 'Jangan sampai terlewat', true, 'reminder_presensi');
            $this->checkAndSendTopik($jadwalApel->jam_apel_pagi, 'apel', 'Waktunya Apel Pagi', 'Jangan sampai terlewat', true, $topicNow);
            $this->checkAndSendTopik($jadwalPresensi->jam_pulang, 'presensi', 'Waktunya Presensi Pulang', 'Jangan sampai terlewat', true, $topicNow);

        } else {

            // jadwal presensi masuk, apel pagi, apel sore dan pulang
            $jadwalPresensi = JamAbsen::find(2);
            $jadwalApel = JadwalApel::where('hari', date('w'))->first();

            $this->checkAndSendTopik($jadwalPresensi->jam_masuk, 'presensi', 'Waktunya Presensi Masuk', 'Jangan sampai terlewat', true, 'reminder_presensi','presensi_masuk');
            $this->checkAndSendTopik($jadwalApel->jam_apel_pagi, 'apel', 'Waktunya Apel Pagi', 'Jangan sampai terlewat', true, $topicNow, 'apel_pagi');
            $this->checkAndSendTopik($jadwalApel->jam_apel_sore, 'apel', 'Waktunya Apel Sore', 'Jangan sampai terlewat', true, $topicNow, 'apel_sore');
            $this->checkAndSendTopik($jadwalPresensi->jam_pulang, 'presensi', 'Waktunya Presensi Pulang', 'Jangan sampai terlewat', true, $topicNow, 'presensi_pulang');

        }
    }

    private function checkAndSend($time, $type, $message)
    {
        if(now()->format('H:i') == $time){

            $cacheKey = "notif_presensi_".$type."_".date('Y-m-d');

            // hindari spam
            if(Cache::has($cacheKey)){
                return;
            }

            Cache::put($cacheKey, true, 60);

            $this->sendNotif($type, $message);
        }
    }

    private function checkAndSendTopik($time, $type, $title, $message, $topik, $topikName, $logPrefix = '')
    {
       
        $now = now();
        $jadwal = \Carbon\Carbon::parse($time);

        echo "Checking $type at $time, now is ".$now->format('H:i')."\n";
        if ($now->between($jadwal, $jadwal->copy()->addMinute())) {

            $cacheKey = "notif_presensi_".$logPrefix."_".date('Y-m-d');

            // hindari spam
            // if(Cache::has($cacheKey)){
            //     return;
            // }

            // Cache::put($cacheKey, true, 60);

            // $this->sendNotif($type, $message);

            $this->sendFCMNotification(
                null,
                $title,
                $message,
                $type,
                $topik,
                $topikName
            );
        }
    }
    
    public function sendNotif($type, $message, $topik = false)
    {

        $users = Pegawai::where('active',1)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token');

        foreach ($users as $token) {

            // $this->sendFCMNotification(
            //     $token,
            //     "Pengingat Presensi",
            //     $message,
            //     $type,
            //     $topik,
            //     $topikName
            // );
        }
    }

    public function sendNotifTopik($type, $title, $message, $topik, $topikName)
    {
        $this->sendFCMTopicNotification(
            $type,
            $title,
            $message,
            $type,
            $topik,
            $topikName
        );
    }

    private function sendFCMNotification($token, $title, $message, $type, $topik, $topikName)
    {

        try {

            $projectId = "absen-dinas-pesibar";

            $data = [
                "message" => [
                    // $topik ? "topic" : "token" => $topik ? $topikName : $token,
                    "topic" => $topikName,
                    // "notification" => [
                    //     "title" => $title,
                    //     "body" => $message
                    // ],

                    "data" => [
                        "type" => $type,
                        "title" => $title,
                        "body" => $message,
                    ],

                    "android" => [
                        "priority" => "high"
                    ]
                ]
            ];

            $accessToken = $this->getAccessToken();

            $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $data);
                echo "FCM Response: ".$response->body();
            Log::info("FCM Response: ".$response->body());

        } catch (\Exception $e) {

            Log::error("FCM Error: ".$e->getMessage());

        }

    }

     function getAccessToken()
    {
        $credentials = new ServiceAccountCredentials(
            "https://www.googleapis.com/auth/firebase.messaging",
            storage_path('firebase/firebase-service-account.json')
        );

        $token = $credentials->fetchAuthToken();

        return $token['access_token'];
    }
    
}
