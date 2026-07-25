<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\InterventionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SatisfactionController;
use App\Http\Controllers\Api\SoftwareSolutionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes authentifiées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Incidents
    Route::apiResource('incidents', IncidentController::class);
    Route::post('/incidents/{incident}/take-over', [IncidentController::class, 'takeOver']);
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
    Route::get('/incidents/{incident}/transitions', [IncidentController::class, 'getAvailableTransitions']);

    // Interventions
    Route::get('/incidents/{incident}/interventions', [InterventionController::class, 'index']);
    Route::post('/incidents/{incident}/interventions', [InterventionController::class, 'store']);

    // Messages
    Route::get('/incidents/{incident}/messages', [MessageController::class, 'index']);
    Route::post('/incidents/{incident}/messages', [MessageController::class, 'store']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Satisfaction
    Route::get('/incidents/{incident}/satisfaction', [SatisfactionController::class, 'show']);
    Route::post('/incidents/{incident}/satisfaction', [SatisfactionController::class, 'store']);

    // Dashboard (adapté selon le rôle connecté)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Users (Admin uniquement)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
    });

        // Software Solutions
    // Consultation ouverte aux 3 rôles (filtrée par le contrôleur selon le rôle)
    Route::apiResource('software-solutions', SoftwareSolutionController::class)
        ->only(['index', 'show']);

    // Entreprises clientes (autorisation gérée par CompanyPolicy dans le contrôleur)
    Route::apiResource('companies', CompanyController::class);

    // Création/modification/suppression réservées à l'admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('software-solutions', SoftwareSolutionController::class)
            ->except(['index', 'show']);
    });
});