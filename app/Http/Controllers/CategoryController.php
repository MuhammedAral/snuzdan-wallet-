<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * List categories for authenticated user's workspace.
     * Supports ?direction=INCOME|EXPENSE filter.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Category::forWorkspace($user->current_workspace_id);

        if ($request->has('direction')) {
            $query->byDirection($request->input('direction'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    /**
     * Create a new custom category.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'icon'      => 'nullable|string|max:50',
            'color'     => 'nullable|string|max:7',
            'direction' => 'required|string|in:INCOME,EXPENSE',
        ], [
            'name.required'      => 'Kategori adı zorunludur.',
            'name.max'           => 'Kategori adı en fazla 100 karakter olabilir.',
            'direction.required' => 'Yön (gelir/gider) seçimi zorunludur.',
            'direction.in'       => 'Yön yalnızca INCOME veya EXPENSE olabilir.',
        ]);

        $category = Category::create([
            'workspace_id' => $user->current_workspace_id,
            'name'         => $validated['name'],
            'icon'         => $validated['icon'] ?? '📌',
            'color'        => $validated['color'] ?? '#AEB6BF',
            'direction'    => $validated['direction'],
            'cat_type'     => 'CUSTOM',
        ]);

        return response()->json($category, 201);
    }

    /**
     * Update a custom category (name, icon, color only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        $category = Category::where('id', $id)
            ->where('workspace_id', $user->current_workspace_id)
            ->where('cat_type', 'CUSTOM')
            ->firstOrFail();

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:100',
            'icon'  => 'sometimes|string|max:50',
            'color' => 'sometimes|string|max:7',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Soft-delete: set is_active = false.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        $category = Category::where('id', $id)
            ->where('workspace_id', $user->current_workspace_id)
            ->where('cat_type', 'CUSTOM')
            ->firstOrFail();

        $category->update(['is_active' => false]);

        return response()->json(['message' => 'Kategori pasife alındı.']);
    }
}
