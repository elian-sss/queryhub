# QueryHub

QueryHub é um gerenciador de banco de dados moderno e seguro, baseado na web, construído com Laravel 10 e Vue.js. Ele serve como uma alternativa a ferramentas como o phpMyAdmin, fornecendo uma interface limpa para desenvolvedores interagirem com bancos de dados MySQL/MariaDB.

O diferencial do QueryHub é seu sistema de permissões, onde um Administrador central controla quais usuários (Developers) podem acessar quais conexões de banco de dados.

## Funcionalidades (Estado Atual)

* **Autenticação de Usuários:** Sistema completo de login e registro (via Laravel Breeze).
* **Papéis de Usuário:** Diferenciação entre `Administrator` e `Developer`.
* **Gerenciamento de Conexões (CRUD Admin):**
    * Administradores podem criar, listar, atualizar e deletar conexões com bancos de dados externos.
    * As senhas das conexões são armazenadas de forma segura (criptografadas).
* **Atribuição Automática para Admins:** Novas conexões são automaticamente atribuídas a todos os usuários `Administrator` existentes.
* **Navegador de Banco de Dados (Dashboard):**
    * Layout de 4 colunas: Conexões \> Bancos \> Tabelas \> Dados.
    * Lista apenas as conexões permitidas para o usuário logado.
    * Lista os bancos de dados de uma conexão selecionada.
    * Lista as tabelas de um banco de dados selecionado.
    * Exibe os dados de uma tabela selecionada com paginação (100 registros por página).

## Stack de Tecnologia

* **Backend:** PHP 8.2, Laravel 10
* **Frontend:** Vue.js 3, Inertia.js
* **Estilização:** Tailwind CSS 3 (com suporte a Light/Dark Mode)
* **Autenticação:** Laravel Breeze (VILT Stack)
* **Banco de Dados (Aplicação):** MySQL / MariaDB
* **Bancos de Dados (Gerenciados):** MySQL / MariaDB

---

## Instalação e Configuração Local

Siga estes passos para rodar o QueryHub em sua máquina local.

### 1. Pré-requisitos

* **PHP 8.2** (exatamente)
* **Composer**
* **Node.js** (v18+) e **NPM**
* Um servidor de banco de dados **MySQL** ou **MariaDB**

### 2. Instalação (Método Rápido)

Este método usa um script PHP para automatizar a maior parte da configuração.

1.  **Clonar o Repositório**
    ```bash
    git clone https://github.com/elian-sss/queryhub queryhub
    cd queryhub
    ```

2.  **Instalar Dependências do PHP (Obrigatório)**
    O script de setup precisa dos pacotes do Composer para rodar.
    ```bash
    composer install
    ```

3.  **Configurar Arquivo de Ambiente (Obrigatório)**
    Copie o arquivo de exemplo.
    ```bash
    cp .env.example .env
    ```

4.  **EDITAR O `.env` (Obrigatório)**
    Abra o arquivo `.env` que você acabou de criar e **configure suas credenciais de banco de dados**:
    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=queryhub_app
    DB_USERNAME=root
    DB_PASSWORD=             # Sua senha do banco
    ```

5.  **Executar o Script de Setup**
    Este comando fará o resto: gerar a `APP_KEY`, criar o banco, rodar as migrações e criar o usuário admin.
    ```bash
    php setup.php
    ```

6.  **Instalar Dependências do Node.js**
    ```bash
    npm install
    ```

7.  **Rodar os Servidores de Desenvolvimento**
    * Terminal 1: `npm run dev`
    * Terminal 2: `php artisan serve`

8.  **Pronto!** Você pode fazer login diretamente com:
    * **Usuário:** `admin@admin.com`
    * **Senha:** `password`

## Uso Básico (Pós-Instalação)

1.  **Login:** Faça login com o usuário Admin.
2.  **Criar Conexão:** No menu de navegação, clique em **Conexões**.
3.  **Formulário:** Preencha o formulário para adicionar uma nova conexão de teste.
4.  **Acessar o Dashboard:** Clique em **Dashboard**. A nova conexão aparecerá automaticamente.
