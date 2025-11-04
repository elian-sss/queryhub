<?php

/**
 * Script de Configuração do Banco de Dados para QueryHub
 *
 * Este script automatiza a configuração inicial do banco de dados.
 * 1. Verifica se .env existe (criado manualmente).
 * 2. Verifica se APP_KEY existe (e a gera se necessário).
 * 3. Cria o banco de dados MySQL se ele não existir.
 * 4. Executa as migrações (limpando o banco).
 * 5. Popula o banco com dados (criando o usuário Admin).
 */

// Define o caminho base como o diretório onde o script está
$basePath = __DIR__;

// --- PASSO 1: Verificar Composer ---
if (!file_exists($basePath . '/vendor/autoload.php')) {
    die("❌ Autoload do Composer não encontrado. Execute 'composer install' primeiro.\n");
}
require $basePath . '/vendor/autoload.php';

echo "🚀 Iniciando configuração do QueryHub...\n";

// --- PASSO 2: Carregar .env ---
if (!file_exists($basePath . '/.env')) {
    die("❌ Arquivo .env não encontrado. Copie .env.example para .env e configure seu banco de dados.\n");
}

// Carrega as variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->load();

// --- PASSO 3: Gerar Chave (se necessário) ---
if (!file_exists($basePath . '/artisan')) {
    die("❌ O arquivo 'artisan' do Laravel não foi encontrado.\n");
}

// Recarrega o .env para garantir que estamos lendo o arquivo
$dotenv->overload();
if (empty($_ENV['APP_KEY'])) {
    echo "🔑 Gerando chave da aplicação (APP_KEY)...\n";
    passthru('php ' . $basePath . '/artisan key:generate');
    echo "✅ Chave gerada.\n";

    // Recarrega o .env mais uma vez para pegar a chave recém-gerada
    $dotenv->overload();
}

// --- PASSO 4: Setup do Banco de Dados ---
echo "\n--- Iniciando Configuração do Banco de Dados ---\n";

$dbHost = $_ENV['DB_HOST'];
$dbPort = $_ENV['DB_PORT'];
$dbUser = $_ENV['DB_USERNAME'];
$dbPass = $_ENV['DB_PASSWORD'];
$dbName = $_ENV['DB_DATABASE'];

if (!$dbName) {
    die("❌ DB_DATABASE não está definido no seu .env. O script não pode continuar.\n");
}

try {
    $pdo = new PDO("mysql:host={$dbHost};port={$dbPort}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "📦 Verificando banco de dados '{$dbName}'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Banco de dados '{$dbName}' verificado/criado com sucesso!\n";

} catch (PDOException $e) {
    die("❌ Erro na conexão com MySQL: " . $e->getMessage() . "\n   Verifique suas credenciais em .env (DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD).\n");
}

// 5. Executa as migrações (limpa o banco)
echo "🔄 Executando migrações (migrate:fresh)...\n";
passthru('php ' . $basePath . '/artisan migrate:fresh --force');

// 6. Popula o banco de dados (chama os Seeders)
echo "\n🌱 Populando o banco (db:seed)...\n";
passthru('php ' . $basePath . '/artisan db:seed --force');

echo "\n✨ Configuração do QueryHub concluída com sucesso!\n";
echo "🎉 Você pode fazer login com:\n";
echo "   Usuário: admin@admin.com\n";
echo "   Senha:   password\n";

echo "\nPróximos passos:\n";
echo "1. npm install\n";
echo "2. npm run dev (em um terminal)\n";
echo "3. php artisan serve (em outro terminal)\n";

?>
