<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\AccountTypeController;
// use App\Http\Controllers\TeamController; // Comentado - controlador eliminado
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DataTables\PersonDataTableController;
// use App\Http\Controllers\DataTables\TeamDataTableController; // Comentado - controlador eliminado
use App\Http\Controllers\DataTables\AccountDataTableController;
use App\Http\Controllers\DataTables\TransactionDataTableController;
use App\Http\Controllers\DataTables\ExpenseDataTableController;
use App\Http\Controllers\DataTables\BankDataTableController;
use App\Http\Controllers\DataTables\AccountTypeDataTableController;
use App\Http\Controllers\DataTables\ApprovalDataTableController;
use App\Http\Controllers\Admin\ToolsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DataTables\UserDataTableController;
use App\Http\Controllers\PersonBankAccountController;
use App\Http\Controllers\StatisticsController;
use App\Livewire\TransactionList;
use App\Livewire\AccountList;
use App\Livewire\ExpenseList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Estadísticas
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

    // Rutas de Cuentas
    Route::resource('accounts', AccountController::class);

    // Rutas de Transacciones  
    Route::resource('transactions', TransactionController::class);

    // Rutas de Gastos
    Route::resource('expenses', ExpenseController::class);

    // Rutas de Personas
    Route::resource('people', PersonController::class);
    Route::get('people-export', [PersonController::class, 'export'])->name('people.export');
    // Cuentas bancarias personales por persona (AJAX)
    Route::get('people/{person}/personal-accounts', [PersonBankAccountController::class, 'index'])->name('people.personal-accounts.index');
    Route::post('people/{person}/personal-accounts', [PersonBankAccountController::class, 'store'])->name('people.personal-accounts.store');
    Route::put('people/{person}/personal-accounts/{account}', [PersonBankAccountController::class, 'update'])->name('people.personal-accounts.update');
    Route::delete('people/{person}/personal-accounts/{account}', [PersonBankAccountController::class, 'destroy'])->name('people.personal-accounts.destroy');

    // Rutas de Bancos
    Route::resource('banks', BankController::class);

    // Rutas de Tipos de Cuenta
    Route::resource('account-types', AccountTypeController::class);

    // Rutas de Usuarios (solo admin/boss)
    Route::resource('users', UserController::class)->middleware('role:boss');
    Route::get('datatables/users', [UserDataTableController::class, 'index'])->name('datatables.users')->middleware('role:boss');

    // Rutas de Equipos (comentado - controlador eliminado)
    // Route::resource('teams', TeamController::class);

    // Rutas para Aprobaciones
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('approvals/transactions/{transaction}/approve', [ApprovalController::class, 'approveTransaction'])->name('approvals.transactions.approve');
    Route::post('approvals/transactions/{transaction}/reject', [ApprovalController::class, 'rejectTransaction'])->name('approvals.transactions.reject');
    Route::post('approvals/expenses/{expense}/approve', [ApprovalController::class, 'approveExpense'])->name('approvals.expenses.approve');
    Route::post('approvals/expenses/{expense}/reject', [ApprovalController::class, 'rejectExpense'])->name('approvals.expenses.reject');

    // Rutas DataTables AJAX
    Route::get('datatables/people', [PersonDataTableController::class, 'index'])->name('datatables.people');
    Route::get('datatables/approvals/transactions', [ApprovalDataTableController::class, 'transactions'])->name('datatables.approvals.transactions');
    Route::get('datatables/approvals/expenses', [ApprovalDataTableController::class, 'expenses'])->name('datatables.approvals.expenses');
    // Route::get('datatables/teams', [TeamDataTableController::class, 'index'])->name('datatables.teams'); // Comentado - controlador eliminado
    Route::get('datatables/accounts', [AccountDataTableController::class, 'index'])->name('datatables.accounts');
    Route::get('datatables/transactions', [TransactionDataTableController::class, 'index'])->name('datatables.transactions');
    Route::get('datatables/expenses', [ExpenseDataTableController::class, 'index'])->name('datatables.expenses');
    Route::get('datatables/banks', [BankDataTableController::class, 'index'])->name('datatables.banks');
    Route::get('datatables/account-types', [AccountTypeDataTableController::class, 'index'])->name('datatables.account-types');

    // Rutas de Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Herramientas de Administración
    Route::get('/admin/tools', [ToolsController::class, 'index'])->name('admin.tools');
    Route::post('/admin/tools/normalize', [ToolsController::class, 'normalizeLegacyTransactions'])->name('admin.tools.normalize');
});

require __DIR__ . '/auth.php';
