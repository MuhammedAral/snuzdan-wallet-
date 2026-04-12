<?php

namespace App\Observers;

use App\Models\ExpenseTransaction;

class ExpenseTransactionObserver
{
    /**
     * Handle the ExpenseTransaction "created" event.
     */
    public function created(ExpenseTransaction $transaction): void
    {
        // When an expense is added, decrease the account balance.
        $account = $transaction->account;
        if ($account) {
            $account->balance -= $transaction->amount;
            $account->save();
        }
    }

    /**
     * Handle the ExpenseTransaction "updated" event. (For soft-delete/void)
     */
    public function updated(ExpenseTransaction $transaction): void
    {
        // Check if the transaction was voided in this update
        if ($transaction->wasChanged('is_void') && $transaction->is_void) {
            $account = $transaction->account;
            if ($account) {
                // Determine how much was subtracted previously, and increment it
                $account->balance += $transaction->amount;
                $account->save();
            }
        }
    }
}
