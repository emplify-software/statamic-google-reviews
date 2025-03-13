<?php
use EmplifySoftware\StatamicGoogleReviews\Http\Controllers\GoogleReviewsUtilityController;
use Illuminate\Support\Facades\Route;
\Log::info('Custom CP routes loaded!'); // Debugging Log

Route::prefix('google-reviews')->name('google-reviews.')->group(function () {
    Route::get('/', [GoogleReviewsUtilityController::class, 'settings'])->name('settings');
    Route::post('/', [GoogleReviewsUtilityController::class, 'update'])->name('update');
});
