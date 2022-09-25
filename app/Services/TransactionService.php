<?php

namespace App\Services;

use App\Models\MemberTransaction;
use App\Repositories\BootcampRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Xendit\Xendit;

class TransactionService
{
    private $bootcampRepository;
    private $transactionRepository;

    public function __construct(
        BootcampRepository $bootcampRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->bootcampRepository = $bootcampRepository;
        $this->transactionRepository = $transactionRepository;

        Xendit::setApiKey(env('XENDIT_API_KEY'));
    }

    public function create($member, $bootcampID, $data)
    {

        // check status if member already checkout this bootcamp
        $memberStatus = $this->transactionRepository->memberTransaction($member->id, $bootcampID);
        if ($memberStatus == MemberTransaction::PAYMENT_STATUS_PENDING) {
            return to_route('detail', $memberStatus->transaction_id);
        }

        if ($memberStatus == MemberTransaction::PAYMENT_STATUS_ACCEPT) {
            return to_route('bootcamps')->with('danger', 'Kamu sudah membeli bootcamp ini');
        }

        // validate bootcamp
        $bootcamp = $this->bootcampRepository->detail($bootcampID);
        if (!$bootcamp) {
            return redirect()->back()->with('danger', 'Bootcamp tidak ditemukan');
        }

        $finalPrice = (0.11 * $bootcamp->price) + $bootcamp->price;

        $transactionExp = date('Y-m-d H:i:s', strtotime("1 days"));

        $bootcamp->payment_channel = $data['payment_channel'];
        $bootcamp->transaction_id = Str::uuid();
        $bootcamp->member_id = $member->id;
        $bootcamp->bootcamp_id = $bootcampID;
        $bootcamp->final_price = $finalPrice;
        $bootcamp->transaction_exp = $transactionExp;

        try {
            DB::beginTransaction();
            $memberTransaction = $this->transactionRepository->create($bootcamp);

            // create invoice xendit
            if ($bootcamp->payment_channel == MemberTransaction::PAYMENT_CHANNEL_BANK) {

                $desc = 'Checkout Bootcamp' . ucfirst($bootcamp->title);
                $description = preg_replace('/\s+/', ' ', $desc);
                $params = [
                    'external_id' => $memberTransaction->transaction_id, //string
                    'payer_email' => $member->email,
                    'description' => $description,
                    'amount' => $memberTransaction->final_price,
                    'should_send_email' => false,
                    // 'invoice_duration' => 60,
                    'success_redirect_url' => env('APP_URL') . '/bootcamp/transaction/' . $memberTransaction->transaction_id,
                ];

                $createInvoice = \Xendit\Invoice::create($params);
                Log::info('===PARAMS CREATE INVOICE CHECKOUT BANK===');
                Log::info($params);
                $this->transactionRepository->paymentChannelBank($memberTransaction, $createInvoice);
                $memberTransaction->transaction_exp = date('Y-m-d H:i:s', strtotime("1 days"));
            }

            if ($bootcamp->payment_channel == MemberTransaction::PAYMENT_CHANNEL_OVO) {
                $phone = $data['phone'];
                $memberTransaction->transaction_exp = date('Y-m-d H:i:s', strtotime("+55 seconds"));
                $channelCode = 'ID_OVO';
                $this->paymentEwallet($memberTransaction, $phone, $channelCode);
            }

            if ($bootcamp->payment_channel == MemberTransaction::PAYMENT_CHANNEL_DANA) {
                $phone = $data['phone'];
                $memberTransaction->transaction_exp = date('Y-m-d H:i:s', strtotime("+30 minutes"));
                $channelCode = 'ID_DANA';
                $this->paymentEwallet($memberTransaction, $phone, $channelCode);
            }

            if ($bootcamp->payment_channel == MemberTransaction::PAYMENT_CHANNEL_LINKAJA) {
                $phone = $data['phone'];
                $memberTransaction->transaction_exp = date('Y-m-d H:i:s', strtotime("+30 minutes"));
                $channelCode = 'ID_LINKAJA';
                $this->paymentEwallet($memberTransaction, $phone, $channelCode);
            }


            DB::commit();
            return to_route('detail', $memberTransaction->transaction_id);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('transaction');
            Log::error($th);
            return to_route('bootcamps');
        }
    }

    public function paymentEwallet($memberTransaction, $phone, $channelCode)
    {

        $paramsNew = [
            'reference_id' => $memberTransaction->transaction_id,
            'currency' => 'IDR',
            'amount' => $memberTransaction->final_price,
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code' => $channelCode,
            'channel_properties' => [
                'mobile_number' => '+628' . $phone,
                'success_redirect_url' => env('APP_URL') . '/bootcamp/transaction/' . $memberTransaction->transaction_id,
            ]
        ];

        $createInvoice = \Xendit\EWallets::createEWalletCharge($paramsNew);
        Log::info('===RESPONSE CHECKOUT EWALLET ' . $channelCode . '===');
        Log::info($createInvoice);
        $this->transactionRepository->paymentChannelEwallet($memberTransaction, $createInvoice);
        if ($createInvoice['status'] == 'PENDING') {
            $memberTransaction->payment_channel = $channelCode;
            $memberTransaction->save();
        }
    }

    public function detail($memberID, $bootcampTransactionID)
    {
        $memberTransaction = $this->transactionRepository->memberTransactionByTransactionID($memberID, $bootcampTransactionID);

        if ($memberTransaction) {
            $memberTransaction->ppn = 0.11 * $memberTransaction->price;
            $memberTransaction->buttonPayment = true;
            if ($memberTransaction->status == MemberTransaction::PAYMENT_STATUS_ACCEPT || $memberTransaction->status == MemberTransaction::PAYMENT_STATUS_EXPIRED) {
                $memberTransaction->buttonPayment = false;
            }
        }
        return $memberTransaction;
    }

    public function xenditCallback($data)
    {
        $memberTransaction = $this->transactionRepository->memberTransactionExternalID($data->external_id);
        if (!$memberTransaction) {
            Log::info('external_id not found in table member_transactions ' . $data->external_id);
            return false;
        }

        if ($data->status == "EXPIRED") {
            // update status member transaction
            $memberTransaction->status = MemberTransaction::PAYMENT_STATUS_EXPIRED;
            $memberTransaction->save();
            return true;
        }

        try {
            DB::beginTransaction();
            $xendit = $this->transactionRepository->xendit($data->external_id);
            if (!$xendit) {
                Log::info('external_id not found in table xendit ' . $data->external_id);
                return false;
            }
            $xendit->status = $data->status;
            $xendit->amount = $data->amount;
            $xendit->paid_amount = $data->paid_amount ?? NULL;
            $xendit->bank = $data->bank_code ?? NULL;
            $xendit->paid_at = isset($data->paid_at) ? date('Y-m-d H:i:s', strtotime($data->paid_at)) : NULL;
            $xendit->created = date('Y-m-d H:i:s', strtotime($data->created));
            $xendit->updated = date('Y-m-d H:i:s', strtotime($data->updated));
            $xendit->payer_email = $data->payer_email;
            $xendit->description = $data->description;
            $xendit->adjusted_received_amount = $data->adjusted_received_amount ?? NULL;
            $xendit->paid_amount = $data->fees_paid_amount ?? NULL;
            $xendit->payment_channel = $data->payment_channel ?? NULL;
            $xendit->payment_destination = $data->payment_destination ?? NULL;

            $xendit->save();

            if ($xendit->status == "PAID") {
                // update status member transaction
                if ($memberTransaction) {
                    $memberTransaction->status = MemberTransaction::PAYMENT_STATUS_ACCEPT;
                    $memberTransaction->save();
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            Log::info('===CALLBACK BANK TRANSFER FROM XENDIT===');
            Log::error($e);
            return false;
        }
    }

    public function xenditCallbackEwallet($data)
    {
        $memberTransaction = $this->transactionRepository->memberTransactionExternalID($data->data->reference_id);
        if (!$memberTransaction) {
            Log::info('external_id not found in table member_transactions ' . $data->data->reference_id);
            return false;
        }

        try {
            DB::beginTransaction();
            if ($data->data->status == 'SUCCEEDED') {
                $xendit = $this->transactionRepository->xendit($data->data->reference_id);
                if (!$xendit) {
                    Log::info('external_id not found in table xendit ' . $data->data->reference_id);
                    return false;
                }
                $xendit->xendit_transaction_id = $data->data->id;
                $xendit->event = $data->event;
                $xendit->phone = $data->data->channel_properties->mobile_number ?? NULL;
                $xendit->amount = $data->data->charge_amount;
                $xendit->currency = $data->data->currency;
                $xendit->status = $data->data->status;
                $xendit->member_id = $memberTransaction->member_id;
                $xendit->external_id = $data->data->reference_id;
                $xendit->ewallet_type = $data->data->channel_code;
                $xendit->save();


                $memberTransaction->status = MemberTransaction::PAYMENT_STATUS_ACCEPT;

                $memberTransaction->save();

                DB::commit();
                return true;
            } else {
                $memberTransaction->status = MemberTransaction::PAYMENT_STATUS_EXPIRED;
                $memberTransaction->save();
                DB::commit();
                return false;
            }
            return true;
        } catch (Exception $e) {
            DB::rollback();
            Log::info('===CALLBACK EWALLET===');
            Log::error($e);
            return false;
        }
    }
}
