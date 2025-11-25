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


            $allDatabases = collect($results)
                ->map(fn($db) => $db->Database)
                ->filter(fn($dbName) => !in_array($dbName, $excludedDbs))
                ->values();


            $allowedDatabases = \App\Models\DatabasePermission::where('user_id', Auth::id())
                ->where('connection_id', $connection->id)
                ->pluck('database_name');


            if (Auth::user()->role === 'Administrator') {

                $databases = $allDatabases->all();
            } else {

                if ($allowedDatabases->isEmpty()) {

                    $databases = $allDatabases->all();
                } else {

                    $databases = $allDatabases->filter(fn($dbName) => $allowedDatabases->contains($dbName))
                        ->values()
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
            'databases' => $databases,
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

            $db = $this->setupDynamicConnection($connection, $databaseName);
            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();



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

            $db = $this->setupDynamicConnection($connection, $databaseName);


            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();


            $columnsQuery = $db->select("SHOW COLUMNS FROM `{$tableName}`");
            $tableData['columns'] = collect($columnsQuery)->pluck('Field')->all();

            $pkResults = $db->select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
            $tableData['primaryKeyColumns'] = collect($pkResults)->pluck('Column_name')->all();

            $perPage = 100;
            $tableData['rowsPaginator'] = $db->table($tableName)->paginate($perPage)
                ->withQueryString();


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
        $tableStructure = [];
        $error = null;
        $layoutData = [];
        $db = null;

        try {

            $db = $this->setupDynamicConnection($connection, $databaseName);


            $results = $db->select('SHOW TABLES');
            $key = 'Tables_in_' . $databaseName;
            $tables = collect($results)->map(fn($t) => $t->$key)->values()->all();



            $tableStructure = $db->select("SHOW COLUMNS FROM `{$tableName}`");


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
            'tableStructure' => $tableStructure,
            'connectionError' => $error,
            'activeTab' => 'structure',
        ]);
    }

    public function updateRow(Request $request, Connection $connection, $databaseName, $tableName)
    {

        $validated = $request->validate([
            'newRowData' => 'required|array',
            'originalPkValues' => 'required|array',
        ]);

        $newRowData = $validated['newRowData'];
        $originalPkValues = $validated['originalPkValues'];

        $db = null;
        try {

            $db = $this->setupDynamicConnection($connection, $databaseName);


            if (empty($originalPkValues)) {
                return Redirect::back()->with('error', 'Esta tabela não tem chave primária. Não é possível editar a linha.');
            }




            $query = $db->table($tableName);


            foreach ($originalPkValues as $column => $value) {
                $query->where($column, $value);
            }


            $affectedRows = $query->update($newRowData);

            return Redirect::back()->with('success', "$affectedRows linha(s) atualizada(s).");

        } catch (\Exception $e) {
            Log::error('Falha ao atualizar linha: ' . $e->getMessage());

            $sqlMessage = $e->errorInfo[2] ?? $e->getMessage();
            return Redirect::back()->with('error', 'Falha ao atualizar: ' . substr($sqlMessage, 0, 200));
        } finally {
            if ($db) DB::disconnect($db->getName());
        }
    }

    public function storeRow(Request $request, Connection $connection, $databaseName, $tableName)
    {
        $validated = $request->validate([
            'rowData' => 'required|array',
        ]);



        $rowData = $validated['rowData'];



        foreach ($rowData as $key => $value) {
            if ($value === '') {
                $rowData[$key] = null;
            }
        }

        $db = null;
        try {
            $db = $this->setupDynamicConnection($connection, $databaseName);


            $result = $db->table($tableName)->insert($rowData);

            if ($result) {
                return Redirect::back()->with('success', 'Linha inserida com sucesso.');
            } else {
                return Redirect::back()->with('error', 'Falha ao inserir linha (sem erro específico).');
            }

        } catch (\Exception $e) {
            Log::error('Falha ao inserir linha: ' . $e->getMessage());
            $sqlMessage = $e->errorInfo[2] ?? $e->getMessage();
            return Redirect::back()->with('error', 'Erro ao inserir: ' . substr($sqlMessage, 0, 200));
        } finally {
            if ($db) DB::disconnect($db->getName());
        }
    }

    public function destroyRow(Request $request, Connection $connection, $databaseName, $tableName)
    {

        $row = $request->input('row');
        if (!$row) {
            return Redirect::back()->with('error', 'Nenhum dado de linha recebido.');
        }

        $db = null;
        try {

            $db = $this->setupDynamicConnection($connection, $databaseName);


            $pkResults = $db->select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
            $primaryKeyColumns = collect($pkResults)->pluck('Column_name')->all();

            if (empty($primaryKeyColumns)) {
                return Redirect::back()->with('error', 'Esta tabela não tem chave primária. Não é possível deletar a linha.');
            }


            $whereClauses = [];
            foreach ($primaryKeyColumns as $pkColumn) {
                if (!isset($row[$pkColumn])) {
                    return Redirect::back()->with('error', "Valor da chave primária '{$pkColumn}' não encontrado.");
                }
                $whereClauses[$pkColumn] = $row[$pkColumn];
            }




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

    public function store(Request $request, Connection $connection, $databaseName)
    {
        $data = $request->validate([
            'name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'columns' => 'required|array|min:1',
            'columns.*.name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'columns.*.type' => 'required|string',
            'columns.*.length' => 'nullable|string',
            'columns.*.nullable' => 'boolean',
            'columns.*.ai' => 'boolean',
            'columns.*.pk' => 'boolean',
        ]);

        $tableName = $data['name'];
        $columns = $data['columns'];
        $db = null;

        try {
            $db = $this->setupDynamicConnection($connection, $databaseName);


            $sqlDefinitions = [];
            $primaryKeys = [];

            foreach ($columns as $col) {
                $def = "`{$col['name']}` {$col['type']}";


                if (!empty($col['length']) && !in_array(strtoupper($col['type']), ['TEXT', 'DATE', 'DATETIME', 'BOOLEAN'])) {
                    $def .= "({$col['length']})";
                }


                $def .= $col['nullable'] ? " NULL" : " NOT NULL";


                if ($col['ai']) {
                    $def .= " AUTO_INCREMENT";
                }


                if ($col['pk']) {
                    $primaryKeys[] = "`{$col['name']}`";
                }

                $sqlDefinitions[] = $def;
            }


            if (!empty($primaryKeys)) {
                $sqlDefinitions[] = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
            }

            $sqlBody = implode(', ', $sqlDefinitions);
            $sql = "CREATE TABLE `{$tableName}` ({$sqlBody}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";


            $db->statement($sql);

            return Redirect::back()->with('success', "Tabela '{$tableName}' criada com sucesso.");

        } catch (\Exception $e) {
            Log::error('Falha ao criar tabela: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Erro ao criar tabela: ' . $e->getMessage());
        } finally {
            if ($db) DB::disconnect($db->getName());
        }
    }

    /**
     * Exclui (Drop) uma tabela.
     * (NOVO MÉTODO)
     */
    public function destroy(Connection $connection, $databaseName, $tableName)
    {
        $db = null;
        try {
            $db = $this->setupDynamicConnection($connection, $databaseName);


            $db->statement("DROP TABLE `{$tableName}`");



            return Redirect::route('tables.index', [
                'connection' => $connection->id,
                'databaseName' => $databaseName
            ])->with('success', "Tabela '{$tableName}' excluída.");

        } catch (\Exception $e) {
            Log::error('Falha ao excluir tabela: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Erro ao excluir: ' . $e->getMessage());
        } finally {
            if ($db) DB::disconnect($db->getName());
        }
    }
}
