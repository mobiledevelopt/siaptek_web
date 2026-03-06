<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFcmTokenRequest;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Request;

class FcmController extends Controller
{
    /**
     * Store FCM token for logged-in user.
     */
    public function store(StoreFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user(); // Ambil user dari Bearer token

        // Simpan token ke database
        // Misal user punya kolom fcm_token di tabel users
        $user->fcm_token = $request->token;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token saved successfully',
        ]);
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

    public function sendNotification(Request $request)
    {

        $data = [
            "message" => [
                "token" => "eX0Dh413S3GImg2pRhveWo:APA91bHAuEzxYljeUygA9ihNZpfyeAzdY3YTGZrbHRGI0TZbIYlYW0mjB8vF-76MBxpwYnQ51X60in5ePRuDRGMVq1lmbNaonQLyn17kNngPJNsUnnb-QMM",
                "data" => [
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                    "title" => "Waktunya Presensi Masuk",
                    "body" => "Jangan sampai terlewat",
                    "type" => "presensi"
                ],
                "android" => [
                    "priority" => "high",
                ],

            ]
        ];
        $projectId = "absen-dinas-pesibar";

        $accessToken = $this->getAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        return $response->json();
    }
}
