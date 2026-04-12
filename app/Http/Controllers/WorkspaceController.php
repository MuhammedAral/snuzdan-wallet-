<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    /**
     * Create a new workspace (shared wallet).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = $request->user();

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'created_by' => $user->id,
        ]);

        // Attach the creator as 'owner' in pivot
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        // Switch the user's active workspace to the new one
        $user->current_workspace_id = $workspace->id;
        $user->save();

        return redirect()->back()->with('success', 'Çalışma alanı oluşturuldu: ' . $workspace->name);
    }

    /**
     * Switch to a different workspace.
     */
    public function switch(Request $request, string $id)
    {
        $user = $request->user();

        // Verify the user actually belongs to this workspace
        $workspace = $user->workspaces()->where('workspaces.id', $id)->firstOrFail();

        $user->current_workspace_id = $workspace->id;
        $user->save();

        return redirect()->back()->with('success', 'Aktif cüzdan değiştirildi: ' . $workspace->name);
    }

    /**
     * Invite a user to the current workspace by email.
     */
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', Rule::in(['editor', 'viewer'])],
        ]);

        $currentUser = $request->user();

        if (!$currentUser->current_workspace_id) {
            return redirect()->back()->withErrors(['workspace' => 'Lütfen önce bir çalışma alanı oluşturun.']);
        }

        $workspace = Workspace::findOrFail($currentUser->current_workspace_id);

        // Verify the current user is the owner
        $pivotRole = $workspace->members()->where('users.id', $currentUser->id)->first()?->pivot?->role;
        if ($pivotRole !== 'owner') {
            return redirect()->back()->withErrors(['workspace' => 'Sadece çalışma alanı sahibi davet gönderebilir.']);
        }

        $invitee = User::where('email', $validated['email'])->first();

        if ($invitee->id === $currentUser->id) {
            return redirect()->back()->withErrors(['email' => 'Kendinizi davet edemezsiniz.']);
        }

        // Check if already a member
        if ($workspace->members()->where('users.id', $invitee->id)->exists()) {
            return redirect()->back()->withErrors(['email' => 'Bu kullanıcı zaten bu çalışma alanında.']);
        }

        $workspace->members()->attach($invitee->id, ['role' => $validated['role']]);

        return redirect()->back()->with('success', $invitee->display_name . ' başarıyla davet edildi.');
    }

    /**
     * Return list of workspaces for the current user (API).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $workspaces = $user->workspaces()->withPivot('role')->get();

        return response()->json($workspaces);
    }
}
