<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ConnectionController extends Controller
{
    public function index()
    {
        return Inertia::render('Connections/Index', [
            'connections' => Connection::all(),
        ]);
    }

    /**
     * Salva uma nova conexão no banco.
     */
    public function store(Request $request)
    {
        // TODO: Adicionar lógica para permitir acesso somente a Admins

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
}
