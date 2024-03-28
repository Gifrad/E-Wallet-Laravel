<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataPlanController;
use App\Http\Controllers\Api\OperatorCardController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\TipsController;
use App\Http\Controllers\Api\TopUpController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\TransferHistoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('jwt.verify')->get('test', function (Request $request) {
//     return $request->user();
// });

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('is-email-exists', [UserController::class, 'isEmailExist']);
// Route::post("refresh", [AuthController::class, 'refresh']);
Route::post('webhooks', [WebhookController::class, 'update']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('top-ups', [TopUpController::class, 'store']);
    Route::post('transfers', [TransferController::class, 'store']);
    Route::post('data-plans', [DataPlanController::class, 'store']);
    Route::get('operator-cards', [OperatorCardController::class, 'index']);
    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('transfer-histories', [TransferHistoryController::class, 'index']);
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('users', [UserController::class, 'show']);
    Route::get('users/{username}', [UserController::class, 'getByUsername']);
    Route::put('users', [UserController::class, 'update']);
    Route::get('wallets', [WalletController::class, 'show']);
    Route::put('wallets', [WalletController::class, 'update']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('tips', [TipsController::class, 'index']);
});
