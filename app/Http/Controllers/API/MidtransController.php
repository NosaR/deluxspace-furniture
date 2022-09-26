<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function callback()
    {
        // SET KONFIGURASI MIDTRANS
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // BUAT INSTANCE MIDTRANS NOTIFICATION
        $notification = new Notification();

        // ASSIGN KE VARIABLE UNTUK MEMUDAHKAN CODING
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // GET TRANSCTION ID
        $order = explode('-', $order_id);

        // CARI TRANSAKSI BERDASARKAN ID
        $transaction = Transaction::findOrFail($order[1]);

        // HANDLE NOTIFICATION STATUS MIDTRANS
        if ($status == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $transaction->staus = 'PENDING';
                }

                else {
                    $transaction->staus = 'SUCCESS';
                }
            }
        }

        else if ($status == 'settlement') {
            $transaction->staus = 'SUCCESS';
        }

        else if ($status == 'pending') {
            $transaction->staus = 'PENDING';
        }

        else if ($status == 'deny') {
            $transaction->staus = 'PENDING';
        }

        else if ($status == 'expire') {
            $transaction->staus = 'CANCELLED';
        }

        else if ($status == 'cancel') {
            $transaction->staus = 'CANCELLED';
        }

        // SIMPAN TRANSAKSI
        $transaction->save();

        // RETURN RESPONSE UNTUK MIDTRANS
        return response()->json([
            'meta' => [
                'code' => 200,
                'message' => 'Midtrans Notification Success!'
            ]
        ]);
    }
}
