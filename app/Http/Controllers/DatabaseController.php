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
     * Mostra a lista de bancos de dados
     */
    public function index(Connection $connection)
    {
        $layoutData = $this->getLayoutData($connection);

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'activeTab' => 'tables',
        ]);
    }

    public function store(Request $request, Connection $connection)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
        ]);

        $dbName = $request->input('name');
        $db = null;

        try {
            $db = $this->setupDynamicConnection($connection);
            $db->statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            return Redirect::back()->with('success', "Banco de dados '{$dbName}' criado com sucesso.");

        } catch (\Exception $e) {
            Log::error('Falha ao criar banco de dados: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Erro ao criar banco: ' . $e->getMessage());
        } finally {
            if ($db) DB::disconnect($db->getName());
        }
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

                if (empty($layoutData)) {
                    try { $layoutData = $this->getLayoutData($connection); } catch (\Exception $e2) {}
                }
            } finally {
                if ($db) DB::disconnect($db->getName());
            }


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



            try {
                $db = $this->setupDynamicConnection($connection, $databaseName);
                $db->unprepared($sql);


                return Redirect::route('database.showSql', [
                    'connection' => $connection->id,
                    'databaseName' => $databaseName,
                ])->with('success', "Script SQL executado com sucesso.");

            } catch (\Exception $e) {
                Log::error('Falha na query SQL (AFFECTING): ' . $e->getMessage());


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

            $layoutData = $this->getLayoutData($connection);
        } catch (\Exception $e) {
            Log::error('Falha na conexão (showSql): ' . $e->getMessage());
            $error = 'Falha ao conectar: ' . $e->getMessage();
        }

        return Inertia::render('Dashboard', [
            ...$layoutData,
            'selectedDatabaseName' => $databaseName,
            'connectionError' => $error,
            'activeTab' => 'sql',
        ]);
    }

    public function export(Connection $connection, $databaseName)
    {


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

        $filename = $databaseName . '_' . date('Y-m-d_H-i-s') . '.sql';

        return response()->streamDownload(function () use ($dynamicConnectionName) {
            $db = DB::connection($dynamicConnectionName);


            echo "-- QueryHub Dump\n";
            echo "-- Banco de Dados: `$dynamicConnectionName`\n";
            echo "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";


            $tables = $db->select('SHOW TABLES');

            $keyName = array_key_first((array)$tables[0]);

            foreach ($tables as $tableObj) {
                $table = $tableObj->$keyName;


                echo "-- Estrutura para tabela `$table`\n";
                echo "DROP TABLE IF EXISTS `$table`;\n";

                $createTable = $db->select("SHOW CREATE TABLE `$table`");


                $createTableSql = array_values((array)$createTable[0])[1];

                echo $createTableSql . ";\n\n";


                echo "-- Despejando dados para a tabela `$table`\n";


                $rows = $db->table($table)->cursor();

                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array)$row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } elseif (is_numeric($value)) {
                            $values[] = $value;
                        } else {

                            $val = str_replace(["\\", "'"], ["\\\\", "\\'"], $value);

                            $val = str_replace(["\r\n", "\n", "\r"], ["\\r\\n", "\\n", "\\r"], $val);
                            $values[] = "'$val'";
                        }
                    }

                    $valuesString = implode(", ", $values);
                    echo "INSERT INTO `$table` VALUES ($valuesString);\n";
                }
                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";

            DB::disconnect($dynamicConnectionName);

        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function import(Request $request, Connection $connection, $databaseName)
    {
        $request->validate([
            'sql_file' => 'required|file|extensions:sql|max:102400',
        ]);

        $db = null;
        try {
            $db = $this->setupDynamicConnection($connection, $databaseName);


            $db->statement('SET FOREIGN_KEY_CHECKS=0;');


            $sql = $request->file('sql_file')->get();
            $db->unprepared($sql);


            $db->statement('SET FOREIGN_KEY_CHECKS=1;');

            return Redirect::back()->with('success', 'Arquivo SQL importado e executado com sucesso!');

        } catch (\Exception $e) {
            Log::error("Falha na importação de SQL: " . $e->getMessage());


            if ($db) {
                try {
                    $db->statement('SET FOREIGN_KEY_CHECKS=1;');
                } catch (\Exception $ex) {
                    Log::error("Não foi possível reativar FOREIGN_KEY_CHECKS: " . $ex->getMessage());
                }
            }

            return Redirect::back()->with('error', 'Erro ao importar arquivo: ' . $e->getMessage());
        } finally {
            if ($db) {
                DB::disconnect($db->getName());
            }
        }
    }
}
