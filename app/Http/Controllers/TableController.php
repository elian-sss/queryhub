<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TableController extends Controller
{
    public function index(Connection $connection, $databaseName)
    {
        if (!Auth::user()->connections->contains($connection)) {
            abort(403, 'Acesso não autorizado a esta conexão.');
        }

        $databases = [];
        $tables = [];
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

            Config::set('database.connections.' . $dynamicConnectionName . '.database', $databaseName);
            DB::reconnect($dynamicConnectionName);

            $results = $db->select('SHOW TABLES');

            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();

        } catch (\Exception $e) {
            Log::error('Falha na conexão dinâmica (Tables): ' . $e->getMessage());
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
            'selectedDatabaseName' => $databaseName,
            'tables' => $tables,
            'connectionError' => $error,
        ]);
    }

    public function showData(Connection $connection, $databaseName, $tableName)
    {
        if (!Auth::user()->connections->contains($connection)) {
            abort(403, 'Acesso não autorizado a esta conexão.');
        }

        $databases = [];
        $tables = [];
        $tableData = [
            'columns' => [],
            'rows' => [],
        ];
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

            Config::set('database.connections.' . $dynamicConnectionName . '.database', $databaseName);
            DB::reconnect($dynamicConnectionName);

            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();

            $columnsQuery = $db->select("SHOW COLUMNS FROM `{$tableName}`");
            $tableData['columns'] = collect($columnsQuery)->pluck('Field')->all();

            $perPage = 100;
            $tableData['rowsPaginator'] = $db->table($tableName)->paginate($perPage)
                ->withQueryString();

        } catch (\Exception $e) {
            Log::error('Falha na conexão dinâmica (ShowData): ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        } finally {
            DB::disconnect($dynamicConnectionName);
        }

        $userConnections = Auth::user()->connections->map(fn($conn) => [
            'id' => $conn->id,
            'name' => $conn->name,
        ]);

        return Inertia::render('Dashboard', [
            'userConnections' => $userConnections,
            'selectedConnectionId' => $connection->id,
            'databases' => $databases,
            'selectedDatabaseName' => $databaseName,
            'tables' => $tables,
            'selectedTableName' => $tableName,
            'tableData' => $tableData,
            'connectionError' => $error,
        ]);
    }
}
