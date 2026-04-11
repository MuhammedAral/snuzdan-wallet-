<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Services\IncomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IncomeController extends Controller
{
    private IncomeService $incomeService;

    public function __construct(IncomeService $incomeService)
    {
        $this->incomeService = $incomeService;
    }

    /**
     * Render the React page.
     */
    public function page()
    {
        return Inertia::render('Income/Index');
    }

    /**
     * Get incomes (JSON API).
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = Auth::user()->current_workspace_id;
        $month = $request->query('month'); // YYYY-MM

        $incomes = $this->incomeService->getIncomesForWorkspace($workspaceId, $month);
        return response()->json($incomes);
    }

    /**
     * Store new income (JSON API).
     */
    public function store(StoreIncomeRequest $request): JsonResponse
    {
        $income = $this->incomeService->store($request->validated());
        return response()->json($income, 201);
    }

    /**
     * Void an income instead of deleting (JSON API).
     */
    public function void(string $id): JsonResponse
    {
        $income = $this->incomeService->voidTransaction($id);
        return response()->json(['message' => 'İşlem iptal edildi.', 'data' => $income]);
    }
}
