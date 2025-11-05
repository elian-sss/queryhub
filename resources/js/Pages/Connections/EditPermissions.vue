<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    connection: Object,
    allUsers: Array,
    assignedUserIds: Array,
});

const form = useForm({
    user_ids: props.assignedUserIds,
});

const submit = () => {
    form.patch(route('connections.permissions.update', props.connection.id), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head :title="'Permissões - ' + connection.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gerenciar Permissões
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 sm:p-8">
                        
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Editando permissões para: {{ connection.name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Selecione quais usuários terão acesso a esta conexão. 
                                Administradores sempre têm acesso.
                            </p>
                        </header>

                        <div class="mt-6 space-y-4">
                            
                            <div v-for="user in allUsers" :key="user.id">
                                
                                <label v-if="user.role === 'Developer'" class="flex items-center p-3 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <Checkbox 
                                        v-model:checked="form.user_ids"
                                        :value="user.id"
                                    />
                                    <span class="ms-3 text-sm text-gray-800 dark:text-gray-200">{{ user.name }}</span>
                                    <span class="ms-2 text-xs text-gray-500 dark:text-gray-400">({{ user.email }})</span>
                                </label>
                                
                                <div v-else class="flex items-center p-3 border dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-900 opacity-60">
                                    <Checkbox :checked="true" :disabled="true" />
                                    <span class="ms-3 text-sm text-gray-800 dark:text-gray-200">{{ user.name }}</span>
                                    <span class="ms-2 text-xs text-gray-500 dark:text-gray-400">({{ user.email }} - Admin)</span>
                                </div>
                            </div>
                            
                            <InputError :message="form.errors.user_ids" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <PrimaryButton :disabled="form.processing">Salvar Permissões</PrimaryButton>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Salvo.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>