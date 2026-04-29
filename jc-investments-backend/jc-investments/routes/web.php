<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\AdminController;

// ══════════════════════════════════════════
// RUTAS PÚBLICAS
// ══════════════════════════════════════════
Route::get('/', function () {
    return view('landing');
})->name('home');

// ══════════════════════════════════════════
// AUTENTICACIÓN
// ══════════════════════════════════════════
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/registro', [AuthController::class, 'showRegistro'])->name('registro');
    Route::post('/registro',[AuthController::class, 'registro']);
    Route::get('/recuperar',[AuthController::class, 'showRecuperar'])->name('recuperar');
    Route::post('/recuperar',[AuthController::class,'recuperar']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ══════════════════════════════════════════
// ÁREA CLIENTE (autenticado + rol cliente)
// ══════════════════════════════════════════
Route::middleware(['auth', 'role:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard',          [ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/solicitudes',        [SolicitudController::class, 'index'])->name('solicitudes');
    Route::get('/solicitudes/nueva',  [SolicitudController::class, 'create'])->name('solicitudes.nueva');
    Route::post('/solicitudes',       [SolicitudController::class, 'store'])->name('solicitudes.store');
    Route::get('/solicitudes/{id}',   [SolicitudController::class, 'show'])->name('solicitudes.show');
    Route::delete('/solicitudes/{id}',[SolicitudController::class, 'cancelar'])->name('solicitudes.cancelar');
    Route::get('/pagos',              [PagoController::class, 'index'])->name('pagos');
    Route::get('/perfil',             [ClienteController::class, 'perfil'])->name('perfil');
    Route::put('/perfil',             [ClienteController::class, 'updatePerfil'])->name('perfil.update');
});

// ══════════════════════════════════════════
// ÁREA ADMIN (autenticado + rol admin)
// ══════════════════════════════════════════
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard',                    [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/solicitudes',                  [AdminController::class, 'solicitudes'])->name('solicitudes');
    Route::get('/solicitudes/{id}',             [AdminController::class, 'verSolicitud'])->name('solicitudes.ver');
    Route::post('/solicitudes/{id}/aprobar',    [AdminController::class, 'aprobar'])->name('solicitudes.aprobar');
    Route::post('/solicitudes/{id}/rechazar',   [AdminController::class, 'rechazar'])->name('solicitudes.rechazar');
    Route::get('/usuarios',                     [AdminController::class, 'usuarios'])->name('usuarios');
    Route::get('/usuarios/{id}',                [AdminController::class, 'verUsuario'])->name('usuarios.ver');
    Route::delete('/usuarios/{id}',             [AdminController::class, 'eliminarUsuario'])->name('usuarios.eliminar');
    Route::get('/pagos',                        [AdminController::class, 'pagos'])->name('pagos');
    Route::post('/pagos',                       [PagoController::class, 'store'])->name('pagos.store');
    Route::get('/reportes',                     [AdminController::class, 'reportes'])->name('reportes');
    Route::get('/configuracion',                [AdminController::class, 'configuracion'])->name('configuracion');
    Route::put('/configuracion',                [AdminController::class, 'updateConfig'])->name('configuracion.update');
});

// ══════════════════════════════════════════
// ÁREA ANALISTA (autenticado + rol analista)
// ══════════════════════════════════════════
Route::middleware(['auth', 'role:analista'])->prefix('analista')->name('analista.')->group(function () {
    Route::get('/dashboard',              [AdminController::class, 'dashboardAnalista'])->name('dashboard');
    Route::get('/solicitudes',            [AdminController::class, 'solicitudes'])->name('solicitudes');
    Route::get('/solicitudes/{id}',       [AdminController::class, 'verSolicitud'])->name('solicitudes.ver');
    Route::post('/solicitudes/{id}/evaluar', [AdminController::class, 'evaluar'])->name('solicitudes.evaluar');
});
