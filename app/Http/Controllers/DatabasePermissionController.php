<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\DatabasePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabasePermissionController extends Controller
{
    private function setupDynamicConnection(Connection $connection, $databaseName = null)
    {
        // Segurança: O admin (usuário logado) pode gerenciar esta conexão?
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
     * Mostra os dados para o modal de permissão.
     * Retorna JSON.
     */
    public function edit(Connection $connection, User $user)
    {
        $db_layout = null;
        $allDatabases = [];
        $allowedDatabases = [];

        try {
            // 1. Conecta SEM banco para pegar a lista de todos os bancos
            $db_layout = $this->setupDynamicConnection($connection);
            $results = $db_layout->select('SHOW DATABASES');
            $excludedDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
            $allDatabases = collect($results)
                ->map(fn($db) => $db->Database)
                ->filter(fn($dbName) => !in_array($dbName, $excludedDbs))
                ->values()
                ->all();

            // 2. Busca as permissões que o usuário JÁ TEM
            $allowedDatabases = DatabasePermission::where('user_id', $user->id)
                ->where('connection_id', $connection->id)
                ->pluck('database_name')
                ->all();

        } catch (\Exception $e) {
            Log::error('Falha ao buscar bancos para permissão: ' . $e->getMessage());
            return response()->json(['error' => 'Falha ao conectar: ' . $e->getMessage()], 500);
        } finally {
            if ($db_layout) DB::disconnect($db_layout->getName());
        }

        // Retorna os dados como JSON para o modal
        return response()->json([
            'allDatabases' => $allDatabases,
            'allowedDatabases' => $allowedDatabases,
        ]);
    }

    /**
     * Salva as permissões de banco de dados para um usuário.
     * Recebe JSON, retorna JSON.
     */
    public function update(Request $request, Connection $connection, User $user)
    {
        $request->validate([
            'allowed_databases' => 'present|array'
        ]);

        $allowedDbs = $request->input('allowed_databases', []);

        try {
            DB::transaction(function () use ($user, $connection, $allowedDbs) {
                // 1. Limpa todas as permissões de banco antigas para este usuário/conexão
                DatabasePermission::where('user_id', $user->id)
                    ->where('connection_id', $connection->id)
                    ->delete();

                // 2. Cria os dados para inserção em massa
                $permissionsToInsert = [];
                $now = now();
                foreach ($allowedDbs as $dbName) {
                    $permissionsToInsert[] = [
                        'user_id' => $user->id,
                        'connection_id' => $connection->id,
                        'database_name' => $dbName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // 3. Insere as novas permissões
                if (!empty($permissionsToInsert)) {
                    DatabasePermission::insert($permissionsToInsert);
                }
            });
        } catch (\Exception $e) {
            Log::error('Falha ao salvar permissões de banco: ' . $e->getMessage());
            return response()->json(['error' => 'Falha ao salvar permissões.'], 500);
        }

        return response()->json(['success' => 'Permissões atualizadas.']);
    }
}
