<?php
use EmplifySoftware\StatamicGoogleReviews\Http\Controllers\GoogleReviewsUtilityController;
use Illuminate\Support\Facades\Route;

Route::get('/update', [GoogleReviewsUtilityController::class, 'update'])->name('update');
