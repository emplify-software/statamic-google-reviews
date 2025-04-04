<?php

namespace EmplifySoftware\StatamicGoogleReviews;

use App\Listeners\PreventDeletingMounts;
use EmplifySoftware\StatamicGoogleReviews\Http\Controllers\GoogleReviewsUtilityController;
use EmplifySoftware\StatamicGoogleReviews\Listeners\GoogleReviewPlacesUpdates;
use Illuminate\Support\Facades\File;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Utility;
use Statamic\Facades\YAML;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{

    public const BLUEPRINTS_PATH = __DIR__.'/../resources/blueprints/';

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
        $this->createPlacesTaxonomy();
        $this->createReviewsCollection();
        $this->addSettingsTab();
        $this->registerCommands();

        $this->publishes([
            __DIR__.'/../config/statamic-google-reviews.php' => config_path('statamic-google-reviews.php'),
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

    private function createReviewsCollection(): void
    {
        if (!Collection::find('google-reviews')) {

            // create collection
            $collection = Collection::make('google-reviews')
                ->title('Google Reviews')
                ->structureContents([
                    'max_depth' => 1,
                ]);
            $collection->save();

            // create blueprint
            $blueprintContents = YAML::parse(File::get(self::BLUEPRINTS_PATH . 'review.yaml'));
            Blueprint::make()
                ->setContents($blueprintContents)
                ->setHandle('review')
                ->setNamespace('collections.' . $collection->handle())
                ->save();

        }
    }

    private function createPlacesTaxonomy(): void
    {
        if (!Taxonomy::find('google-review-places')) {

            // create taxonomy
            $taxonomy = Taxonomy::make('google-review-places')
                ->title('Google Review Places');
            $taxonomy->save();

            // create blueprint
            $blueprintContents = YAML::parse(File::get(self::BLUEPRINTS_PATH . 'place.yaml'));
            Blueprint::make()
                ->setContents($blueprintContents)
                ->setHandle('place')
                ->setNamespace('taxonomies.' . $taxonomy->handle())
                ->save();
        }
    }
}
