<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    private ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * Render the React page.
     */
    public function page()
    {
        return Inertia::render('Expenses/Index');
    }

    /**
     * Get expenses (JSON API).
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = Auth::user()->current_workspace_id;
        $month = $request->query('month'); // YYYY-MM

        $expenses = $this->expenseService->getExpensesForWorkspace($workspaceId, $month);
        return response()->json($expenses);
    }

    /**
     * Store new expense (JSON API).
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->store($request->validated());
        return response()->json($expense, 201);
    }

    /**
     * Void an expense instead of deleting (JSON API).
     */
    public function void(string $id): JsonResponse
    {
        $expense = $this->expenseService->voidTransaction($id);
        return response()->json(['message' => 'İşlem iptal edildi.', 'data' => $expense]);
    }
}
