<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

// --- Props ---
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
    tableStructure: { type: Array, default: () => [] },
    connectionError: { type: String, default: null },
    activeTab: { type: String, default: null },
    sqlQuery: { type: String, default: '' },
    sqlResults: { type: Array, default: null },
    sqlAffectedRows: { type: Number, default: null },
});

// --- Helpers ---
const truncate = (value, length = 50) => {
    if (value === null) return 'NULL';
    let str = String(value);
    if (str.length > length) return str.substring(0, length) + '...';
    return str;
};

// --- SQL ---
const sqlForm = useForm({ query: props.sqlQuery || 'SELECT * FROM ' });
const submitSql = () => {
    sqlForm.post(route('database.executeSql', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName
    }), { preserveState: false, preserveScroll: true });
};

// --- Row Actions ---
const hasDataRows = computed(() => props.tableData.rowsPaginator && props.tableData.rowsPaginator.data.length > 0);
const hasSqlResults = computed(() => props.sqlResults && props.sqlResults.length > 0);
const sqlResultColumns = computed(() => hasSqlResults.value ? Object.keys(props.sqlResults[0]) : []);

const confirmDeleteRow = (row) => {
    if (window.confirm('Tem certeza que deseja deletar esta linha?')) {
        router.delete(route('tables.row.destroy', {
            connection: props.selectedConnectionId,
            databaseName: props.selectedDatabaseName,
            tableName: props.selectedTableName,
        }), { data: { row: row }, preserveScroll: true });
    }
};

// --- Edit Row Logic ---
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

const submitUpdateRow = () => {
    editForm.patch(route('tables.row.update', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName,
        tableName: props.selectedTableName,
    }), { preserveScroll: true, onSuccess: () => closeModal() });
};

// --- Insert Row Logic ---
const showInsertModal = ref(false);
const insertForm = useForm({ rowData: {} });

const openInsertModal = () => {
    let emptyRow = {};
    props.tableData.columns.forEach(col => { emptyRow[col] = ''; });
    insertForm.rowData = emptyRow;
    insertForm.errors = {};
    showInsertModal.value = true;
};

const submitInsertRow = () => {
    insertForm.post(route('tables.row.store', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName,
        tableName: props.selectedTableName,
    }), { preserveScroll: true, onSuccess: () => closeModal() });
};

// --- Table Structure Actions ---
const confirmDropTable = (tableName) => {
    if (window.confirm(`ATENÇÃO: Tem certeza absoluta que deseja EXCLUIR a tabela '${tableName}'? Todos os dados serão perdidos para sempre.`)) {
        router.delete(route('tables.destroy', {
            connection: props.selectedConnectionId,
            databaseName: props.selectedDatabaseName,
            tableName: tableName,
        }));
    }
};

const showCreateTableModal = ref(false);
const createTableForm = useForm({
    name: '',
    columns: [
        { name: 'id', type: 'INT', length: '', nullable: false, ai: true, pk: true },
        { name: '', type: 'VARCHAR', length: '255', nullable: true, ai: false, pk: false }
    ]
});

const openCreateTableModal = () => {
    createTableForm.reset();
    createTableForm.columns = [
        { name: 'id', type: 'INT', length: '', nullable: false, ai: true, pk: true },
        { name: '', type: 'VARCHAR', length: '255', nullable: true, ai: false, pk: false }
    ];
    showCreateTableModal.value = true;
};

const addFormColumn = () => {
    createTableForm.columns.push({ name: '', type: 'VARCHAR', length: '255', nullable: true, ai: false, pk: false });
};

const removeFormColumn = (index) => {
    if (createTableForm.columns.length > 1) {
        createTableForm.columns.splice(index, 1);
    }
};

const submitCreateTable = () => {
    createTableForm.post(route('tables.store', {
        connection: props.selectedConnectionId,
        databaseName: props.selectedDatabaseName,
    }), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    showEditModal.value = false;
    showInsertModal.value = false;
    showCreateTableModal.value = false;
};

// --- RESPONSIVIDADE: Controle de Visualização Mobile ---
// 'nav': Mostra a lista de conexões/bancos/tabelas
// 'content': Mostra a tabela de dados/estrutura/sql
const mobileView = ref(props.selectedTableName || props.activeTab === 'sql' ? 'content' : 'nav');

const toggleMobileView = (view) => {
    mobileView.value = view;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">QueryHub</h2>
                <button
                    v-if="selectedDatabaseName"
                    @click="toggleMobileView(mobileView === 'nav' ? 'content' : 'nav')"
                    class="md:hidden px-3 py-1 text-sm border rounded dark:text-gray-200 border-gray-300 dark:border-gray-600"
                >
                    {{ mobileView === 'nav' ? 'Ver Conteúdo →' : '← Ver Menu' }}
                </button>
            </div>
        </template>

        <div class="flex flex-col md:flex-row h-[calc(100vh-65px)] overflow-hidden">

            <nav
                class="bg-white dark:bg-gray-800 border-r dark:border-gray-700 overflow-y-auto flex-shrink-0 transition-all duration-300"
                :class="{
                    'w-full md:w-80': true, // Largura total no mobile, 80 no desktop
                    'hidden md:block': mobileView !== 'nav' // Esconde no mobile se estiver vendo conteúdo
                }"
            >
                <div class="p-4 space-y-6">

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 px-2">Conexões</h3>
                        <ul v-if="userConnections.length > 0" class="space-y-1">
                            <li v-for="conn in userConnections" :key="conn.id">
                                <Link
                                    :href="route('databases.index', { connection: conn.id })"
                                    :class="{
                                        'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-bold': conn.id === selectedConnectionId,
                                        'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': conn.id !== selectedConnectionId,
                                        'block px-3 py-2 rounded-md text-sm transition-colors': true
                                    }"
                                >
                                    <div class="flex items-center">
                                        <span class="mr-2">🔌</span>
                                        {{ conn.name }}
                                    </div>
                                </Link>

                                <div v-if="conn.id === selectedConnectionId" class="ml-4 mt-1 pl-2 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
                                    <div v-if="databases.length > 0">
                                        <div v-for="db in databases" :key="db">
                                            <Link
                                                :href="route('tables.index', { connection: selectedConnectionId, databaseName: db })"
                                                :class="{
                                                    'text-blue-600 dark:text-blue-400 font-semibold': db === selectedDatabaseName,
                                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200': db !== selectedDatabaseName,
                                                    'block px-2 py-1 text-sm rounded hover:bg-gray-50 dark:hover:bg-gray-700/50': true
                                                }"
                                            >
                                                <div class="flex items-center">
                                                    <span class="mr-2">🗄️</span>
                                                    <span class="truncate">{{ db }}</span>
                                                </div>
                                            </Link>

                                            <div v-if="db === selectedDatabaseName" class="ml-4 mt-1 pl-2 border-l-2 border-gray-200 dark:border-gray-700">

                                                <div class="flex justify-between items-center px-2 py-1 mb-1">
                                                    <span class="text-xs font-bold text-gray-400 uppercase">Tabelas</span>
                                                    <button @click.stop="openCreateTableModal" class="text-blue-500 hover:text-blue-700" title="Nova Tabela">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                    </button>
                                                </div>

                                                <ul v-if="tables.length > 0" class="space-y-0.5">
                                                    <li v-for="table in tables" :key="table" class="group flex items-center justify-between px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                                        <Link
                                                            :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: table })"
                                                            :class="{
                                                                'text-green-600 dark:text-green-400 font-bold': table === selectedTableName,
                                                                'text-gray-500 dark:text-gray-400': table !== selectedTableName,
                                                                'block flex-1 truncate text-sm font-mono cursor-pointer': true
                                                            }"
                                                            preserve-scroll
                                                            @click="mobileView = 'content'"
                                                        >
                                                            {{ table }}
                                                        </Link>

                                                        <button @click.stop="confirmDropTable(table)" class="hidden group-hover:block text-red-400 hover:text-red-600 ml-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                        </button>
                                                    </li>
                                                </ul>
                                                <p v-else class="text-xs text-gray-400 px-2 italic">Vazio</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-gray-500 px-2 italic">Nenhum banco</p>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500 italic px-2">Nenhuma conexão.</p>
                    </div>
                </div>
            </nav>

            <main
                class="flex-1 bg-gray-100 dark:bg-gray-900 overflow-y-auto min-w-0"
                :class="{
                    'hidden md:block': mobileView !== 'content' // Esconde no mobile se estiver vendo menu
                }"
            >
                <div class="p-6">

                    <div v-if="connectionError">
                        <h2 class="text-2xl font-bold text-red-600 dark:text-red-400">Erro</h2>
                        <pre class="mt-4 p-4 bg-gray-200 dark:bg-gray-800 rounded text-red-700 dark:text-red-300 overflow-x-auto text-xs md:text-sm">{{ connectionError }}</pre>
                    </div>

                    <div v-if="selectedDatabaseName && !connectionError">
                        <div class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <nav class="flex space-x-4" aria-label="Tabs">
                                <Link :href="route('tables.index', { connection: selectedConnectionId, databaseName: selectedDatabaseName })" :class="[activeTab === 'tables' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">Info</Link>

                                <Link v-if="selectedTableName" :href="route('tables.structure', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: selectedTableName })" :class="[activeTab === 'structure' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">Estrutura</Link>
                                <span v-else class="border-transparent text-gray-400 dark:text-gray-600 whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm cursor-not-allowed">Estrutura</span>

                                <Link v-if="selectedTableName" :href="route('tables.data', { connection: selectedConnectionId, databaseName: selectedDatabaseName, tableName: selectedTableName })" :class="[activeTab === 'data' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">Dados</Link>
                                <span v-else class="border-transparent text-gray-400 dark:text-gray-600 whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm cursor-not-allowed">Dados</span>

                                <Link :href="route('database.showSql', { connection: selectedConnectionId, databaseName: selectedDatabaseName })" :class="[activeTab === 'sql' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm']">SQL</Link>
                            </nav>
                        </div>

                        <div v-if="activeTab === 'tables'">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    Banco: <span class="text-blue-600 font-mono">{{ selectedDatabaseName }}</span>
                                </h2>

                                <a :href="route('database.export', { connection: selectedConnectionId, databaseName: selectedDatabaseName })" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg> Exportar .SQL
                                </a>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">Selecione uma tabela no menu à esquerda ou crie uma nova.</p>
                        </div>

                        <div v-if="activeTab === 'data'">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                                <h2 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-gray-100 truncate w-full">
                                    Dados: <span class="text-green-600 font-mono">{{ selectedTableName }}</span>
                                </h2>
                                <PrimaryButton @click="openInsertModal" class="flex-shrink-0">Inserir Linha</PrimaryButton>
                            </div>

                            <div v-if="hasDataRows" class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th v-for="col in tableData.columns" :key="col" class="px-4 py-3 font-mono whitespace-nowrap">{{ col }}</th>
                                        <th v-if="tableData.primaryKeyColumns.length > 0" class="px-4 py-3 whitespace-nowrap">Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(row, index) in tableData.rowsPaginator.data" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td v-for="col in tableData.columns" :key="col" class="px-4 py-3 font-mono whitespace-nowrap"><span :class="{'text-gray-400 italic': row[col] === null}">{{ truncate(row[col]) }}</span></td>
                                        <td v-if="tableData.primaryKeyColumns.length > 0" class="px-4 py-3 space-x-2 whitespace-nowrap">
                                            <SecondaryButton @click="openEditModal(row)" class="!px-2 !py-1 !text-xs">Editar</SecondaryButton>
                                            <DangerButton @click="confirmDeleteRow(row)" class="!px-2 !py-1 !text-xs">Deletar</DangerButton>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-10 text-gray-500 dark:text-gray-400">A tabela está vazia.</div>
                        </div>

                        <div v-if="activeTab === 'structure'">
                            <h2 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Estrutura: <span class="text-green-600 font-mono">{{ selectedTableName }}</span></h2>
                            <div class="w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr><th class="px-6 py-3">Nome</th><th class="px-6 py-3">Tipo</th><th class="px-6 py-3">Nulo</th><th class="px-6 py-3">Chave</th><th class="px-6 py-3">Padrão</th><th class="px-6 py-3">Extra</th></tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="column in tableStructure" :key="column.Field" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50">
                                        <th class="px-6 py-4 font-medium text-gray-900 dark:text-white font-mono whitespace-nowrap">{{ column.Field }}</th>
                                        <td class="px-6 py-4 font-mono whitespace-nowrap">{{ column.Type }}</td>
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
                            <h2 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Executar SQL em <span class="text-blue-600 font-mono">{{ selectedDatabaseName }}</span></h2>
                            <form @submit.prevent="submitSql" class="flex flex-col h-full">
                                <textarea v-model="sqlForm.query" rows="10" class="w-full p-2 font-mono text-sm text-gray-900 dark:text-gray-200 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="SELECT * FROM ..."></textarea>
                                <div class="mt-4"><PrimaryButton :disabled="sqlForm.processing">Executar</PrimaryButton></div>
                            </form>
                            <div v-if="sqlAffectedRows !== null" class="mt-6 p-4 bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 rounded-md"><p class="font-medium text-green-800 dark:text-green-200">Sucesso. {{ sqlAffectedRows }} linhas afetadas.</p></div>
                            <div v-if="hasSqlResults" class="mt-6 w-full overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr><th v-for="col in sqlResultColumns" :key="col" class="px-6 py-3 font-mono whitespace-nowrap">{{ col }}</th></tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(row, index) in sqlResults" :key="index" class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50"><td v-for="col in sqlResultColumns" :key="col" class="px-6 py-4 font-mono whitespace-nowrap">{{ truncate(row[col]) }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="!selectedConnectionId && !connectionError" class="flex flex-col items-center justify-center h-full text-center p-10">
                        <div class="text-6xl mb-4">👋</div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Bem-vindo ao QueryHub</h2>
                        <p class="text-gray-600 dark:text-gray-400 max-w-md">
                            Selecione uma conexão no menu à esquerda para começar a navegar pelos seus bancos de dados.
                        </p>
                    </div>
                </div>
            </main>

            <Modal :show="showEditModal" @close="closeModal">
                <form @submit.prevent="submitUpdateRow" class="p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Editando Linha</h2>
                    <div class="mt-4 max-h-[60vh] overflow-y-auto space-y-4 pr-2">
                        <div v-for="(value, column) in editForm.newRowData" :key="column">
                            <InputLabel :for="'edit_'+column" :value="column" class="font-mono text-xs uppercase text-gray-500" />
                            <textarea v-if="typeof value === 'string' && value.length > 100" :id="'edit_'+column" v-model="editForm.newRowData[column]" rows="3" class="mt-1 block w-full font-mono text-sm text-gray-900 dark:text-gray-200 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm" :disabled="tableData.primaryKeyColumns.includes(column)"></textarea>
                            <TextInput v-else :id="'edit_'+column" type="text" v-model="editForm.newRowData[column]" class="mt-1 block w-full font-mono text-sm" :disabled="tableData.primaryKeyColumns.includes(column)" :class="{ 'bg-gray-100 dark:bg-gray-900 opacity-75': tableData.primaryKeyColumns.includes(column) }" />
                            <InputError :message="editForm.errors[`newRowData.${column}`]" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing">Salvar Alterações</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal :show="showInsertModal" @close="closeModal">
                <form @submit.prevent="submitInsertRow" class="p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Inserir Nova Linha</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Campos auto-increment podem ser deixados em branco.</p>
                    <div class="mt-4 max-h-[60vh] overflow-y-auto space-y-4 pr-2">
                        <div v-for="(value, column) in insertForm.rowData" :key="column">
                            <InputLabel :for="'insert_'+column" :value="column" class="font-mono text-xs uppercase text-gray-500" />
                            <TextInput :id="'insert_'+column" type="text" v-model="insertForm.rowData[column]" class="mt-1 block w-full font-mono text-sm" placeholder="NULL" />
                            <InputError :message="insertForm.errors[`rowData.${column}`]" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                        <PrimaryButton :disabled="insertForm.processing">Inserir Linha</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal :show="showCreateTableModal" @close="closeModal">
                <form @submit.prevent="submitCreateTable" class="p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Criar Nova Tabela</h2>
                    <div class="mb-4">
                        <InputLabel for="new_table_name" value="Nome da Tabela" />
                        <TextInput id="new_table_name" type="text" v-model="createTableForm.name" class="mt-1 block w-full" required placeholder="ex: usuarios" />
                        <InputError :message="createTableForm.errors.name" class="mt-2" />
                    </div>
                    <div class="mt-4 max-h-[50vh] overflow-y-auto pr-2 border rounded-md p-2 dark:border-gray-700">
                        <div v-for="(col, index) in createTableForm.columns" :key="index" class="flex flex-wrap md:flex-nowrap gap-2 mb-3 items-end border-b pb-2 last:border-0 dark:border-gray-700">
                            <div class="w-full md:flex-1">
                                <InputLabel value="Nome" class="text-xs mb-1" />
                                <TextInput type="text" v-model="col.name" class="w-full text-xs" required />
                            </div>
                            <div class="w-1/2 md:w-24">
                                <InputLabel value="Tipo" class="text-xs mb-1" />
                                <select v-model="col.type" class="w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm h-[34px]">
                                    <option value="INT">INT</option><option value="VARCHAR">VARCHAR</option><option value="TEXT">TEXT</option><option value="DATE">DATE</option><option value="DATETIME">DATETIME</option><option value="BOOLEAN">BOOLEAN</option><option value="DECIMAL">DECIMAL</option>
                                </select>
                            </div>
                            <div class="w-1/3 md:w-16">
                                <InputLabel value="Tam." class="text-xs mb-1" />
                                <TextInput type="text" v-model="col.length" class="w-full text-xs" placeholder="" />
                            </div>
                            <div class="w-full md:w-auto flex items-center gap-3 pt-2 md:pt-0">
                                <label class="flex items-center text-xs"><input type="checkbox" v-model="col.pk" class="rounded border-gray-300 text-blue-600 shadow-sm mr-1" /> PK</label>
                                <label class="flex items-center text-xs"><input type="checkbox" v-model="col.ai" class="rounded border-gray-300 text-blue-600 shadow-sm mr-1" /> AI</label>
                                <label class="flex items-center text-xs"><input type="checkbox" v-model="col.nullable" class="rounded border-gray-300 text-blue-600 shadow-sm mr-1" /> Null</label>
                                <button type="button" @click="removeFormColumn(index)" class="text-red-500 hover:text-red-700 ml-auto md:ml-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2"><SecondaryButton type="button" @click="addFormColumn" class="text-xs w-full md:w-auto">+ Adicionar Coluna</SecondaryButton></div>
                    <div class="mt-6 flex justify-end gap-3"><SecondaryButton @click="closeModal">Cancelar</SecondaryButton><PrimaryButton :disabled="createTableForm.processing">Criar Tabela</PrimaryButton></div>
                </form>
            </Modal>

        </div>
    </AuthenticatedLayout>
</template>
