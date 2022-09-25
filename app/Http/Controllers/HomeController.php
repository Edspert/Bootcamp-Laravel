<?php

namespace App\Http\Controllers;

use App\Models\Bootcamp;
use App\Services\BootcampService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $bootcampService;
    private $transactionService;
    public function __construct(
        BootcampService $bootcampService,
        TransactionService $transactionService
    ) {
        $this->bootcampService = $bootcampService;
        $this->transactionService = $transactionService;
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $bootcamps = $this->bootcampService->list();
        return view('list', ['bootcamps' => $bootcamps]);
    }

    public function checkout($bootcampID)
    {
        $data = $this->bootcampService->detail(Auth::id(), $bootcampID);
        if ($data['checkkout'] == true) {

            return to_route('detail', $data['memberTransaction']->transaction_id);
        }
        return view('checkout', ['bootcamp' => $data['bootcamp']]);
    }

    public function actCheckout(Request $request, $bootcampID)
    {

        return $this->transactionService->create(Auth::user(), $bootcampID, $request->all());
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
