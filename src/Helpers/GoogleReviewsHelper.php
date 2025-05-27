<?php

namespace EmplifySoftware\StatamicGoogleReviews\Helpers;
use Illuminate\Support\Facades\App;

class GoogleReviewsHelper
{
    public static function getLocale(): string {
        return config('statamic-google-reviews.language') ?? App::currentLocale() ?? 'en';
    }
}