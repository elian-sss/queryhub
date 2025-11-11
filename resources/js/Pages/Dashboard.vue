<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    userConnections: Array,
    selectedConnectionId: { type: Number, default: null },
    databases: { type: Array, default: () => [] },
    selectedDatabaseName: { type: String, default: null },
    tables: { type: Array, default: () => [] },
    selectedTableName: { type: String, default: null },
    tableData: {
        type: Object,
        default: () => ({ columns: [], rowsPaginator: { data: [], links: [] } })
    },
    tableStructure: {
        type: Array,
        default: () => []
    },
    connectionError: { type: String, default: null },

    // Novas props para a aba SQL
    activeTab: { type: String, default: null }, // 'tables', 'data', 'sql'
    sqlQuery: { type: String, default: '' },
    sqlResults: { type: Array, default: null },
    sqlAffectedRows: { type: Number, default: null },
});

// Helper para truncar dados
const truncate = (value, length = 50) => {
    if (value === null) return 'NULL';
    let str = String(value);
    if (str.length > length) return str.substring(0, length) + '...';
    return str;
};

// Computar se a tabela de dados tem linhas
const hasDataRows = computed(() => {
    return props.tableData.rowsPaginator && props.tableData.rowsPaginator.data.length > 0;
});

// Computar se a query SQL retornou linhas
const hasSqlResults = computed(() => {
    return props.sqlResults && props.sqlResults.length > 0;
});

// Computar colunas da query SQL
const sqlResultColumns = computed(() => {
    return hasSqlResults.value ? Object.keys(props.sqlResults[0]) : [];
});

// Formulário para a aba SQL
const sqlForm = useForm({
    query: props.sqlQuery,
});

const submitSql = () => {
    sqlForm.post(route('database.executeSql', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName
    }), {
        preserveState: false, // Recarregar props para obter resultados
        preserveScroll: true,
    });
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

            <main class="flex-1 p-6 bg-gray-50 dark:bg-gray-900 overflow-y-auto">

                <div v-if="connectionError">
                    <h2 class="text-2xl font-bold text-red-600 dark:text-red-400">Erro</h2>
                    <pre class="mt-4 p-4 bg-gray-200 dark:bg-gray-800 rounded text-red-700 dark:text-red-300 overflow-x-auto">{{ connectionError }}</pre>
                </div>

                <div v-if="selectedDatabaseName && !connectionError">
                    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex space-x-4" aria-label="Tabs">
                            <Link
                                :href="route('tables.index', { connection: selectedConnectionId, databaseName: selectedDatabaseName })"
                                :class="[
                                    activeTab === 'tables'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Tabelas
                            </Link>

                            <Link
                                v-if="selectedTableName"
                                :href="route('tables.structure', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: selectedTableName })"
                                :class="[ activeTab === 'structure' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']"
                            >
                                Estrutura
                            </Link>
                            <span v-else class="border-transparent text-gray-400 dark:text-gray-600 whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm cursor-not-allowed">
                                Estrutura
                            </span>

                            <Link
                                v-if="selectedTableName"
                                :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: selectedTableName })"
                                :class="[
                                activeTab === 'data'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm'
                            ]"
                            >
                                Dados
                            </Link>
                            <span v-else class="border-transparent text-gray-400 dark:text-gray-600 whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm cursor-not-allowed">
                                Dados
                            </span>

                            <Link
                                :href="route('database.showSql', { connection: selectedConnectionId, databaseName: selectedDatabaseName })"
                                :class="[
                                activeTab === 'sql'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm'
                            ]"
                            >
                                SQL
                            </Link>
                        </nav>
                    </div>

                    <div v-if="activeTab === 'tables'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            Tabelas em <span class="text-blue-600 font-mono">{{ selectedDatabaseName }}</span>
                        </h2>
                        <ul v-if="tables.length > 0" class="space-y-2">
                            <li v-for="table in tables" :key="table">
                                <Link
                                    :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: table })"
                                    class="p-3 block bg-white dark:bg-gray-800 rounded-lg shadow border border-transparent dark:border-gray-700 hover:shadow-md transition-shadow"
                                >
                                    <span class="text-gray-800 dark:text-gray-200 font-mono">{{ table }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div v-if="activeTab === 'data'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            Mostrando dados de <span class="text-green-600 font-mono">{{ selectedTableName }}</span>
                        </h2>
                        <div v-if="hasDataRows" class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr><th v-for="col in tableData.columns" :key="col" scope="col" class="px-6 py-3 font-mono">{{ col }}</th></tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in tableData.rowsPaginator.data" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td v-for="col in tableData.columns" :key="col" class="px-6 py-4 font-mono">
                                        <span :class="{'text-gray-400 dark:text-gray-500 italic': row[col] === null}">{{ truncate(row[col]) }}</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="hasDataRows && tableData.rowsPaginator.links.length > 3" class="mt-6 flex justify-between items-center">
                        </div>
                    </div>

                    <div v-if="activeTab === 'structure'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            Estrutura da Tabela <span class="text-green-600 font-mono">{{ selectedTableName }}</span>
                        </h2>

                        <div class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nome (Field)</th>
                                    <th scope="col" class="px-6 py-3">Tipo (Type)</th>
                                    <th scope="col" class="px-6 py-3">Nulo (Null)</th>
                                    <th scope="col" class="px-6 py-3">Chave (Key)</th>
                                    <th scope="col" class="px-6 py-3">Padrão (Default)</th>
                                    <th scope="col" class="px-6 py-3">Extra</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="column in tableStructure" :key="column.Field" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap font-mono">
                                        {{ column.Field }}
                                    </th>
                                    <td class="px-6 py-4 font-mono">
                                        {{ column.Type }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ column.Null }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ column.Key }}
                                    </td>
                                    <td class="px-6 py-4 font-mono">
                                        {{ column.Default }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ column.Extra }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="activeTab === 'sql'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            Executar SQL em <span class="text-blue-600 font-mono">{{ selectedDatabaseName }}</span>
                        </h2>

                        <form @submit.prevent="submitSql">
                            <textarea
                                v-model="sqlForm.query"
                                rows="10"
                                class="w-full p-2 font-mono text-sm text-gray-900 dark:text-gray-200 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="SELECT * FROM ..."
                            ></textarea>
                            <div class="mt-4">
                                <button type="submit" :disabled="sqlForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                    Executar
                                </button>
                            </div>
                        </form>

                        <div v-if="sqlAffectedRows !== null" class="mt-6 p-4 bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 rounded-md">
                            <p class="font-medium text-green-800 dark:text-green-200">Sucesso. {{ sqlAffectedRows }} linhas afetadas.</p>
                        </div>

                        <div v-if="hasSqlResults" class="mt-6 w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th v-for="col in sqlResultColumns" :key="col" scope="col" class="px-6 py-3 font-mono">
                                        {{ col }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in sqlResults" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td v-for="col in sqlResultColumns" :key="col" class="px-6 py-4 font-mono">
                                        <span :class="{'text-gray-400 dark:text-gray-500 italic': row[col] === null}">{{ truncate(row[col]) }}</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="sqlResults && !hasSqlResults" class="mt-6 text-gray-600 dark:text-gray-400">
                            Consulta executada com sucesso. Nenhum resultado retornado.
                        </p>
                    </div>

                </div>

                <div v-else-if="!selectedConnectionId && !connectionError">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Bem-vindo ao QueryHub
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Selecione uma conexão na barra lateral para começar.
                    </p>
                </div>

            </main>
        </div>
    </AuthenticatedLayout>
</template>
