<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\BudgetController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user', [UserController::class, 'get']);
    Route::get('/user/{id}', [UserController::class, 'get']);
    Route::put('/user/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);

    Route::get('/logout', [AuthController::class, 'logout']);

    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/category/{id}', [CategoryController::class, 'get']);
    Route::post('/category', [CategoryController::class, 'store']);
    Route::put('/category/{id}', [CategoryController::class, 'update']);
    Route::delete('/category/{id}', [CategoryController::class, 'destroy']);
    Route::get('/category-percentage', [CategoryController::class, 'getCategoryPercentage']);

    Route::group(['prefix' => 'income'], function () {
        Route::get('/', [IncomeController::class, 'index']);
        Route::get('/{id}', [IncomeController::class, 'get']);
        Route::post('/', [IncomeController::class, 'store']);
        Route::put('/{id}', [IncomeController::class, 'update']);
        Route::delete('/{id}', [IncomeController::class, 'destroy']);
    });
    Route::post('income-bulk-add-update', [IncomeController::class, 'bulkAddAndUpdate']);
    Route::get('income-monthly-total', [IncomeController::class, 'getMonthlyTotal']);

    // Route::get('/budget', [BudgetController::class, 'index']);
    Route::get('/budget', [BudgetController::class, 'get']);
    Route::post('/budget-bulk-update', [BudgetController::class, 'bulkAddAndUpdate']);
    Route::delete('/budget/{id}', [BudgetController::class, 'destroy']);
    Route::get('/budget-summary', [BudgetController::class, 'getBudgetSummary']);

    Route::get('/expenses', [ExpensesController::class, 'index']);
    Route::get('/expenses/{id}', [ExpensesController::class, 'get']);
    Route::post('/expenses', [ExpensesController::class, 'store']);
    Route::put('/expenses/{id}', [ExpensesController::class, 'update']);
    Route::post('/expenses-bulk-add-update', [ExpensesController::class, 'bulkAddAndUpdate']);
    Route::delete('/expenses/{id}', [ExpensesController::class, 'destroy']);
    Route::get('/expenses-summary', [ExpensesController::class, 'getExpensesSummary']);
    Route::get('/expenses-total-spent', [ExpensesController::class, 'getExpensesSpent']);
});
