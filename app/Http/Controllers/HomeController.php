<?php

namespace App\Http\Controllers;

use App\Models\Bootcamp;
use App\Models\MemberTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $bootcamps = Bootcamp::orderBy('id', 'desc')->get();
        return view('list', ['bootcamps' => $bootcamps]);
    }

    public function checkout($bootcampID)
    {

        $memberTransaction = MemberTransaction::with('xendit')->where([
            'member_id' => Auth::id(),
            'bootcamp_id' => $bootcampID
        ])
            ->first();

        $checkkout = false;
        if ($memberTransaction && $memberTransaction->status != MemberTransaction::PAYMENT_STATUS_EXPIRED) {
            $checkkout = true;
        }

        $bootcamp = Bootcamp::where('id', $bootcampID)->first();
        if (!$bootcamp) {
            return redirect()->back('danger', "Data tidak ditemukan");
        }


        $bootcamp->ppn = 0.11 * $bootcamp->price;
        $bootcamp->total = $bootcamp->ppn + $bootcamp->price;


        if ($checkkout == true) {

            return to_route('detail', $bootcamp->transaction_id);
        }
        return view('checkout', ['bootcamp' => $bootcamp]);
    }

    public function actCheckout(Request $request, $bootcampID)
    {
        $memberTransaction = MemberTransaction::where([
            'member_id' => Auth::id(),
            'bootcamp_id' => $bootcampID
        ])
            ->first();

        if ($memberTransaction == MemberTransaction::PAYMENT_STATUS_PENDING) {
            return to_route('detail', $memberTransaction->transaction_id);
        }

        if ($memberTransaction == MemberTransaction::PAYMENT_STATUS_ACCEPT) {
            return to_route('bootcamps')->with('danger', 'Kamu sudah membeli bootcamp ini');
        }

        // validate bootcamp
        $bootcamp = $this->bootcampRepository->detail($bootcampID);
        if (!$bootcamp) {
            return redirect()->back()->with('danger', 'Bootcamp tidak ditemukan');
        }

        $finalPrice = (0.11 * $bootcamp->price) + $bootcamp->price;

        $transactionExp = date('Y-m-d H:i:s', strtotime("1 days"));

        $bootcamp->transaction_id = Str::uuid();
        $bootcamp->member_id = Auth::id();
        $bootcamp->bootcamp_id = $bootcampID;
        $bootcamp->final_price = $finalPrice;
        $bootcamp->transaction_exp = $transactionExp;

        try {
            DB::beginTransaction();

            $transaction = new MemberTransaction();
            $transaction->transaction_id = Str::uuid();
            $transaction->member_id = Auth::id();
            $transaction->bootcamp_id = $bootcampID;
            $transaction->price = $bootcamp->price;
            $transaction->final_price = $finalPrice;
            $transaction->status = MemberTransaction::PAYMENT_STATUS_PENDING;
            $transaction->save();

            DB::commit();
            return to_route('detail', $memberTransaction->transaction_id);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('transaction');
            Log::error($th);
            return to_route('bootcamps');
        }
    }

    public function detail($bootcampTransactionID)
    {
        $memberTransaction = $this->transactionService->detail(Auth::id(), $bootcampTransactionID);

        if (!$memberTransaction) {
            return to_route('bootcamps');
        }
        return view('detail', ['memberTransaction' => $memberTransaction]);
    }
}
