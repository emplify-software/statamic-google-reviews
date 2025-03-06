<?php
use EmplifySoftware\StatamicGoogleReviews\Http\Controllers\GoogleReviewsSettingsController;
use Illuminate\Support\Facades\Route;
\Log::info('Custom CP routes loaded!'); // Debugging Log

Route::prefix('google-reviews')->name('google-reviews.')->group(function () {
    Route::get('/', [GoogleReviewsSettingsController::class, 'settings'])->name('settings');
    Route::post('/', [GoogleReviewsSettingsController::class, 'update'])->name('update');
});
