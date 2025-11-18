<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue'; // Importar PrimaryButton
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue'; // Importar InputError (estava faltando)

const props = defineProps({
    userConnections: Array,
    selectedConnectionId: { type: Number, default: null },
    databases: { type: Array, default: () => [] },
    selectedDatabaseName: { type: String, default: null },
    tables: { type: Array, default: () => [] },
    selectedTableName: { type: String, default: null },
    tableData: {
        type: Object,
        default: () => ({ columns: [], rowsPaginator: { data: [], links: [] }, primaryKeyColumns: [] })
    },
    tableStructure: {
        type: Array,
        default: () => []
    },
    connectionError: { type: String, default: null },

    activeTab: { type: String, default: null },
    sqlQuery: { type: String, default: '' },
    sqlResults: { type: Array, default: null },
    sqlAffectedRows: { type: Number, default: null },
});

// --- Helpers e Computed ---
const truncate = (value, length = 50) => {
    if (value === null) return 'NULL';
    let str = String(value);
    if (str.length > length) return str.substring(0, length) + '...';
    return str;
};
const hasDataRows = computed(() => props.tableData.rowsPaginator && props.tableData.rowsPaginator.data.length > 0);
const hasSqlResults = computed(() => props.sqlResults && props.sqlResults.length > 0);
const sqlResultColumns = computed(() => hasSqlResults.value ? Object.keys(props.sqlResults[0]) : []);

// --- SQL Form ---
const sqlForm = useForm({ query: props.sqlQuery });
const submitSql = () => {
    sqlForm.post(route('database.executeSql', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName
    }), { preserveState: false, preserveScroll: true });
};

// --- Delete Logic ---
const confirmDelete = (row) => {
    if (window.confirm('Tem certeza que deseja deletar esta linha?')) {
        router.delete(route('tables.row.destroy', {
            connection: props.selectedConnectionId,
            databaseName: props.selectedDatabaseName,
            tableName: props.selectedTableName,
        }), { data: { row: row }, preserveScroll: true });
    }
};

// --- Edit Logic ---
const showEditModal = ref(false);
const editForm = useForm({ newRowData: {}, originalPkValues: {} });

const openEditModal = (row) => {
    editForm.newRowData = JSON.parse(JSON.stringify(row));
    editForm.errors = {};
    let tempPks = {};
    props.tableData.primaryKeyColumns.forEach(pkCol => { tempPks[pkCol] = row[pkCol]; });
    editForm.originalPkValues = tempPks;
    showEditModal.value = true;
};

const submitUpdate = () => {
    editForm.patch(route('tables.row.update', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName,
        tableName: props.selectedTableName,
    }), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- INSERT LOGIC (NOVO) ---
const showInsertModal = ref(false);
const insertForm = useForm({ rowData: {} });

const openInsertModal = () => {
    // Inicializa o formulário com chaves vazias baseadas nas colunas da tabela
    let emptyRow = {};
    props.tableData.columns.forEach(col => {
        emptyRow[col] = ''; // Começa vazio. O backend converterá '' para null se necessário.
    });

    insertForm.rowData = emptyRow;
    insertForm.errors = {};
    showInsertModal.value = true;
};

const submitInsert = () => {
    insertForm.post(route('tables.row.store', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName,
        tableName: props.selectedTableName,
    }), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- Common Modal Logic ---
const closeModal = () => {
    showEditModal.value = false;
    showInsertModal.value = false;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">QueryHub</h2>
        </template>

        <div class="flex h-[calc(100vh-65px)]">
            <nav class="w-64 bg-white dark:bg-gray-800 border-r dark:border-gray-700 p-4 space-y-2 overflow-y-auto flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Minhas Conexões</h3>
                <ul v-if="userConnections.length > 0">
                    <li v-for="conn in userConnections" :key="conn.id">
                        <Link :href="route('databases.index', { connection: conn.id })" :class="{'bg-gray-200 dark:bg-gray-700 font-bold': conn.id === selectedConnectionId, 'block p-2 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': true}">{{ conn.name }}</Link>
                    </li>
                </ul>
            </nav>

            <div class="w-72 bg-gray-100 dark:bg-gray-800/50 border-r dark:border-gray-700 p-4 overflow-y-auto flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Bancos de Dados</h3>
                <ul class="space-y-1" v-if="databases.length > 0">
                    <li v-for="db in databases" :key="db">
                        <Link :href="route('tables.index', { connection: selectedConnectionId, databaseName: db })" :class="{'bg-blue-100 dark:bg-blue-900 font-bold text-blue-700 dark:text-blue-300': db === selectedDatabaseName, 'block p-2 rounded font-mono text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700': true}">{{ db }}</Link>
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
                            <Link :href="route('tables.index', { connection: selectedConnectionId, databaseName: selectedDatabaseName })" :class="[activeTab === 'tables' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">Tabelas</Link>

                            <Link v-if="selectedTableName" :href="route('tables.structure', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: selectedTableName })" :class="[activeTab === 'structure' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">Estrutura</Link>
                            <span v-else class="border-transparent text-gray-400 dark:text-gray-600 whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm cursor-not-allowed">Estrutura</span>

                            <Link v-if="selectedTableName" :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: selectedTableName })" :class="[activeTab === 'data' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">Dados</Link>
                            <span v-else class="border-transparent text-gray-400 dark:text-gray-600 whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm cursor-not-allowed">Dados</span>

                            <Link :href="route('database.showSql', { connection: selectedConnectionId, databaseName: selectedDatabaseName })" :class="[activeTab === 'sql' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">SQL</Link>
                        </nav>
                    </div>

                    <div v-if="activeTab === 'tables'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Tabelas em <span class="text-blue-600 font-mono">{{ selectedDatabaseName }}</span></h2>
                        <ul v-if="tables.length > 0" class="space-y-2">
                            <li v-for="table in tables" :key="table">
                                <Link :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: table })" class="p-3 block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow"><span class="text-gray-800 dark:text-gray-200 font-mono">{{ table }}</span></Link>
                            </li>
                        </ul>
                    </div>

                    <div v-if="activeTab === 'data'">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Mostrando dados de <span class="text-green-600 font-mono">{{ selectedTableName }}</span></h2>
                            <PrimaryButton @click="openInsertModal">Inserir Linha</PrimaryButton>
                        </div>

                        <div v-if="hasDataRows" class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th v-for="col in tableData.columns" :key="col" class="px-6 py-3 font-mono">{{ col }}</th>
                                    <th v-if="tableData.primaryKeyColumns.length > 0" class="px-6 py-3">Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in tableData.rowsPaginator.data" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td v-for="col in tableData.columns" :key="col" class="px-6 py-4 font-mono"><span :class="{'text-gray-400 italic': row[col] === null}">{{ truncate(row[col]) }}</span></td>
                                    <td v-if="tableData.primaryKeyColumns.length > 0" class="px-6 py-4 space-x-2 whitespace-nowrap">
                                        <SecondaryButton @click="openEditModal(row)">Editar</SecondaryButton>
                                        <DangerButton @click="confirmDelete(row)">Deletar</DangerButton>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="activeTab === 'structure'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Estrutura da Tabela <span class="text-green-600 font-mono">{{ selectedTableName }}</span></h2>
                        <div class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr><th class="px-6 py-3">Nome</th><th class="px-6 py-3">Tipo</th><th class="px-6 py-3">Nulo</th><th class="px-6 py-3">Chave</th><th class="px-6 py-3">Padrão</th><th class="px-6 py-3">Extra</th></tr>
                                </thead>
                                <tbody>
                                <tr v-for="column in tableStructure" :key="column.Field" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50">
                                    <th class="px-6 py-4 font-medium text-gray-900 dark:text-white font-mono">{{ column.Field }}</th>
                                    <td class="px-6 py-4 font-mono">{{ column.Type }}</td>
                                    <td class="px-6 py-4">{{ column.Null }}</td>
                                    <td class="px-6 py-4">{{ column.Key }}</td>
                                    <td class="px-6 py-4 font-mono">{{ column.Default }}</td>
                                    <td class="px-6 py-4">{{ column.Extra }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="activeTab === 'sql'">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Executar SQL em <span class="text-blue-600 font-mono">{{ selectedDatabaseName }}</span></h2>
                        <form @submit.prevent="submitSql">
                            <textarea v-model="sqlForm.query" rows="10" class="w-full p-2 font-mono text-sm text-gray-900 dark:text-gray-200 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="SELECT * FROM ..."></textarea>
                            <div class="mt-4"><PrimaryButton :disabled="sqlForm.processing">Executar</PrimaryButton></div>
                        </form>
                        <div v-if="sqlAffectedRows !== null" class="mt-6 p-4 bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 rounded-md"><p class="font-medium text-green-800 dark:text-green-200">Sucesso. {{ sqlAffectedRows }} linhas afetadas.</p></div>
                        <div v-if="hasSqlResults" class="mt-6 w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr><th v-for="col in sqlResultColumns" :key="col" class="px-6 py-3 font-mono">{{ col }}</th></tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in sqlResults" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50"><td v-for="col in sqlResultColumns" :key="col" class="px-6 py-4 font-mono">{{ truncate(row[col]) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div v-else-if="!selectedConnectionId && !connectionError">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Bem-vindo ao QueryHub</h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Selecione uma conexão na barra lateral para começar.</p>
                </div>
            </main>

            <Modal :show="showEditModal" @close="closeModal">
                <form @submit.prevent="submitUpdate" class="p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Editando Linha</h2>
                    <div class="mt-6 max-h-[60vh] overflow-y-auto space-y-4 pr-2">
                        <div v-for="(value, column) in editForm.newRowData" :key="column">
                            <InputLabel :for="'edit_'+column" :value="column" class="font-mono" />
                            <textarea v-if="typeof value === 'string' && value.length > 100" :id="'edit_'+column" v-model="editForm.newRowData[column]" rows="4" class="mt-1 block w-full font-mono text-sm text-gray-900 dark:text-gray-200 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm" :disabled="tableData.primaryKeyColumns.includes(column)"></textarea>
                            <TextInput v-else :id="'edit_'+column" type="text" v-model="editForm.newRowData[column]" class="mt-1 block w-full font-mono text-sm" :disabled="tableData.primaryKeyColumns.includes(column)" :class="{ 'bg-gray-100 dark:bg-gray-900': tableData.primaryKeyColumns.includes(column) }" />
                            <InputError :message="editForm.errors[`newRowData.${column}`]" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-4">
                        <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing">Salvar Alterações</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal :show="showInsertModal" @close="closeModal">
                <form @submit.prevent="submitInsert" class="p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Inserir Nova Linha</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Deixe campos auto-increment vazios.</p>

                    <div class="mt-6 max-h-[60vh] overflow-y-auto space-y-4 pr-2">
                        <div v-for="(value, column) in insertForm.rowData" :key="column">
                            <InputLabel :for="'insert_'+column" :value="column" class="font-mono" />
                            <TextInput
                                :id="'insert_'+column"
                                type="text"
                                v-model="insertForm.rowData[column]"
                                class="mt-1 block w-full font-mono text-sm"
                                placeholder="NULL"
                            />
                            <InputError :message="insertForm.errors[`rowData.${column}`]" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-4">
                        <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="insertForm.processing">Inserir Linha</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
