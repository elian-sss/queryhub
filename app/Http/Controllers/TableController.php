<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TableController extends Controller
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

        // Limpa a conexão antiga para forçar a leitura da nova config
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
     * Lista as tabelas de um banco de dados específico.
     * (MÉTODO CORRIGIDO)
     */
    public function index(Connection $connection, $databaseName)
    {
        $tables = [];
        $error = null;
        $layoutData = [];
        $db = null;

        try {
            // --- PASSO 1: Pegar Tabelas (Conexão COM banco) ---
            $db = $this->setupDynamicConnection($connection, $databaseName);
            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();

            // --- PASSO 2: Pegar Layout Data (Conexão SEM banco) ---
            // Isso é feito por último para não sobrescrever a config da conexão $db
            $layoutData = $this->getLayoutData($connection);

        } catch (\Exception $e) {
            Log::error('Falha na conexão dinâmica (Tables): ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        } finally {
            if ($db) DB::disconnect($db->getName());
        }

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'selectedDatabaseName' => $databaseName,
            'tables' => $tables,
            'connectionError' => $error,
            'activeTab' => 'tables',
        ]);
    }

    /**
     * Mostra os dados de uma tabela específica.
     * (MÉTODO CORRIGIDO)
     */
    public function showData(Request $request, Connection $connection, $databaseName, $tableName)
    {
        $tables = [];
        $tableData = [
            'columns' => [],
            'rowsPaginator' => null,
            'primaryKeyColumns' => [],
        ];
        $error = null;
        $layoutData = [];
        $db = null;

        try {
            // --- PASSO 1: Pegar Tabelas e Dados (Conexão COM banco) ---
            $db = $this->setupDynamicConnection($connection, $databaseName);

            // 1a. Pegar tabelas (para a 3ª coluna)
            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();

            // 1b. Pega dados da tabela (colunas e linhas)
            $columnsQuery = $db->select("SHOW COLUMNS FROM `{$tableName}`");
            $tableData['columns'] = collect($columnsQuery)->pluck('Field')->all();

            $pkResults = $db->select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
            $tableData['primaryKeyColumns'] = collect($pkResults)->pluck('Column_name')->all();

            $perPage = 100;
            $tableData['rowsPaginator'] = $db->table($tableName)->paginate($perPage)
                ->withQueryString();

            // --- PASSO 2: Pegar Layout Data (Conexão SEM banco) ---
            $layoutData = $this->getLayoutData($connection);

        } catch (\Exception $e) {
            Log::error('Falha na conexão dinâmica (ShowData): ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        } finally {
            if ($db) DB::disconnect($db->getName());
        }

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'selectedDatabaseName' => $databaseName,
            'tables' => $tables,
            'selectedTableName' => $tableName,
            'tableData' => $tableData,
            'connectionError' => $error,
            'activeTab' => 'data',
        ]);
    }

    public function showStructure(Request $request, Connection $connection, $databaseName, $tableName)
    {
        $tables = [];
        $tableStructure = []; // Prop para os dados da estrutura
        $error = null;
        $layoutData = [];
        $db = null;

        try {
            // --- PASSO 1: Conectar COM o banco ---
            $db = $this->setupDynamicConnection($connection, $databaseName);

            // 1a. Pegar tabelas (para a 3ª coluna)
            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();

            // 1b. Pega a ESTRUTURA da tabela (A NOVA LÓGICA)
            // SHOW COLUMNS retorna: Field, Type, Null, Key, Default, Extra
            $tableStructure = $db->select("SHOW COLUMNS FROM `{$tableName}`");

            // --- PASSO 2: Pegar Layout Data (Conexão SEM banco) ---
            $layoutData = $this->getLayoutData($connection);

        } catch (\Exception $e) {
            Log::error('Falha na conexão dinâmica (ShowStructure): ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        } finally {
            if ($db) DB::disconnect($db->getName());
        }

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'selectedDatabaseName' => $databaseName,
            'tables' => $tables,
            'selectedTableName' => $tableName,
            'tableStructure' => $tableStructure, // <-- Passa a estrutura
            'connectionError' => $error,
            'activeTab' => 'structure', // <-- Define a aba ativa
        ]);
    }

    public function destroyRow(Request $request, Connection $connection, $databaseName, $tableName)
    {
        // Pega a linha inteira que o frontend enviou
        $row = $request->input('row');
        if (!$row) {
            return Redirect::back()->with('error', 'Nenhum dado de linha recebido.');
        }

        $db = null;
        try {
            // --- PASSO 1: Conectar COM o banco ---
            $db = $this->setupDynamicConnection($connection, $databaseName);

            // --- PASSO 2: Pegar a Chave Primária (de novo, por segurança) ---
            $pkResults = $db->select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
            $primaryKeyColumns = collect($pkResults)->pluck('Column_name')->all();

            if (empty($primaryKeyColumns)) {
                return Redirect::back()->with('error', 'Esta tabela não tem chave primária. Não é possível deletar a linha.');
            }

            // --- PASSO 3: Construir a Cláusula WHERE dinâmica ---
            $whereClauses = [];
            foreach ($primaryKeyColumns as $pkColumn) {
                if (!isset($row[$pkColumn])) {
                    return Redirect::back()->with('error', "Valor da chave primária '{$pkColumn}' não encontrado.");
                }
                $whereClauses[$pkColumn] = $row[$pkColumn];
            }

            // --- PASSO 4: Executar o DELETE ---
            // Usamos o Query Builder com o array de WHEREs,
            // o que nos protege de SQL Injection.
            $affectedRows = $db->table($tableName)->where($whereClauses)->delete();

            if ($affectedRows > 0) {
                return Redirect::back()->with('success', 'Linha deletada com sucesso.');
            } else {
                return Redirect::back()->with('error', 'A linha não foi encontrada ou já foi deletada.');
            }

        } catch (\Exception $e) {
            Log::error('Falha ao deletar linha: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Falha ao deletar: '.$e->getMessage());
        } finally {
            if ($db) DB::disconnect($db->getName());
        }
    }
}
