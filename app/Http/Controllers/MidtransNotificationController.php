<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use App\Services\PembayaranService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook notifikasi Midtrans (HTTP Notification).
 *
 * Endpoint ini TIDAK memakai login dan tidak memakai CSRF (dikecualikan di
 * bootstrap/app.php), karena dipanggil server Midtrans.
 *
 * Catatan localhost: Midtrans tidak bisa menghubungi 127.0.0.1. Untuk menguji
 * webhook, jalankan tunnel publik (mis. ngrok) dan set MIDTRANS_NOTIFICATION_URL
 * atau URL notifikasi di dashboard Midtrans. Tanpa webhook, status tetap
 * tersinkron lewat polling di halaman pembayaran.
 */
class MidtransNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        MidtransService $midtrans,
        PembayaranService $pembayaran
    ): JsonResponse {
        $payload = $request->json()->all() ?: $request->all();

        if (! $midtrans->signatureSah($payload)) {
            Log::warning('Notifikasi Midtrans ditolak: signature tidak valid.', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $hasil = $pembayaran->dariNotifikasi($payload);

        return response()->json(
            ['message' => $hasil['pesan'] ?? $hasil['status']],
            $hasil['status'] === 'ditolak' ? 400 : 200
        );
    }
}
