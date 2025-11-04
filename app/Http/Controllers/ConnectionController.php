<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ConnectionController extends Controller
{
    public function index()
    {
        $connections = Connection::all()->map(fn($conn) => [
            'id' => $conn->id,
            'name' => $conn->name,
            'host' => $conn->host,
            'port' => $conn->port,
            'database_user' => $conn->database_user,
        ]);

        return Inertia::render('Connections/Index', [
            'connections' => $connections,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:connections',
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'database_user' => 'required|string|max:255',
            'database_password' => 'nullable|string',
        ]);

        Connection::create([
            'name' => $request->name,
            'host' => $request->host,
            'port' => $request->port,
            'database_user' => $request->database_user,
            'database_password' => $request->database_password,
        ]);

        return redirect()->route('connections.index')->with('success', 'Conexão criada.');
    }

    public function update(Request $request, Connection $connection)
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('connections')->ignore($connection->id)],
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'database_user' => 'required|string|max:255',
            'database_password' => 'nullable|string',
        ]);
        $connection->update($validated);

        return redirect()->route('connections.index')->with('success', 'Conexão atualizada.');
    }

    public function destroy(Connection $connection)
    {
        $connection->delete();
        return redirect()->route('connections.index')->with('success', 'Conexão removida.');
    }
}
