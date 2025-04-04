<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Maps API Key
    |--------------------------------------------------------------------------
    |
    | The API key for the Google Maps API. The key needs to have access to the
    | Google Places API.
    |
    */
    'api_key' => env('GOOGLE_REVIEWS_API_KEY', ''),


    /*
    |--------------------------------------------------------------------------
    | Review Language
    |--------------------------------------------------------------------------
    |
    | The language in which the review texts should be fetched. This should be
    | a valid language code (e.g. 'de' for German). If no language is set,
    | the current locale of the application will be used.
    |
    */
    'language' => env('GOOGLE_REVIEWS_LANGUAGE'),

    /*
    |--------------------------------------------------------------------------
    | Legacy API
    |--------------------------------------------------------------------------
    |
    | If you want to use the legacy Google Places API, set this to true.
    | This will use the old API endpoint to fetch the reviews.
    |
    */
    'legacy_api' => env('GOOGLE_REVIEWS_LEGACY_API', false),
];
