<x-mail::message>
# Bem-vindo ao QueryHub, {{ $user->name }}!

Sua conta foi criada com sucesso. Abaixo estão suas credenciais de acesso temporárias.

**Email:** {{ $user->email }}
**Senha:** {{ $tempPassword }}

Por motivos de segurança, você será solicitado a alterar sua senha no seu primeiro login.

<x-mail::button :url="route('login')">
Acessar o Sistema
</x-mail::button>

Obrigado,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
