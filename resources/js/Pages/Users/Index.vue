<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'Developer',
});

const openCreateModal = () => {
    form.reset();
    showCreateModal.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    form.password_confirmation = '';
    showEditModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedUser.value = null;
    form.reset();
};

const submitCreate = () => {
    form.post(route('users.store'), {
        onSuccess: () => closeModal(),
    });
};

const submitUpdate = () => {
    form.patch(route('users.update', selectedUser.value.id), {
        onSuccess: () => closeModal(),
    });
};

const submitDelete = (user) => {
    if (confirm(`Tem certeza que deseja excluir o usuário ${user.name}?`)) {
        useForm({}).delete(route('users.destroy', user.id));
    }
};
</script>

<template>
    <Head title="Gerenciar Usuários" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gerenciar Usuários</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                        <div class="flex justify-end mb-4">
                            <PrimaryButton @click="openCreateModal">Criar Usuário</PrimaryButton>
                        </div>

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Nome</th>
                                        <th scope="col" class="px-6 py-3">Email</th>
                                        <th scope="col" class="px-6 py-3">Nível</th>
                                        <th scope="col" class="px-6 py-3">
                                            <span class="sr-only">Ações</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users" :key="user.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ user.name }}</th>
                                        <td class="px-6 py-4">{{ user.email }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="{'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': user.role === 'Administrator', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': user.role === 'Developer'}" class="text-xs font-medium me-2 px-2.5 py-0.5 rounded">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <SecondaryButton @click="openEditModal(user)" class="!px-2 !py-1 !text-xs">Editar</SecondaryButton>
                                            <DangerButton @click="submitDelete(user)" class="!px-2 !py-1 !text-xs">Excluir</DangerButton>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="showCreateModal" @close="closeModal">
            <form @submit.prevent="submitCreate" class="p-6 dark:bg-gray-800">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Criar Novo Usuário</h2>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="create_name" value="Nome" />
                        <TextInput id="create_name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create_email" value="Email" />
                        <TextInput id="create_email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="create_role" value="Nível" />
                        <select id="create_role" v-model="form.role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="Developer">Developer</option>
                            <option value="Administrator">Administrator</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Criar</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="closeModal">
            <form @submit.prevent="submitUpdate" class="p-6 dark:bg-gray-800">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Editar Usuário</h2>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="edit_name" value="Nome" />
                        <TextInput id="edit_name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_email" value="Email" />
                        <TextInput id="edit_email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_password" value="Nova Senha (deixe em branco para não alterar)" />
                        <TextInput id="edit_password" v-model="form.password" type="password" class="mt-1 block w-full" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_password_confirmation" value="Confirmar Nova Senha" />
                        <TextInput id="edit_password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" />
                    </div>
                     <div>
                        <InputLabel for="edit_role" value="Nível" />
                        <select id="edit_role" v-model="form.role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="Developer">Developer</option>
                            <option value="Administrator">Administrator</option>
                        </select>
                         <InputError :message="form.errors.role" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Salvar Alterações</PrimaryButton>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>
