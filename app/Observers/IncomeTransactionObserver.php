<?php

namespace App\Observers;

use App\Models\IncomeTransaction;

class IncomeTransactionObserver
{
    /**
     * Handle the IncomeTransaction "created" event.
     */
    public function created(IncomeTransaction $transaction): void
    {
        // When an income is added, increase the account balance.
        $account = $transaction->account;
        if ($account) {
            $account->balance += $transaction->amount;
            $account->save();
        }
    }

    /**
     * Handle the IncomeTransaction "updated" event. (For soft-delete/void)
     */
    public function updated(IncomeTransaction $transaction): void
    {
        // Check if the transaction was voided in this update
        if ($transaction->wasChanged('is_void') && $transaction->is_void) {
            $account = $transaction->account;
            if ($account) {
                // Determine how much was added previously, and decrement it
                $account->balance -= $transaction->amount;
                $account->save();
            }
        }
    }
}
