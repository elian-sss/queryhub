<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue'; // <-- 1. Importar Modal
import {Head, useForm} from '@inertiajs/vue3';
import {ref} from 'vue'; // <-- 2. Importar ref
import axios from 'axios'; // <-- 3. Importar axios
import { useToast } from 'vue-toastification';

const toast = useToast();

// --- Props (sem mudança) ---
const props = defineProps({
    connection: Object,
    allUsers: Array,
    assignedUserIds: Array,
});

// --- Formulário Principal (sem mudança) ---
const form = useForm({
    user_ids: props.assignedUserIds,
});

const submit = () => {
    form.patch(route('connections.permissions.update', props.connection.id), {
        preserveScroll: true
    });
};


// --- NOVA LÓGICA DO MODAL DE PERMISSÃO DE BANCO ---
const showDbModal = ref(false);
const isLoadingModal = ref(false);
const selectedUser = ref(null);
const allDatabases = ref([]);
const dbPermissionError = ref(null);

// Formulário separado para o modal
const dbPermissionForm = useForm({
    allowed_databases: [],
});

// 1. Função para fechar o modal e resetar o estado
const closeModal = () => {
    showDbModal.value = false;
    isLoadingModal.value = false;
    selectedUser.value = null;
    allDatabases.value = [];
    dbPermissionError.value = null;
    dbPermissionForm.reset();
};

// 2. Função chamada pelo botão "Gerenciar Bancos" (AGORA É ASYNC)
const manageDbPermissions = async (user) => {
    // Seta o estado inicial do modal
    selectedUser.value = user;
    isLoadingModal.value = true;
    showDbModal.value = true;
    dbPermissionError.value = null;
    dbPermissionForm.reset();

    try {
        // Chama nossa nova rota de API via axios
        const response = await axios.get(route('connections.users.db-permissions.edit', {
            connection: props.connection.id,
            user: user.id
        }));

        // Preenche os dados do modal com a resposta
        allDatabases.value = response.data.allDatabases;
        dbPermissionForm.allowed_databases = response.data.allowedDatabases;

    } catch (error) {
        console.error("Erro ao buscar permissões de banco:", error);
        dbPermissionError.value = error.response?.data?.error || "Não foi possível carregar os dados.";
    } finally {
        isLoadingModal.value = false;
    }
};

// 3. Função chamada pelo botão "Salvar" do modal
const submitDbPermissions = () => {
    dbPermissionForm.processing = true;
    dbPermissionError.value = null;

    axios.post(route('connections.users.db-permissions.update', {
        connection: props.connection.id,
        user: selectedUser.value.id
    }), {
        allowed_databases: dbPermissionForm.allowed_databases
    })
        .then(response => {
            closeModal();
            // --- 4. CHAMAR O TOAST MANUALMENTE ---
            toast.success('Permissões de banco atualizadas!');
        })
        .catch(error => {
            console.error("Erro ao salvar permissões:", error);
            dbPermissionError.value = error.response?.data?.error || "Não foi possível salvar as permissões.";
        })
        .finally(() => {
            dbPermissionForm.processing = false;
        });
};

</script>

<template>
    <Head :title="'Permissões - ' + connection.name"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gerenciar Permissões
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 sm:p-8">

                        <header>
                        </header>

                        <div class="mt-6 space-y-4">
                            <div v-for="user in allUsers" :key="user.id">
                                <div v-if="user.role === 'Developer'"
                                     class="flex items-center justify-between p-3 border dark:border-gray-700 rounded-lg">
                                    <label class="flex items-center cursor-pointer">
                                        <Checkbox
                                            v-model:checked="form.user_ids"
                                            :value="user.id"
                                        />
                                        <div class="ms-3">
                                            <span class="text-sm text-gray-800 dark:text-gray-200">{{
                                                    user.name
                                                }}</span>
                                            <span class="ms-2 text-xs text-gray-500 dark:text-gray-400">({{
                                                    user.email
                                                }})</span>
                                        </div>
                                    </label>
                                    <div>
                                        <SecondaryButton
                                            @click.prevent="manageDbPermissions(user)"
                                            title="Gerenciar permissões de banco de dados"
                                        >
                                            Gerenciar Bancos
                                        </SecondaryButton>
                                    </div>
                                </div>
                                <div v-else
                                     class="flex items-center p-3 border dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-900 opacity-60">
                                </div>
                            </div>
                            <InputError :message="form.errors.user_ids" class="mt-2"/>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <PrimaryButton :disabled="form.processing">Salvar Permissões</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <Modal :show="showDbModal" @close="closeModal">
            <div class="p-6 dark:bg-gray-800">

                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Gerenciar Bancos para:
                    <span class="font-bold">{{ selectedUser?.name }}</span>
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Selecione os bancos que este usuário pode acessar na conexão: {{ connection.name }}
                </p>

                <div v-if="isLoadingModal" class="my-10 text-center">
                    <p class="text-gray-600 dark:text-gray-400">Carregando bancos de dados...</p>
                </div>

                <form v-else-if="!dbPermissionError" @submit.prevent="submitDbPermissions" class="mt-6">
                    <div class="max-h-60 overflow-y-auto space-y-3 p-2 border dark:border-gray-700 rounded-md">
                        <label v-for="dbName in allDatabases" :key="dbName"
                               class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                            <Checkbox
                                v-model:checked="dbPermissionForm.allowed_databases"
                                :value="dbName"
                            />
                            <span class="ms-3 text-sm text-gray-800 dark:text-gray-200 font-mono">{{ dbName }}</span>
                        </label>
                        <p v-if="allDatabases.length === 0"
                           class="text-sm text-gray-500 dark:text-gray-400 text-center p-2">
                            Nenhum banco de dados encontrado nesta conexão.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-4">
                        <SecondaryButton @click="closeModal"> Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="dbPermissionForm.processing">
                            Salvar Permissões do Banco
                        </PrimaryButton>
                    </div>
                </form>

                <div v-if="dbPermissionError" class="mt-6">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ dbPermissionError }}</p>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeModal"> Fechar</SecondaryButton>
                    </div>
                </div>

            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
