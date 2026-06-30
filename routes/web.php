<?php

use App\Http\Controllers\AlertsController;
use App\Http\Controllers\RevistaArmasController;
use App\Http\Controllers\RevistaGuestAuthController;
use App\Http\Controllers\RevistaGuestWeaponController;
use App\Http\Controllers\RevistaPhotoReviewController;
use App\Http\Controllers\RevistaPhotoStagingController;
use App\Http\Controllers\TemporaryPhotoAccessController;
use App\Http\Controllers\TemporaryPhotoUserController;
use App\Http\Controllers\AuthenticatedPermitImageController;
use App\Http\Controllers\Auth\ForcedPasswordChangeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormatController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\WeaponCustodyController;
use App\Http\Controllers\WeaponCustodyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResponsiblePortfolioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeaponClientAssignmentController;
use App\Http\Controllers\WeaponController;
use App\Http\Controllers\WeaponDocumentController;
use App\Http\Controllers\WeaponImportController;
use App\Http\Controllers\WeaponIncidentController;
use App\Http\Controllers\WeaponIncidentReportController;
use App\Http\Controllers\WeaponInternalAssignmentController;
use App\Http\Controllers\WeaponPhotoController;
use App\Http\Controllers\WeaponTransferController;
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

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.metrics');

Route::prefix('revista-armas')->name('revista-armas.')->group(function () {
    Route::middleware('revista.guest.guest')->group(function () {
        Route::get('/ingreso', [RevistaGuestAuthController::class, 'showLogin'])->name('guest.login');
        Route::post('/ingreso', [RevistaGuestAuthController::class, 'login'])->name('guest.login.store');
    });

    Route::middleware('revista.guest')->group(function () {
        Route::post('/salir', [RevistaGuestAuthController::class, 'logout'])->name('guest.logout');
        Route::get('/mis-armas', [RevistaGuestWeaponController::class, 'index'])->name('guest.weapons.index');
        Route::get('/mis-armas/{weapon}/estado', [RevistaGuestWeaponController::class, 'stagingState'])->name('guest.weapons.staging-state');
        Route::post('/mis-armas/{weapon}/fotos', [RevistaPhotoStagingController::class, 'storeGuest'])->name('guest.weapons.photos.store');
    });
});

Route::post('/locale', function (Request $request) {
    $locale = (string) $request->input('locale', 'es');
    $supportedLocales = ['es', 'en'];

    if (! in_array($locale, $supportedLocales, true)) {
        $locale = 'es';
    }

    $request->session()->put('locale', $locale);

    return back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::get('/password/change-required', [ForcedPasswordChangeController::class, 'edit'])->name('password.force.edit');
    Route::put('/password/change-required', [ForcedPasswordChangeController::class, 'update'])->name('password.force.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->whereUuid('id')
        ->name('notifications.read');

    Route::get('/posts/{post}/histories', [PostController::class, 'histories'])->name('posts.histories');
    Route::patch('/posts/{post}/restore', [PostController::class, 'restore'])->name('posts.restore');
    Route::resource('clients', ClientController::class)->except(['show']);
    Route::resource('posts', PostController::class)->except(['show']);
    Route::get('/workers/{worker}/histories', [WorkerController::class, 'histories'])->name('workers.histories');
    Route::patch('/workers/{worker}/restore', [WorkerController::class, 'restore'])->name('workers.restore');
    Route::resource('workers', WorkerController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::post('/users/{user}/send-access-credentials', [UserController::class, 'sendAccessCredentials'])->name('users.send-access-credentials');
    Route::get('/weapons/export-preview', [WeaponController::class, 'exportPreview'])->name('weapons.export.preview');
    Route::get('/weapons/export', [WeaponController::class, 'export'])->name('weapons.export');
    Route::post('/weapons/export-selected', [WeaponController::class, 'exportSelected'])->name('weapons.export.selected');
    Route::get('/weapons/filter-options', [WeaponController::class, 'filterOptions'])->name('weapons.filter_options');
    Route::resource('weapons', WeaponController::class);
    Route::get('/subir-armas', [WeaponImportController::class, 'index'])->name('weapon-imports.index');
    Route::get('/subir-armas/plantillas/armas', [WeaponImportController::class, 'downloadWeaponTemplate'])->name('weapon-imports.templates.weapon');
    Route::get('/subir-armas/plantillas/clientes', [WeaponImportController::class, 'downloadClientTemplate'])->name('weapon-imports.templates.client');
    Route::post('/subir-armas/preview', [WeaponImportController::class, 'preview'])->name('weapon-imports.preview');
    Route::get('/subir-armas/{weaponImportBatch}', [WeaponImportController::class, 'show'])->name('weapon-imports.show');
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
    Route::get('/authenticated-permit-images/{permit_kind}', [AuthenticatedPermitImageController::class, 'show'])
        ->whereIn('permit_kind', ['porte', 'tenencia'])
        ->name('authenticated-permit-images.show');

    Route::post('/weapon-imports/permit-authenticated/{permit_kind}', [WeaponImportController::class, 'updatePermitAuthenticated'])
        ->whereIn('permit_kind', ['porte', 'tenencia'])
        ->name('weapon-imports.permit-authenticated.update');

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

    Route::post('/weapon-incidents', [WeaponIncidentController::class, 'store'])
        ->name('weapon-incidents.store');
    Route::post('/weapon-incidents/{weaponIncident}/updates', [WeaponIncidentController::class, 'storeUpdate'])
        ->name('weapon-incidents.updates.store');
    Route::patch('/weapon-incidents/{weaponIncident}/reopen', [WeaponIncidentController::class, 'reopen'])
        ->name('weapon-incidents.reopen');
    Route::patch('/weapon-incidents/{weaponIncident}/close', [WeaponIncidentController::class, 'close'])
        ->name('weapon-incidents.close');
    Route::get('/weapon-incidents/{weaponIncident}/attachment', [WeaponIncidentController::class, 'downloadAttachment'])
        ->name('weapon-incidents.attachment');
    Route::get('/weapon-incidents/{weaponIncident}/updates/{weaponIncidentUpdate}/attachment', [WeaponIncidentController::class, 'downloadUpdateAttachment'])
        ->name('weapon-incidents.updates.attachment');

    Route::post('/weapons/{weapon}/internal-assignments', [WeaponInternalAssignmentController::class, 'store'])
        ->name('weapons.internal_assignments.store');
    Route::patch('/weapons/{weapon}/internal-assignments/retire', [WeaponInternalAssignmentController::class, 'retire'])
        ->name('weapons.internal_assignments.retire');
    Route::post('/weapons/{weapon}/custody/armerillo', [WeaponCustodyController::class, 'moveToArmerillo'])
        ->name('weapons.custody.armerillo');
    Route::post('/weapons/{weapon}/custody/para-mantenimiento', [WeaponCustodyController::class, 'moveToParaMantenimiento'])
        ->name('weapons.custody.para_mantenimiento');
    Route::post('/weapons/{weapon}/custody/armero', [WeaponCustodyController::class, 'moveToArmero'])
        ->name('weapons.custody.armero');
    Route::post('/weapons/{weapon}/custody/armero-posts', [WeaponCustodyController::class, 'storeArmeroPost'])
        ->name('weapons.custody.armero_posts.store');
    Route::patch('/weapons/{weapon}/imprints', [WeaponController::class, 'toggleImprint'])
        ->name('weapons.imprints.toggle');
    Route::get('/transfers', [WeaponTransferController::class, 'index'])->name('transfers.index');
    Route::post('/transfers/bulk', [WeaponTransferController::class, 'bulkStore'])->name('transfers.bulk');
    Route::patch('/transfers/{transfer}/accept', [WeaponTransferController::class, 'accept'])->name('transfers.accept');
    Route::patch('/transfers/{transfer}/cancel', [WeaponTransferController::class, 'cancel'])->name('transfers.cancel');

    Route::get('/mapa', [MapController::class, 'index'])->name('maps.index');
    Route::get('/mapa/armas', [MapController::class, 'weapons'])->name('maps.weapons');
    Route::get('/geocode/search', [GeocodingController::class, 'search'])->name('geocode.search');
    Route::get('/geocode/reverse', [GeocodingController::class, 'reverse'])->name('geocode.reverse');

    Route::get('/portfolios', [ResponsiblePortfolioController::class, 'index'])->name('portfolios.index');
    Route::get('/portfolios/{user}/edit', [ResponsiblePortfolioController::class, 'edit'])->name('portfolios.edit');
    Route::put('/portfolios/{user}', [ResponsiblePortfolioController::class, 'update'])->name('portfolios.update');
    Route::post('/portfolios/{user}/transfer', [ResponsiblePortfolioController::class, 'transfer'])->name('portfolios.transfer');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/formatos', [FormatController::class, 'index'])->name('formatos.index');
    Route::get('/formatos/revista-mensual/vacio', [FormatController::class, 'downloadEmptyMonthlyReview'])->name('formatos.revista-mensual.vacio');
    Route::get('/formatos/revista-mensual/armas', [FormatController::class, 'monthlyReviewWeapons'])->name('formatos.revista-mensual.armas');
    Route::get('/formatos/revista-mensual/opciones-columna', [FormatController::class, 'monthlyReviewColumnOptions'])->name('formatos.revista-mensual.column-options');
    Route::post('/formatos/revista-mensual/vista-previa', [FormatController::class, 'previewMonthlyReview'])->name('formatos.revista-mensual.vista-previa');
    Route::post('/formatos/revista-mensual/descargar', [FormatController::class, 'downloadMonthlyReview'])->name('formatos.revista-mensual.descargar');
    Route::get('/reports/assignments', [ReportController::class, 'weaponsByClient'])->name('reports.assignments');
    Route::get('/reports/no-destination', [ReportController::class, 'weaponsWithoutDestination'])->name('reports.no_destination');
    Route::get('/reports/history', [ReportController::class, 'history'])->name('reports.history');
    Route::get('/reports/audit', [ReportController::class, 'audit'])->name('reports.audit');
    Route::get('/reports/weapon-incidents/weapons/search', [WeaponIncidentReportController::class, 'searchWeapons'])->name('reports.weapon-incidents.weapons.search');
    Route::get('/reports/weapon-incidents', [WeaponIncidentReportController::class, 'index'])->name('reports.weapon-incidents.index');
    Route::get('/reports/weapon-incidents/{incidentType}', [WeaponIncidentReportController::class, 'show'])->name('reports.weapon-incidents.show');
    Route::get('/reports/weapon-custody', [WeaponCustodyReportController::class, 'index'])->name('reports.weapon-custody.index');

    Route::middleware('revista.staff')->prefix('revista-armas')->name('revista-armas.')->group(function () {
        Route::get('/', [RevistaArmasController::class, 'index'])->name('index');
        Route::get('/armas/{weapon}/revision/{temporary_photo_user}', [RevistaArmasController::class, 'review'])->name('review');
        Route::post('/accesos', [TemporaryPhotoAccessController::class, 'store'])->name('access.store');
        Route::post('/accesos/{grant}/revocar', [TemporaryPhotoAccessController::class, 'revoke'])->name('access.revoke');
        Route::post('/armas/{weapon}/revision/{temporary_photo_user}/aprobar', [RevistaPhotoReviewController::class, 'approve'])->name('review.approve');
        Route::post('/armas/{weapon}/revision/{temporary_photo_user}/rechazar', [RevistaPhotoReviewController::class, 'reject'])->name('review.reject');
        Route::resource('usuarios-temporales', TemporaryPhotoUserController::class)
            ->parameters(['usuarios-temporales' => 'temporary_photo_user'])
            ->names('temporary-users');
    });

    Route::get('/alerts/documents', [AlertsController::class, 'documents'])->name('alerts.documents');
    Route::post('/alerts/documents/preview', [AlertsController::class, 'previewBatch'])->name('alerts.documents.preview');
    Route::post('/alerts/documents/download', [AlertsController::class, 'downloadBatch'])->name('alerts.documents.download');
});

require __DIR__.'/auth.php';
