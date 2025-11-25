<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\ConnectionPermissionController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DatabasePermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TableController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {

    $user = Auth::user()->load('connections');

    return Inertia::render('Dashboard', [
        'userConnections' => $user->connections->map(fn($conn) => [
            'id' => $conn->id,
            'name' => $conn->name,
        ])
    ]);

})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Connections
    Route::middleware('role:Administrator')->group(function () {
        Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
        Route::post('/connections', [ConnectionController::class, 'store'])->name('connections.store');
        Route::patch('/connections/{connection}', [ConnectionController::class, 'update'])->name('connections.update');
        Route::delete('/connections/{connection}', [ConnectionController::class, 'destroy'])->name('connections.destroy');

        Route::get('/connections/{connection}/users/{user}/db-permissions', [DatabasePermissionController::class, 'edit'])
            ->name('connections.users.db-permissions.edit');

        Route::post('/connections/{connection}/users/{user}/db-permissions', [DatabasePermissionController::class, 'update'])
            ->name('connections.users.db-permissions.update');

    });

    Route::get('/connections/{connection}/databases', [DatabaseController::class, 'index'])
        ->name('databases.index');

    Route::get('/connections/{connection}/databases/{databaseName}/export', [DatabaseController::class, 'export'])
        ->name('database.export');
    Route::post('/connections/{connection}/databases', [DatabaseController::class, 'store'])
        ->name('databases.store');
    Route::post('/connections/{connection}/databases/{databaseName}/import', [DatabaseController::class, 'import'])
        ->name('database.import');

    Route::get('/connections/{connection}/databases/{databaseName}/tables', [TableController::class, 'index'])
        ->name('tables.index');
    Route::get('/connections/{connection}/databases/{databaseName}/sql', [DatabaseController::class, 'showSql'])
        ->name('database.showSql');
    Route::post('/connections/{connection}/databases/{databaseName}/execute-sql', [DatabaseController::class, 'executeSql'])
        ->name('database.executeSql');
    Route::get('/connections/{connection}/databases/{databaseName}/tables/{tableName}', [TableController::class, 'showData'])
        ->name('tables.data');
    Route::get('/connections/{connection}/databases/{databaseName}/tables/{tableName}/structure', [TableController::class, 'showStructure'])
        ->name('tables.structure');
    Route::delete('/connections/{connection}/databases/{databaseName}/tables/{tableName}/row', [TableController::class, 'destroyRow'])
        ->name('tables.row.destroy');
    Route::patch('/connections/{connection}/databases/{databaseName}/tables/{tableName}/row', [TableController::class, 'updateRow'])
        ->name('tables.row.update');
    Route::post('/connections/{connection}/databases/{databaseName}/tables/{tableName}/row', [TableController::class, 'storeRow'])
        ->name('tables.row.store');

    Route::post('/connections/{connection}/databases/{databaseName}/tables', [TableController::class, 'store'])
        ->name('tables.store');
    Route::delete('/connections/{connection}/databases/{databaseName}/tables/{tableName}', [TableController::class, 'destroy'])
        ->name('tables.destroy');

    Route::get('/connections/{connection}/databases/{databaseName}/export', [DatabaseController::class, 'export'])
        ->name('database.export');

    Route::post('/connections/{connection}/databases', [DatabaseController::class, 'store'])
        ->name('databases.store');

    Route::get('/connections/{connection}/permissions', [ConnectionPermissionController::class, 'edit'])
            ->name('connections.permissions.edit');
    Route::patch('/connections/{connection}/permissions', [ConnectionPermissionController::class, 'update'])
            ->name('connections.permissions.update');
});

// --- Rotas de Administrador ---
Route::middleware(['auth', 'role:Administrator'])->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show']);
});

// --- Rota para Forçar Mudança de Senha ---
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [\App\Http\Controllers\PasswordChangeController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [\App\Http\Controllers\PasswordChangeController::class, 'updatePassword'])->name('password.updateFirst');
});


require __DIR__.'/auth.php';
