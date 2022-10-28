<?php

namespace App\Services;

use App\Models\MemberTransaction;
use App\Repositories\BootcampRepository;
use App\Repositories\TransactionRepository;

class BootcampService
{
    private $bootcampRepository;
    private $transactionRepository;

    public function __construct(
        BootcampRepository $bootcampRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->bootcampRepository = $bootcampRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function list()
    {
        return $this->bootcampRepository->list();
    }

    public function detail($memberID, $bootcampID)
    {
        $memberTransaction = $this->transactionRepository->memberTransaction($memberID, $bootcampID);

        if ($memberTransaction && $memberTransaction->status != MemberTransaction::PAYMENT_STATUS_EXPIRED) {
            return [
                'checkkout' => true,
                'memberTransaction' => $memberTransaction
            ];
        }
        $bootcamp = $this->bootcampRepository->detail($bootcampID);

        $bootcamp->ppn = 0.11 * $bootcamp->price;
        $bootcamp->total = $bootcamp->ppn + $bootcamp->price;
        return [
            'checkkout' => false,
            'bootcamp' => $bootcamp
        ];
    }
}
