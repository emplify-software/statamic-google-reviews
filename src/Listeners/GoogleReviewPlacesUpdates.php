<?php

namespace EmplifySoftware\StatamicGoogleReviews\Listeners;

use Illuminate\Support\Facades\Artisan;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;

class GoogleReviewPlacesUpdates
{
    /**
     * Trigger the Google Reviews crawler when google-review-places taxonomy terms were updated.
     */
    public function handle(TermSaved|TermDeleted $event): void
    {
        // only handle google-review-places
        if ($event->term->taxonomy()->handle() !== 'google-review-places') {
            return;
        }

        // crawl
        Artisan::call('emplify-software:google-reviews:crawl');
    }
}
