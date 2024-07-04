<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\SuscriptoresController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth.basic')->get('/suscriptores/{id}/saldos', [SuscriptoresController::class, 'consultarSaldo']);

Route::middleware('auth.basic')->post('/cupones', [SuscriptoresController::class, 'generarAbono']);


