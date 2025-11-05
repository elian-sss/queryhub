<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ConnectionPermissionController extends Controller
{
    public function edit(Connection $connection)
    {
        $connection->load('users');

        $assignedUserIds = $connection->users->pluck('id');

        return Inertia::render('Connections/EditPermissions', [
            'connection' => [
                'id' => $connection->id,
                'name' => $connection->name,
            ],
            'allUsers' => User::all()->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]),
            'assignedUserIds' => $assignedUserIds,
        ]);
    }

    public function update(Request $request, Connection $connection)
    {
        $request->validate([
            'user_ids' => 'present|array',    
            'user_ids.*' => 'exists:users,id',
        ]);

        $developerUserIds = $request->input('user_ids', []);

        $adminUserIds = User::where('role', 'Administrator')->pluck('id')->toArray();

        $allUserIds = array_unique(array_merge($developerUserIds, $adminUserIds));

        $connection->users()->sync($allUserIds);

        return redirect()->route('connections.index')->with('success', 'Permissões atualizadas.');
    }
}
