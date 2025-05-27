<?php

namespace EmplifySoftware\StatamicGoogleReviews;

use App\Listeners\PreventDeletingMounts;
use EmplifySoftware\StatamicGoogleReviews\Http\Controllers\GoogleReviewsUtilityController;
use EmplifySoftware\StatamicGoogleReviews\Listeners\GoogleReviewPlacesUpdates;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $listen = [
        TermSaved::class => [
            GoogleReviewPlacesUpdates::class,
        ],
        TermDeleted::class => [
            GoogleReviewPlacesUpdates::class,
        ]
    ];

    public function bootAddon(): void
    {
        $this->addSettingsTab();
        $this->registerCommands();

        // config for statamic-google-reviews
        $this->publishes([
            __DIR__.'/../config/statamic-google-reviews.php' => config_path('statamic-google-reviews.php'),
        ], 'statamic-google-reviews');

        // blueprint for google-reviews collection
        $this->publishes([
            __DIR__.'/../resources/blueprints/review.yaml' => resource_path('blueprints/collections/google-reviews/review.yaml'),
        ], 'statamic-google-reviews');

        // blueprint for google-review-places taxonomy
        $this->publishes([
            __DIR__.'/../resources/blueprints/place.yaml' => resource_path('blueprints/taxonomies/google-review-places/place.yaml'),
        ], 'statamic-google-reviews');

        // content for google-reviews collection
        $this->publishes([
            __DIR__.'/../resources/content/google-reviews.yaml' => base_path('content/collections/google-reviews.yaml'),
        ], 'statamic-google-reviews');

        // content for google-review-places taxonomy
        $this->publishes([
            __DIR__.'/../resources/content/google-review-places.yaml' => base_path('content/taxonomies/google-review-places.yaml'),
        ], 'statamic-google-reviews');
    }

    private function registerCommands(): void
    {
        $this->commands([
            Console\Commands\CrawlGoogleReviewsCommand::class,
        ]);
    }

    private function addSettingsTab(): void
    {
        Utility::extend(function () {
            Utility::register('google-reviews')
                ->title('Google Reviews')
                ->action([GoogleReviewsUtilityController::class, 'utility'])
                ->description('Manage reviews from Google Places')
                ->icon('users');
        });
    }
}
