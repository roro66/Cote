<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DataTables\PersonDataTableController;
use App\Http\Controllers\DataTables\TeamDataTableController;
use App\Http\Controllers\DataTables\AccountDataTableController;
use App\Http\Controllers\DataTables\TransactionDataTableController;
use App\Http\Controllers\DataTables\ExpenseDataTableController;
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
    
    // Rutas de Cuentas
    Route::resource('accounts', AccountController::class);
    
    // Rutas de Transacciones  
    Route::resource('transactions', TransactionController::class);
    
    // Rutas de Gastos
    Route::resource('expenses', ExpenseController::class);
    
    // Rutas de Personas
    Route::resource('people', PersonController::class);
    Route::get('people-export', [PersonController::class, 'export'])->name('people.export');
    
    // Rutas de Equipos (mantenemos solo para no romper referencias existentes)
    Route::resource('teams', TeamController::class);
    
    // Rutas para Aprobaciones
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('approvals/transactions/{transaction}/approve', [ApprovalController::class, 'approveTransaction'])->name('approvals.transactions.approve');
    Route::post('approvals/transactions/{transaction}/reject', [ApprovalController::class, 'rejectTransaction'])->name('approvals.transactions.reject');
    Route::post('approvals/expenses/{expense}/approve', [ApprovalController::class, 'approveExpense'])->name('approvals.expenses.approve');
    Route::post('approvals/expenses/{expense}/reject', [ApprovalController::class, 'rejectExpense'])->name('approvals.expenses.reject');
    
    // Rutas DataTables AJAX
    Route::get('datatables/people', [PersonDataTableController::class, 'index'])->name('datatables.people');
    Route::get('datatables/teams', [TeamDataTableController::class, 'index'])->name('datatables.teams');
    Route::get('datatables/accounts', [AccountDataTableController::class, 'index'])->name('datatables.accounts');
    Route::get('datatables/transactions', [TransactionDataTableController::class, 'index'])->name('datatables.transactions');
    Route::get('datatables/expenses', [ExpenseDataTableController::class, 'index'])->name('datatables.expenses');
    
    // Rutas de Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
