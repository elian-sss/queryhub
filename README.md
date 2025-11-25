# QueryHub

QueryHub é um gerenciador de banco de dados baseado na web, construído com Laravel 10 e Vue 3 (Inertia). Ele facilita o gerenciamento e a inspeção de bancos MySQL/MariaDB e adiciona controles de acesso por papéis.

**Este README foi atualizado para incluir instruções de setup, informações sobre as funcionalidades recentes (importação .sql e gerenciamento de usuários) e notas de configuração de e-mail.**

## Funcionalidades Principais

- Autenticação de usuários via Laravel Breeze.
- Papéis: `Administrator` e `Developer` (controle de acesso por conexão).
- Gerenciamento de conexões (CRUD) por Administradores.
- Dashboard com navegação: Conexões → Bancos → Tabelas → Dados.
- Importação de arquivos `.sql` via Dashboard (executa SQL bruto no banco selecionado).
- Exportação de esquema e dados (dump simples) a partir do Dashboard.
- Gerenciamento de usuários (somente `Administrator`): criar/editar/remover.
- Criação automática de senha temporária ao criar um novo usuário; e-mail de boas-vindas enviado e obrigatoriedade de troca de senha no primeiro login.

## Avisos Importantes

- A funcionalidade de importação executa SQL bruto (`unprepared`) no banco de dados selecionado. Use com cautela e apenas em ambientes confiáveis.
- Configure backups regulares antes de permitir importações em produção.
- O envio de e-mail (boas-vindas, reset de senha) depende das variáveis de `MAIL_*` no `.env` (ex.: SMTP do Gmail com App Password).

---

## Pré-requisitos

- PHP 8.2
- Composer
- Node.js (v18+) e NPM
- MySQL / MariaDB

---

## Passos de Instalação (local)

### Método Rápido (Recomendado com setup.php)

1. Clone o repositório:

```powershell
git clone https://github.com/elian-sss/queryhub queryhub
cd queryhub
```

2. Instale as dependências:

```powershell
composer install
npm install
```

3. Copie o arquivo `.env`:

```powershell
copy .env.example .env
```

4. **Edite o `.env`** e configure pelo menos:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=queryhub_app
DB_USERNAME=root
DB_PASSWORD=
```

5. **Execute o script de setup** (faz tudo automaticamente):

```powershell
php setup.php
```

Este script fará:
- Gerar a chave da aplicação (`APP_KEY`)
- Criar o banco de dados (se não existir)
- Executar todas as migrações (incluindo `password_change_required`)
- Popular o banco com o admin inicial

6. Compile os assets e rodando o servidor:

```powershell
npm run dev
php artisan serve
```

7. Acesse http://127.0.0.1:8000 e faça login:
   - **Email:** `admin@admin.com`
   - **Senha:** `password`

---

### Método Manual (sem setup.php)

Se preferir fazer manualmente, siga os passos abaixo:

1. Clone e configure como acima (passos 1-4).

2. Gere a chave manualmente:

```powershell
php artisan key:generate
php artisan migrate
```

3. Crie um admin via tinker:

```powershell
php artisan tinker
>>> \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => bcrypt('password'), 'role' => 'Administrator', 'password_change_required' => false]);
>>> exit
```

4. Continue com os assets e servidor (passo 6 acima).

---

### Configurar SMTP (e-mail)

Adicione ao seu `.env` as variáveis de e-mail. Exemplo usando Gmail:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu.email@gmail.com
MAIL_PASSWORD=seu_app_password_aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu.email@gmail.com
MAIL_FROM_NAME="QueryHub"
```

**Para usar Gmail:**
1. Ative a autenticação de 2 fatores na sua conta Google.
2. Gere um "App Password" em https://myaccount.google.com/apppasswords
3. Use esse app password no `.env` em `MAIL_PASSWORD`.

**Para testes locais (sem e-mail real):**
Configure `MAIL_MAILER=log` para gravar os e-mails nos logs da aplicação (`storage/logs/`).

---

## Notas sobre usuários e e-mail

- Quando um Administrador cria um novo usuário, o sistema gera uma senha temporária e envia um e-mail de boas-vindas com as credenciais.
- No primeiro login com essa senha temporária o sistema força a troca de senha (`password_change_required`).
- Para que os e-mails sejam enviados corretamente, configure as variáveis `MAIL_*` no `.env` e verifique se o servidor SMTP permite o envio (no caso do Gmail, um App Password).

---

## Importação de arquivos .sql

- A tela do Dashboard permite enviar um arquivo `.sql` para ser executado no banco de dados selecionado.
- Validação do upload aceita arquivos com extensão `.sql`.
- O SQL é executado diretamente no banco (com `FOREIGN_KEY_CHECKS` temporariamente desabilitado durante a execução). Faça backup antes de usar esta funcionalidade em produção.

---

## Seeders e criação de Admin inicial

O `setup.php` já cria o admin inicial automaticamente. Se desejar criar mais usuários admin via console:

```powershell
php artisan tinker
>>> \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => bcrypt('password'), 'role' => 'Administrator', 'password_change_required' => false]);
>>> exit
```

Ou via interface gráfica: após logar como admin, vá para o menu **Usuários** e crie novos usuários. O sistema gerará uma senha temporária automaticamente e enviará um e-mail de boas-vindas.

---

## Testes e Verificações Locais

- Verifique migrações:

```powershell
php artisan migrate:status
```

- Para testar envio de e-mail localmente sem SMTP, considere usar `mailtrap.io` ou configurar `MAIL_MAILER=log` para gravar e-mails nos logs.

---

## Boas Práticas e Segurança

- Não conceda privilégios de importação a usuários não confiáveis.
- Mantenha backups das bases antes de executar imports.
- Se for usar em produção, considere colocar o serviço de e-mail em fila (jobs) para não bloquear requisições.

---

## Próximos Passos / Sugestões

- Adicionar testes automatizados para fluxos de autenticação e importação.
- Implementar fila para envio de e-mails (Redis/Database + Laravel Queue).
- Adicionar logs e auditoria para importações de SQL.

---

## Contribuição

Sinta-se à vontade para abrir issues e pull requests. Consulte as guidelines do projeto antes de enviar alterações maiores.

---

## Contato

Desenvolvedor: Elian


