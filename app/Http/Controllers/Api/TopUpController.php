<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Midtrans\Snap;

class TopUpController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only(['amount', 'pin', 'payment_method_code']);

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'payment_method_code' => 'required|in:bni_va,bca_va,bri_va'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }

        $transactionType = TransactionType::where('code', 'top_up')->first();
        $paymenyMethod = PaymentMethod::where('code', $request->payment_method_code)->first();

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'user_id' => auth()->user()->id,
                'payment_method_id' => $paymenyMethod->id,
                'product_id' => null,
                'transaction_type_id' => $transactionType->id,
                'amount' => $request->amount,
                'transaction_code' => strtoupper(Str::random(10)),
                'description' => 'Top Up via ' . $paymenyMethod->name,
                'status' => 'pending'
            ]);

            $params = $this->buildMidtransParameters([
                'transaction_code' => $transaction->transaction_code,
                'amount' => $transaction->amount,
                'payment_method' => $transaction->payment_method
            ]);

            $midtrans = $this->callMidtrans($params);


            DB::commit();
            
            return response()->json($midtrans);

        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json(['message' =>  $th->getMessage()], 500);
        }
    }

    private function callMidtrans(array $param)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANDS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANDS_IS_PRODUCTION');
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANDS_IS_SANITIZED');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANDS_IS_3DS');

        $createTransaction = Snap::createTransaction($param);

        return [
            'redirect_url' => $createTransaction->redirect_url,
            'token' => $createTransaction->token,
        ];
    }

    private function buildMidtransParameters(array $params)
    {
        $transactionDetails = [
            'order_id' => $params['transaction_code'],
            'gross_amount' => $params['amount'],
        ];

        $user = auth()->user();
        $spiltName = $this->splitName($user->name);
        $customerDetails = [
            'first_name' => $spiltName['first_name'],
            'last_name' => $spiltName['last_name'],
            'email' => $user->email
        ];

        $enabledPayment = [
            $params['payment_method']
        ];

        return [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'enabledPayment' => $enabledPayment
        ];
    }

    private function splitName($fullName)
    {
        $name = explode(' ', $fullName);
        $lastName = count($name) > 1 ? array_pop($name) : $fullName;
        $firstName = implode(' ', $name);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
    }
}
