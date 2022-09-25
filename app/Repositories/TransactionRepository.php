<?php

namespace App\Repositories;

use App\Models\MemberTransaction;
use App\Models\XenditTransaction;
use Illuminate\Support\Str;

class TransactionRepository
{

    public function memberTransaction($memberID, $bootcampID)
    {

        return MemberTransaction::with('xendit')->where([
            'member_id' => $memberID,
            'bootcamp_id' => $bootcampID
        ])
            ->first();
    }

    public function memberTransactionByTransactionID($memberID, $transactionID)
    {
        return MemberTransaction::with('xendit')->where([
            'member_id' => $memberID,
            'transaction_id' => $transactionID
        ])
            ->first();
    }

    public function memberTransactionExternalID($externalID)
    {
        return MemberTransaction::where('transaction_id', $externalID)->first();
    }

    public function create($data)
    {
        $transaction = new MemberTransaction();
        $transaction->transaction_id = $data['transaction_id'];
        $transaction->payment_channel = $data['payment_channel'];
        $transaction->member_id = $data['member_id'];
        $transaction->bootcamp_id = $data['bootcamp_id'];
        $transaction->price = $data['price'];
        $transaction->final_price = $data['final_price'];
        $transaction->status = MemberTransaction::PAYMENT_STATUS_PENDING;
        $transaction->transaction_exp = $data['transaction_exp'];
        $transaction->save();
        return $transaction;
    }

    public function paymentChannelBank($memberTransaction, $dataInvoice)
    {
        $xendit = new XenditTransaction();
        $xendit->id = Str::uuid();
        $xendit->xendit_transaction_id = $dataInvoice['id'];
        $xendit->external_id = $dataInvoice['external_id'];
        $xendit->member_id = $memberTransaction['member_id'];
        $xendit->status = $dataInvoice['status'];
        $xendit->amount = $dataInvoice['amount'];
        $xendit->payer_email = $dataInvoice['payer_email'];
        $xendit->description = $dataInvoice['description'];
        $xendit->expiry_date = date('Y-m-d H:i:s', strtotime($dataInvoice['expiry_date']));
        $xendit->invoice_url = $dataInvoice['invoice_url'];
        $xendit->created = date('Y-m-d H:i:s', strtotime($dataInvoice['created']));
        $xendit->updated = date('Y-m-d H:i:s', strtotime($dataInvoice['updated']));
        $xendit->save();
    }

    public function paymentChannelEwallet($memberTransaction, $createInvoice)
    {
        $xendit = new XenditTransaction();
        $xendit->id = Str::uuid();
        $xendit->xendit_transaction_id = $createInvoice['id'];
        $xendit->external_id = $createInvoice['reference_id'];
        $xendit->member_id = $memberTransaction['member_id'];
        $xendit->status = $createInvoice['status'];
        $xendit->amount = $createInvoice['charge_amount'];
        $xendit->phone = $createInvoice['channel_properties']['mobile_number'] ?? NULL;
        $xendit->ewallet_type = $createInvoice['channel_code'];
        $xendit->invoice_url = $createInvoice['actions']['desktop_web_checkout_url'] ?? NULL;
        $xendit->created = date('Y-m-d H:i:s', strtotime($createInvoice['created']));
        $xendit->save();
    }

    public function xendit($externalID)
    {
        return XenditTransaction::where('external_id', $externalID)->first();
    }
}
