<?php

namespace EmplifySoftware\StatamicGoogleReviews;

use App\Http\Controllers\LosysController;
use EmplifySoftware\StatamicGoogleReviews\Http\Controllers\GoogleReviewsSettingsController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\YAML;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{

    public const BLUEPRINTS_PATH = __DIR__.'/../resources/blueprints/';

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    public function bootAddon(): void
    {
        $this->createPlacesTaxonomy();
        $this->createReviewsCollection();
        $this->addSettingsTab();

        $this->publishes([
            __DIR__.'/../config/statamic-google-reviews.php' => config_path('statamic-google-reviews.php'),
        ], 'statamic-google-reviews');
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('google-reviews:crawl')->hourly();
    }

    // TODO: fix cp routes
    private function addSettingsTab(): void
    {
//        Nav::extend(function ($nav) {
//            $nav->create('Google Reviews')
//                ->section('Tools')
//                ->icon('toggle')
//                ->route('google-reviews.settings');
//        });
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
