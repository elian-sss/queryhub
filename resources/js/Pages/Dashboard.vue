<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    userConnections: Array,
    selectedConnectionId: { type: Number, default: null },
    databases: { type: Array, default: () => [] },
    selectedDatabaseName: { type: String, default: null },
    tables: { type: Array, default: () => [] },
    selectedTableName: { type: String, default: null }, // Novo
    tableData: { // Novo
        type: Object,
        default: () => ({ columns: [], rows: [] })
    },
    connectionError: { type: String, default: null },
});

const truncate = (value, length = 50) => {
    if (value === null) return 'NULL';
    if (value === undefined) return '...';

    let str = String(value);
    if (str.length > length) {
        return str.substring(0, length) + '...';
    }
    return str;
};

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                QueryHub
            </h2>
        </template>

        <div class="flex h-[calc(100vh-65px)]">

            <nav class="w-64 bg-white dark:bg-gray-800 border-r dark:border-gray-700 p-4 space-y-2 overflow-y-auto flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Minhas Conexões
                </h3>
                <ul v-if="userConnections.length > 0">
                    <li v-for="conn in userConnections" :key="conn.id">
                        <Link
                            :href="route('databases.index', { connection: conn.id })"
                            :class="{
                                'bg-gray-200 dark:bg-gray-700 font-bold': conn.id === selectedConnectionId,
                                'block p-2 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': true
                            }"
                        >
                            {{ conn.name }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="w-72 bg-gray-100 dark:bg-gray-800/50 border-r dark:border-gray-700 p-4 overflow-y-auto flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Bancos de Dados
                </h3>
                <ul class="space-y-1" v-if="databases.length > 0">
                    <li v-for="db in databases" :key="db">
                        <Link
                            :href="route('tables.index', { connection: selectedConnectionId, databaseName: db })"
                            :class="{
                                'bg-blue-100 dark:bg-blue-900 font-bold text-blue-700 dark:text-blue-300': db === selectedDatabaseName,
                                'block p-2 rounded font-mono text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700': true
                            }"
                        >
                            {{ db }}
                        </Link>
                    </li>
                </ul>
            </div>

            <div class="w-72 bg-gray-50 dark:bg-gray-800 border-r dark:border-gray-700 p-4 overflow-y-auto flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Tabelas
                </h3>
                <ul class="space-y-1" v-if="tables.length > 0">
                    <li v-for="table in tables" :key="table">
                        <Link
                            :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: table })"
                            :class="{
                                'bg-green-100 dark:bg-green-900 font-bold text-green-700 dark:text-green-300': table === selectedTableName,
                                'block p-2 rounded font-mono text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700': true
                            }"
                        >
                            {{ table }}
                        </Link>
                    </li>
                </ul>
            </div>

            <main class="flex-1 p-6 bg-gray-100 dark:bg-gray-900 overflow-y-auto">

                <div v-if="connectionError">
                    <h2 class="text-2xl font-bold text-red-600 dark:text-red-400">Erro de Conexão</h2>
                    <pre class="mt-4 p-4 bg-gray-200 dark:bg-gray-800 rounded text-red-700 dark:text-red-300 overflow-x-auto">{{ connectionError }}</pre>
                </div>

                <div v-else-if="selectedTableName">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        Mostrando dados de <span class="text-green-600 font-mono">{{ selectedTableName }}</span>
                    </h2>

                    <div v-if="tableData.rows.length > 0" class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th v-for="col in tableData.columns" :key="col" scope="col" class="px-6 py-3 font-mono">
                                    {{ col }}
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in tableData.rows" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td v-for="col in tableData.columns" :key="col" class="px-6 py-4 font-mono">
                                        <span :class="{'text-gray-400 dark:text-gray-500 italic': row[col] === null}">
                                            {{ truncate(row[col]) }}
                                        </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="mt-4 text-gray-600 dark:text-gray-400">
                        Tabela vazia.
                    </p>
                </div>

                <div v-else-if="!selectedConnectionId">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Bem-vindo ao QueryHub
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Selecione uma conexão na barra lateral para começar.
                    </p>
                </div>

                <div v-else>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Selecione um item
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Escolha um banco de dados e uma tabela nas colunas à esquerda para ver os dados.
                    </p>
                </div>

            </main>
        </div>
    </AuthenticatedLayout>
</template>
