<?php

namespace App\Jobs;

use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\RecurringTransaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRecurringTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        // 1. Fetch all active recurring transactions where next_run_date is today or earlier
        $transactions = RecurringTransaction::where('is_active', true)
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        foreach ($transactions as $recurring) {
            
            // Validate if the account still exists and is active, else skip.
            // Also validate if category exists.
            if (!$recurring->account || !$recurring->category) {
                continue;
            }

            // 2. Insert into the appropriate ledger
            if ($recurring->direction === 'INCOME') {
                IncomeTransaction::create([
                    'workspace_id' => $recurring->workspace_id,
                    'created_by_user_id' => $recurring->created_by_user_id,
                    'account_id' => $recurring->account_id,
                    'category_id' => $recurring->category_id,
                    'amount' => $recurring->amount,
                    'currency' => $recurring->currency,
                    'income_date' => $recurring->next_run_date,
                    'notes' => 'Oto-Pilot Düzenli İşlem: ' . $recurring->note,
                ]);
            } else {
                ExpenseTransaction::create([
                    'workspace_id' => $recurring->workspace_id,
                    'created_by_user_id' => $recurring->created_by_user_id,
                    'account_id' => $recurring->account_id,
                    'category_id' => $recurring->category_id,
                    'amount' => $recurring->amount,
                    'currency' => $recurring->currency,
                    'expense_date' => $recurring->next_run_date,
                    'notes' => 'Oto-Pilot Düzenli İşlem: ' . $recurring->note,
                ]);
            }

            // 3. Move next_run_date forward based on period
            $nextDate = Carbon::parse($recurring->next_run_date);
            switch ($recurring->period) {
                case 'DAILY':
                    $nextDate->addDay();
                    break;
                case 'WEEKLY':
                    $nextDate->addWeek();
                    break;
                case 'MONTHLY':
                    $nextDate->addMonth();
                    break;
                case 'YEARLY':
                    $nextDate->addYear();
                    break;
            }

            $recurring->next_run_date = $nextDate;
            $recurring->save();
        }
    }
}
