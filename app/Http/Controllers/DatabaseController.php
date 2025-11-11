<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
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
            // Conecta SEM banco
            $db_layout = $this->setupDynamicConnection($connection);
            $results = $db_layout->select('SHOW DATABASES');
            $excludedDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];

            // 1. Pega TODOS os bancos da conexão
            $allDatabases = collect($results)
                ->map(fn($db) => $db->Database)
                ->filter(fn($dbName) => !in_array($dbName, $excludedDbs))
                ->values();

            // 2. Pega as permissões ESPECÍFICAS do usuário
            $allowedDatabases = \App\Models\DatabasePermission::where('user_id', Auth::id())
                ->where('connection_id', $connection->id)
                ->pluck('database_name'); // Pega só os nomes

            // 3. Lógica de Filtro
            if (Auth::user()->role === 'Administrator') {
                // 3.1. Admins veem tudo
                $databases = $allDatabases->all();
            } else {
                // 3.2. Para Developers, checar se há permissões específicas
                if ($allowedDatabases->isEmpty()) {
                    // 3.3. Nenhuma permissão específica? Mostra TODOS os bancos
                    $databases = $allDatabases->all();
                } else {
                    // 3.4. Tem permissões? Filtra a lista
                    $databases = $allDatabases->filter(fn($dbName) => $allowedDatabases->contains($dbName))
                        ->values() // <-- ESTA É A CORREÇÃO
                        ->all();
                }
            }

        } finally {
            if ($db_layout) DB::disconnect($db_layout->getName());
        }

        $userConnections = Auth::user()->connections->map(fn($conn) => [
            'id' => $conn->id,
            'name' => $conn->name,
        ]);

        return [
            'userConnections' => $userConnections,
            'databases' => $databases, // A lista agora está filtrada
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
        $db = null;

        $isSelectQuery = Str::startsWith(strtoupper(trim($sql)), 'SELECT') ||
            Str::startsWith(strtoupper(trim($sql)), 'SHOW');

        if ($isSelectQuery) {
            // --- BLOCO DO SELECT / SHOW ---
            // Este bloco renderiza a si mesmo, não redireciona.

            $sqlResults = null;
            $error = null;
            $layoutData = [];

            try {
                $db = $this->setupDynamicConnection($connection, $databaseName);
                $sqlResults = $db->select($sql);
                $layoutData = $this->getLayoutData($connection);
            } catch (\Exception $e) {
                Log::error('Falha na query SQL (SELECT): ' . $e->getMessage());
                $error = 'Falha ao executar query: ' . $e->getMessage();
                // Se falhar, ainda tenta pegar o layout
                if (empty($layoutData)) {
                    try { $layoutData = $this->getLayoutData($connection); } catch (\Exception $e2) {}
                }
            } finally {
                if ($db) DB::disconnect($db->getName());
            }

            // Renderiza a página com resultados ou erro (sem redirect)
            return Inertia::render('Dashboard', [
                ...$layoutData,
                'selectedDatabaseName' => $databaseName,
                'sqlQuery' => $sql,
                'sqlResults' => $sqlResults,
                'sqlAffectedRows' => null,
                'connectionError' => $error,
                'activeTab' => 'sql',
            ]);

        } else {
            // --- BLOCO DO UPDATE/INSERT/DELETE ---
            // Este bloco SEMPRE redireciona, com sucesso ou erro.

            try {
                $db = $this->setupDynamicConnection($connection, $databaseName);
                $sqlAffectedRows = $db->affectingStatement($sql);

                // SUCESSO: Redireciona para a aba SQL com notificação de sucesso
                return Redirect::route('database.showSql', [
                    'connection' => $connection->id,
                    'databaseName' => $databaseName,
                ])->with('success', "$sqlAffectedRows linhas afetadas.");

            } catch (\Exception $e) {
                Log::error('Falha na query SQL (AFFECTING): ' . $e->getMessage());

                // ERRO: Redireciona para a aba SQL com notificação de erro
                return Redirect::route('database.showSql', [
                    'connection' => $connection->id,
                    'databaseName' => $databaseName,
                ])->with('error', 'Falha: ' . substr($e->getMessage(), 0, 200));

            } finally {
                if ($db) DB::disconnect($db->getName());
            }
        }
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
