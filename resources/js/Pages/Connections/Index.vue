<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    connections: Array,
});

const createForm = useForm({
    name: '',
    host: '127.0.0.1',
    port: 3306,
    database_user: '',
    database_password: '',
});

const submitCreate = () => {
    createForm.post(route('connections.store'), {
        onSuccess: () => createForm.reset(),
    });
};

const showEditModal = ref(false);

const editForm = useForm({
    id: null,
    name: '',
    host: '',
    port: 3306,
    database_user: '',
    database_password: '',
});

const openEditModal = (connection) => {
    editForm.id = connection.id;
    editForm.name = connection.name;
    editForm.host = connection.host;
    editForm.port = connection.port;
    editForm.database_user = connection.database_user;
    editForm.database_password = '';
    editForm.errors = {};
    showEditModal.value = true;
};

const closeModal = () => {
    showEditModal.value = false;
};

const submitUpdate = () => {
    editForm.patch(route('connections.update', editForm.id), {
        onSuccess: () => {
            closeModal();
            editForm.reset();
        },
        preserveScroll: true,
    });
};

const confirmDeletion = (connection) => {
    if (window.confirm('Tem certeza que deseja remover esta conexão?')) {
        router.delete(route('connections.destroy', connection.id), {
            preserveScroll: true,
            onSuccess: () => {
            },
        });
    }
};
</script>

<template>
    <Head title="Gerenciar Conexões" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gerenciar Conexões</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nova Conexão</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Adicione uma nova conexão de banco de dados que o QueryHub irá gerenciar.
                            </p>
                        </header>

                        <form @submit.prevent="submitCreate" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Nome (Apelido)" />
                                <TextInput id="name" type="text" v-model="createForm.name" class="mt-1 block w-full" required />
                                <InputError :message="createForm.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="host" value="Host" />
                                <TextInput id="host" type="text" v-model="createForm.host" class="mt-1 block w-full" required />
                                <InputError :message="createForm.errors.host" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="port" value="Porta" />
                                <TextInput id="port" type="number" v-model="createForm.port" class="mt-1 block w-full" required />
                                <InputError :message="createForm.errors.port" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="database_user" value="Usuário do Banco" />
                                <TextInput id="database_user" type="text" v-model="createForm.database_user" class="mt-1 block w-full" required />
                                <InputError :message="createForm.errors.database_user" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="database_password" value="Senha do Banco" />
                                <TextInput id="database_password" type="password" v-model="createForm.database_password" class="mt-1 block w-full" />
                                <InputError :message="createForm.errors.database_password" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="createForm.processing">Salvar</PrimaryButton>
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="createForm.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Salvo.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Conexões Salvas</h2>
                        </header>
                        <div v-if="connections.length > 0" class="mt-4 space-y-4">
                            <div v-for="conn in connections" :key="conn.id" class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ conn.name }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 block">{{ conn.database_user }}@{{ conn.host }}:{{ conn.port }}</span>
                                </div>
                                <div class="space-x-2 flex-shrink-0">
                                    <Link 
                                        :href="route('connections.permissions.edit', conn.id)"
                                        as="button"
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-500 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            Permissões
                                        </Link>
                                    <SecondaryButton @click="openEditModal(conn)">
                                        Editar
                                    </SecondaryButton>
                                    <DangerButton @click="confirmDeletion(conn)">
                                        Deletar
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-gray-600 dark:text-gray-400">Nenhuma conexão cadastrada.</p>
                    </section>
                </div>
            </div>
        </div>

        <Modal :show="showEditModal" @close="closeModal">
            <form @submit.prevent="submitUpdate" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Editar Conexão: {{ editForm.name }}
                </h2>

                <div class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="edit_name" value="Nome (Apelido)" />
                        <TextInput id="edit_name" type="text" v-model="editForm.name" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_host" value="Host" />
                        <TextInput id="edit_host" type="text" v-model="editForm.host" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.host" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_port" value="Porta" />
                        <TextInput id="edit_port" type="number" v-model="editForm.port" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.port" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_database_user" value="Usuário do Banco" />
                        <TextInput id="edit_database_user" type="text" v-model="editForm.database_user" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.database_user" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_database_password" value="Nova Senha" />
                        <TextInput id="edit_database_password" type="password" v-model="editForm.database_password" class="mt-1 block w-full" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">(Deixe em branco para não alterar)</p>
                        <InputError :message="editForm.errors.database_password" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>

                    <PrimaryButton
                        class="ms-3"
                        :class="{ 'opacity-25': editForm.processing }"
                        :disabled="editForm.processing"
                    >
                        Salvar Alterações
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>
