<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DatabaseController extends Controller
{
    public function index(Connection $connection)
    {
        if (!Auth::user()->connections->contains($connection)) {
            abort(403, 'Acesso não autorizado a esta conexão.');
        }

        $databases = [];
        $error = null;

        $dynamicConnectionName = 'dynamic_db_' . $connection->id;

        Config::set('database.connections.' . $dynamicConnectionName, [
            'driver' => 'mysql',
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => null,
            'username' => $connection->database_user,
            'password' => $connection->database_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        try {
            $db = DB::connection($dynamicConnectionName);
            $results = $db->select('SHOW DATABASES');

            $excludedDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
            $databases = collect($results)
                ->map(fn($db) => $db->Database)
                ->filter(fn($dbName) => !in_array($dbName, $excludedDbs))
                ->values()
                ->all();

        } catch (\Exception $e) {
            Log::error('Falha na conexão dinâmica: ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        }

        $userConnections = Auth::user()->connections->map(fn($conn) => [
            'id' => $conn->id,
            'name' => $conn->name,
        ]);

        return Inertia::render('Dashboard', [
            'userConnections' => $userConnections,
            'selectedConnectionId' => $connection->id,
            'databases' => $databases,
            'connectionError' => $error,
        ]);
    }
}
