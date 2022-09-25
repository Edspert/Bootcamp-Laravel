<?php

namespace App\Http\Controllers;

use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditController extends Controller
{

    private $transactionService;

    public function __construct(
        TransactionService $transactionService
    ) {
        $this->transactionService = $transactionService;
    }

    public function XenditCallback()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = file_get_contents("php://input");
            Log::info('===CALLBACK XENDIT INVOICE VA, ALFA===');
            Log::info($data);
            $data = json_decode($data);
            $result = $this->transactionService->XenditCallback($data);

            if ($result == true) {
                return response()->json('Pembelian Bootcamp berhasil', 200);
            } else {
                return response()->json('Internal Server Error', 500);
            }
        } else {
            abort(404);
        }
    }

    public function xenditCallbackEwallet()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = file_get_contents("php://input");
            Log::info('=== Callback Ewallet ===');
            Log::error($data);
            Log::info('=== ================ ===');
            $data = json_decode($data);
            $result = $this->transactionService->xenditCallbackEwallet($data);

            if ($result == true) {
                return response()->json('Pembelian Bootcamp berhasil', 200);
            } else {
                return response()->json('Internal Server Error', 500);
            }
        } else {
            abort(404);
        }
    }
}
