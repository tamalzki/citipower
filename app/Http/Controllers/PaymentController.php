<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request, Sale $sale)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,e_wallet,other',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $result = DB::transaction(function () use ($request, $sale) {
            // Lock the sale row so concurrent cashiers can't double-pay
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);

            $currentPaid = (float) $sale->payments()->sum('amount');
            $balance     = max(0, (float) $sale->total_amount - $currentPaid);
            $amount      = min((float) $request->amount, $balance);

            if ($amount <= 0) {
                return 'already_paid';
            }

            $sale->payments()->create([
                'payment_date'   => $request->payment_date,
                'amount'         => $amount,
                'payment_method' => $request->payment_method,
                'reference_no'   => $request->reference_no,
                'note'           => $request->note,
            ]);

            return 'ok';
        });

        if ($result === 'already_paid') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Sale is already fully paid.');
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Sale $sale, Payment $payment)
    {
        if ((int) $payment->sale_id !== (int) $sale->id) {
            abort(404);
        }

        $payment->delete();

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Payment removed successfully.');
    }
}
