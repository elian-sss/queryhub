<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DatabaseController extends Controller
{
    /**
     * Lógica de conexão (privada)
     */
    private function setupDynamicConnection(Connection $connection, $databaseName = null)
    {
        if (!Auth::user()->connections->contains($connection)) {
            abort(403, 'Acesso não autorizado a esta conexão.');
        }

        $dynamicConnectionName = 'dynamic_db_' . $connection->id;

        Config::set('database.connections.' . $dynamicConnectionName, [
            'driver' => 'mysql',
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $databaseName,
            'username' => $connection->database_user,
            'password' => $connection->database_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge($dynamicConnectionName);

        return DB::connection($dynamicConnectionName);
    }

    /**
     * Coleta dados comuns para o layout (Conexões e Bancos)
     */
    private function getLayoutData(Connection $connection)
    {
        $db_layout = null;
        $databases = [];
        try {
            $db_layout = $this->setupDynamicConnection($connection);
            $results = $db_layout->select('SHOW DATABASES');
            $excludedDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
            $databases = collect($results)
                ->map(fn($db) => $db->Database)
                ->filter(fn($dbName) => !in_array($dbName, $excludedDbs))
                ->values()
                ->all();
        } finally {
            if ($db_layout) DB::disconnect($db_layout->getName());
        }

        $userConnections = Auth::user()->connections->map(fn($conn) => [
            'id' => $conn->id,
            'name' => $conn->name,
        ]);

        return [
            'userConnections' => $userConnections,
            'databases' => $databases,
            'selectedConnectionId' => $connection->id,
        ];
    }


    /**
     * Mostra a lista de bancos de dados
     */
    public function index(Connection $connection)
    {
        $layoutData = $this->getLayoutData($connection);

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'activeTab' => 'tables', // 'tables' é a aba padrão
        ]);
    }

    /**
     * Executa uma consulta SQL manual no banco de dados.
     * (MÉTODO CORRIGIDO)
     */
    public function executeSql(Request $request, Connection $connection, $databaseName)
    {
        $request->validate(['query' => 'required|string']);
        $sql = $request->input('query');

        $sqlResults = null;
        $sqlAffectedRows = null;
        $error = null;
        $layoutData = [];
        $db = null;

        try {
            // --- PASSO 1: Executar a Query (Conexão COM banco) ---
            $db = $this->setupDynamicConnection($connection, $databaseName);

            $isSelectQuery = Str::startsWith(strtoupper(trim($sql)), 'SELECT') ||
                Str::startsWith(strtoupper(trim($sql)), 'SHOW');

            if ($isSelectQuery) {
                $sqlResults = $db->select($sql);
            } else {
                $sqlAffectedRows = $db->affectingStatement($sql);
            }

            // --- PASSO 2: Pegar Layout Data (Conexão SEM banco) ---
            $layoutData = $this->getLayoutData($connection);

        } catch (\Exception $e) {
            Log::error('Falha na query SQL: ' . $e->getMessage());
            $error = 'Falha ao executar query: Tabelas ' . $e->getMessage();
            // Se a query falhar, ainda tentamos pegar o layout
            if (empty($layoutData)) {
                try {
                    $layoutData = $this->getLayoutData($connection);
                } catch (\Exception $e2) {
                    // Falha total
                }
            }
        } finally {
            if ($db) DB::disconnect($db->getName());
        }

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'selectedDatabaseName' => $databaseName,
            'sqlQuery' => $sql,
            'sqlResults' => $sqlResults,
            'sqlAffectedRows' => $sqlAffectedRows,
            'connectionError' => $error,
            'activeTab' => 'sql',
        ]);
    }

    public function showSql(Connection $connection, $databaseName)
    {
        $layoutData = [];
        $error = null;

        try {
            // Pega os dados do layout (lista de bancos e conexões)
            $layoutData = $this->getLayoutData($connection);
        } catch (\Exception $e) {
            Log::error('Falha na conexão (showSql): ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        }

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'selectedDatabaseName' => $databaseName,
            'connectionError' => $error,
            'activeTab' => 'sql', // Diz ao frontend para abrir a aba SQL
        ]);
    }
}
