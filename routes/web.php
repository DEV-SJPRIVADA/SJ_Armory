<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AlertsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ResponsiblePortfolioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeaponController;
use App\Http\Controllers\WeaponClientAssignmentController;
use App\Http\Controllers\WeaponDocumentController;
use App\Http\Controllers\WeaponInternalAssignmentController;
use App\Http\Controllers\WeaponImportController;
use App\Http\Controllers\WeaponPhotoController;
use App\Http\Controllers\WeaponTransferController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\WorkerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/locale', function (Request $request) {
    $locale = (string) $request->input('locale', 'es');
    $supportedLocales = ['es', 'en'];

    if (!in_array($locale, $supportedLocales, true)) {
        $locale = 'es';
    }

    $request->session()->put('locale', $locale);

    return back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('clients', ClientController::class)->except(['show']);
    Route::resource('posts', PostController::class)->except(['show']);
    Route::resource('workers', WorkerController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::resource('weapons', WeaponController::class);
    Route::get('/subir-armas', [WeaponImportController::class, 'index'])->name('weapon-imports.index');
    Route::post('/subir-armas/preview', [WeaponImportController::class, 'preview'])->name('weapon-imports.preview');
    Route::post('/subir-armas/{weaponImportBatch}/execute/start', [WeaponImportController::class, 'startExecution'])->name('weapon-imports.start');
    Route::post('/subir-armas/{weaponImportBatch}/execute/process', [WeaponImportController::class, 'processExecution'])->name('weapon-imports.process');
    Route::get('/subir-armas/{weaponImportBatch}/execute/status', [WeaponImportController::class, 'executionStatus'])->name('weapon-imports.status');
    Route::post('/subir-armas/{weaponImportBatch}/execute', [WeaponImportController::class, 'execute'])->name('weapon-imports.execute');
    Route::post('/subir-armas/{weaponImportBatch}/discard', [WeaponImportController::class, 'discard'])->name('weapon-imports.discard');
    Route::post('/weapons/{weapon}/client-assignments', [WeaponClientAssignmentController::class, 'store'])
        ->name('weapons.client_assignments.store');
    Route::get('/weapons/{weapon}/permit', [WeaponController::class, 'permitPhoto'])
        ->name('weapons.permit');
    Route::patch('/weapons/{weapon}/permit', [WeaponController::class, 'updatePermitPhoto'])
        ->name('weapons.permit.update');

    Route::post('/weapons/{weapon}/photos', [WeaponPhotoController::class, 'store'])
        ->name('weapons.photos.store');
    Route::patch('/weapons/{weapon}/photos/{photo}', [WeaponPhotoController::class, 'update'])
        ->name('weapons.photos.update');
    Route::delete('/weapons/{weapon}/photos/{photo}', [WeaponPhotoController::class, 'destroy'])
        ->name('weapons.photos.destroy');

    Route::post('/weapons/{weapon}/documents', [WeaponDocumentController::class, 'store'])
        ->name('weapons.documents.store');
    Route::get('/weapons/{weapon}/documents/{document}/download', [WeaponDocumentController::class, 'download'])
        ->name('weapons.documents.download');
    Route::delete('/weapons/{weapon}/documents/{document}', [WeaponDocumentController::class, 'destroy'])
        ->name('weapons.documents.destroy');

    Route::post('/weapons/{weapon}/internal-assignments', [WeaponInternalAssignmentController::class, 'store'])
        ->name('weapons.internal_assignments.store');
    Route::patch('/weapons/{weapon}/internal-assignments/retire', [WeaponInternalAssignmentController::class, 'retire'])
        ->name('weapons.internal_assignments.retire');
    Route::patch('/weapons/{weapon}/imprints', [WeaponController::class, 'toggleImprint'])
        ->name('weapons.imprints.toggle');
    Route::get('/transfers', [WeaponTransferController::class, 'index'])->name('transfers.index');
    Route::post('/transfers/bulk', [WeaponTransferController::class, 'bulkStore'])->name('transfers.bulk');
    Route::patch('/transfers/{transfer}/accept', [WeaponTransferController::class, 'accept'])->name('transfers.accept');
    Route::patch('/transfers/{transfer}/reject', [WeaponTransferController::class, 'reject'])->name('transfers.reject');

    Route::get('/mapa', [MapController::class, 'index'])->name('maps.index');
    Route::get('/mapa/armas', [MapController::class, 'weapons'])->name('maps.weapons');
    Route::get('/geocode/reverse', [GeocodingController::class, 'reverse'])->name('geocode.reverse');

    Route::get('/portfolios', [ResponsiblePortfolioController::class, 'index'])->name('portfolios.index');
    Route::get('/portfolios/{user}/edit', [ResponsiblePortfolioController::class, 'edit'])->name('portfolios.edit');
    Route::put('/portfolios/{user}', [ResponsiblePortfolioController::class, 'update'])->name('portfolios.update');
    Route::post('/portfolios/{user}/transfer', [ResponsiblePortfolioController::class, 'transfer'])->name('portfolios.transfer');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/assignments', [ReportController::class, 'weaponsByClient'])->name('reports.assignments');
    Route::get('/reports/no-destination', [ReportController::class, 'weaponsWithoutDestination'])->name('reports.no_destination');
    Route::get('/reports/history', [ReportController::class, 'history'])->name('reports.history');
    Route::get('/reports/audit', [ReportController::class, 'audit'])->name('reports.audit');

    Route::get('/alerts/documents', [AlertsController::class, 'documents'])->name('alerts.documents');
    Route::post('/alerts/documents/download', [AlertsController::class, 'downloadBatch'])->name('alerts.documents.download');
});

require __DIR__.'/auth.php';
