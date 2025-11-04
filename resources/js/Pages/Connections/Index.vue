<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    connections: Array,
});

const form = useForm({
    name: '',
    host: '127.0.0.1',
    port: 3306,
    database_user: '',
    database_password: '',
});

const submit = () => {
    form.post(route('connections.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Gerenciar Conexões" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gerenciar Conexões</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Nova Conexão</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Adicione uma nova conexão de banco de dados que o QueryHub irá gerenciar.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Nome (Apelido)" />
                                <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="host" value="Host" />
                                <TextInput id="host" type="text" v-model="form.host" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.host" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="port" value="Porta" />
                                <TextInput id="port" type="number" v-model="form.port" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.port" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="database_user" value="Usuário do Banco" />
                                <TextInput id="database_user" type="text" v-model="form.database_user" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.database_user" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="database_password" value="Senha do Banco" />
                                <TextInput id="database_password" type="password" v-model="form.database_password" class="mt-1 block w-full" />
                                <InputError :message="form.errors.database_password" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Salvar</PrimaryButton>
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Salvo.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Conexões Salvas</h2>
                        </header>
                        <ul v-if="connections.length > 0" class="mt-4 space-y-2">
                            <li v-for="conn in connections" :key="conn.id" class="p-2 border rounded">
                                {{ conn.name }} ({{ conn.database_user }}@{{ conn.host }})
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-gray-600">Nenhuma conexão cadastrada.</p>
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
