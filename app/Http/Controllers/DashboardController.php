<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Render the React page.
     */
    public function page()
    {
        return Inertia::render('Dashboard');
    }

    /**
     * Get summary metrics for the dashboard.
     */
    public function summary(): JsonResponse
    {
        $workspaceId = Auth::user()->current_workspace_id;

        if (!$workspaceId) {
            return response()->json([
                'monthly_income'  => 0,
                'monthly_expense' => 0,
                'monthly_balance' => 0,
            ]);
        }

        $currentMonth = date('Y-m');

        // İşlemleri çek
        $expenses = ExpenseTransaction::where('workspace_id', $workspaceId)
            ->active()
            ->whereRaw("TO_CHAR(expense_date, 'YYYY-MM') = ?", [$currentMonth])
            ->get();

        $incomes = IncomeTransaction::where('workspace_id', $workspaceId)
            ->active()
            ->whereRaw("TO_CHAR(income_date, 'YYYY-MM') = ?", [$currentMonth])
            ->get();

        // N+1 Fix: Kullanılan tüm para birimlerini topla, tek sorguda FX kurörini al
        $currencies = $expenses->pluck('currency')
            ->merge($incomes->pluck('currency'))
            ->unique()
            ->reject(fn($c) => $c === 'TRY')
            ->values()
            ->all();

        $rateMap = $this->prefetchFxRates($currencies);

        $totalExpense = 0;
        foreach ($expenses as $exp) {
            $totalExpense += $this->convertToTry($exp->amount, $exp->currency, $rateMap);
        }

        $totalIncome = 0;
        foreach ($incomes as $inc) {
            $totalIncome += $this->convertToTry($inc->amount, $inc->currency, $rateMap);
        }

        return response()->json([
            'monthly_income'  => (float) $totalIncome,
            'monthly_expense' => (float) $totalExpense,
            'monthly_balance' => (float) ($totalIncome - $totalExpense),
        ]);
    }

    /**
     * Get the recent activity feed combining multiple transaction types.
     */
    public function recentActivity(): JsonResponse
    {
        $workspaceId = Auth::user()->current_workspace_id;

        if (!$workspaceId) {
            return response()->json([]);
        }

        $expenses = ExpenseTransaction::with('category')
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'exp_' . $item->id,
                    'type' => 'EXPENSE',
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'date' => $item->expense_date,
                    'category' => $item->category,
                    'notes' => $item->notes,
                    'is_void' => $item->is_void,
                    'created_at' => $item->created_at,
                ];
            });

        $incomes = IncomeTransaction::with('category')
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'inc_' . $item->id,
                    'type' => 'INCOME',
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'date' => $item->income_date,
                    'category' => $item->category,
                    'notes' => $item->notes,
                    'is_void' => $item->is_void,
                    'created_at' => $item->created_at,
                ];
            });

        // Birleştir ve sırala
        $activities = $expenses->concat($incomes)->sortByDesc('created_at')->take(10)->values();

        return response()->json($activities);
    }

    /**
     * Convert an amount to TRY using a prefetched rate map.
     *
     * @param float  $amount
     * @param string $currency
     * @param array  $rateMap  ['USD' => 32.50, ...]
     */
    private function convertToTry($amount, $currency, array $rateMap = []): float
    {
        if ($currency === 'TRY') {
            return (float) $amount;
        }

        // Fallback rates
        $fallbackRates = [
            'USD' => 32.50,
            'EUR' => 35.00,
            'GBP' => 40.50,
        ];

        $rate = $rateMap[$currency] ?? $fallbackRates[$currency] ?? 1.0;

        return (float) $amount * $rate;
    }

    /**
     * Verilen para birimlerinin TRY kurlarını tek DB sorgusunda çek.
     *
     * @param array $currencies  ['USD', 'EUR', ...]
     * @return array             ['USD' => 32.50, ...]
     */
    private function prefetchFxRates(array $currencies): array
    {
        if (empty($currencies)) {
            return [];
        }

        $records = \App\Models\FxRateSnapshot::whereIn('base_currency', $currencies)
            ->where('quote_currency', 'TRY')
            ->orderByDesc('fetched_at')
            ->get()
            ->unique('base_currency') // Her para birimi için en son kuru al
            ->keyBy('base_currency');

        $rateMap = [];
        foreach ($currencies as $currency) {
            if (isset($records[$currency])) {
                $rateMap[$currency] = (float) $records[$currency]->rate;
            }
        }

        return $rateMap;
    }
}
