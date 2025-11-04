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

-----

## Instalação e Configuração Local

Siga estes passos para rodar o QueryHub em sua máquina local.

### 1\. Pré-requisitos

Certifique-se de que seu ambiente de desenvolvimento possui:

* **PHP 8.2** (exatamente)
* **Composer**
* **Node.js** (v18+) e **NPM**
* Um servidor de banco de dados **MySQL** ou **MariaDB** (para o *próprio* QueryHub)

### 2\. Passo a Passo

1.  **Clonar o Repositório**

    ```bash
    git clone https://github.com/elian-sss/queryhub queryhub
    cd queryhub
    ```

2.  **Instalar Dependências do PHP**

    ```bash
    composer install
    ```

    *(Opcional: Se você tiver o PHP 8.3 instalado globalmente, mas quiser usar o 8.2, pode ser necessário especificar: `php8.2 /usr/bin/composer install`)*

3.  **Configurar Arquivo de Ambiente**

    ```bash
    cp .env.example .env
    ```

4.  **Gerar Chave da Aplicação**

    ```bash
    php artisan key:generate
    ```

5.  **Configurar o `.env`**
    Abra o arquivo `.env` e configure a conexão com o banco de dados que **o QueryHub irá usar** para armazenar seus próprios usuários e conexões:

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=queryhub_app    # Sugestão de nome
    DB_USERNAME=root
    DB_PASSWORD=             # Senha do seu banco local
    ```

6.  **Rodar as Migrations**
    Isso criará as tabelas `users`, `connections`, `connection_user`, etc.

    ```bash
    php artisan migrate
    ```

6.  **Rodar o Seeder**
    Isso populará as tabelas `users`, `connections`, `connection_user`, etc.

    ```bash
    php artisan db:seed
    ```

7.  **Instalar Dependências do Node.js**

    ```bash
    npm install
    ```

8.  **Rodar os Servidores de Desenvolvimento**
    Você precisará de dois terminais abertos:

    * **Terminal 1 (Vite Frontend):**
      ```bash
      npm run dev
      ```
    * **Terminal 2 (Laravel Backend):**
      ```bash
      php artisan serve
      ```

## Uso Básico (Pós-Instalação)

1.  **Login:** Faça login com o usuário `admin@admin.com` e senha `password`.
2.  **Criar Conexão:** No menu de navegação, clique em **Conexões** (o link só aparece para Admins).
3.  **Formulário:** Preencha o formulário para adicionar uma nova conexão (ex: aponte para outro banco de dados de teste que você tenha na sua máquina, como `127.0.0.1`, `root`, `sua_senha`).
4.  **Acessar o Dashboard:** Clique em **Dashboard**.
5.  **Navegar:** A conexão que você criou aparecerá automaticamente na sidebar da esquerda (graças à lógica de auto-atribuição para Admins). Clique nela para começar a navegar pelos bancos, tabelas e dados.
