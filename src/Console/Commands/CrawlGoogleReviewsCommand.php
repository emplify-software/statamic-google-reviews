<?php

namespace EmplifySoftware\StatamicGoogleReviews\Console\Commands;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Taxonomies\LocalizedTerm;

class CrawlGoogleReviewsCommand extends Command
{
    const string API_URL_LEGACY = "https://maps.googleapis.com/maps/api/place/details/json";
    const string API_URL = "https://places.googleapis.com/v1/places/";


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-reviews:crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl Google Maps API for place reviews.';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        $this->info("Crawling Google Maps API...");

        $places = Taxonomy::find('google-review-places')->queryTerms()->get();
        $lang = config('statamic-google-reviews.language', App::currentLocale());

        foreach ($places as $place) {
            $this->crawlPlace($place, $lang);
        }
    }

    /**
     * @throws Exception
     */
    private function crawlPlace(LocalizedTerm $place, string $lang) {

        $name = $place->get('title');
        $placeId = $place->get('place_id');

        $this->info("\nCrawling place \"$name\" (place ID: $placeId)");

        $reviews = $this->getReviewsForPlace($placeId, $lang);

        foreach ($reviews as $review) {
            // get slug from author_url: https://www.google.com/maps/contrib/x/reviews -> x
            $slug = $placeId . '-' . explode('/', $review['author_url'])[5];
            // invalid slug name: multiple underscore, replace with single underscore
            $slug = preg_replace('/_+/', '_', $slug);
            $authorName = $review['author_name'];

            $data = [
                'title' => $review['author_name'],
                'author_name' => $authorName,
                'time' => $review['time'],
                'rating' => $review['rating'],
                'profile_photo_url' => $review['profile_photo_url'],
                'text' => $review['text'],
                'place' => $place->slug(),
            ];

            // upsert
            if ($entry = Entry::query()->where('slug', $slug)->where('collection', 'google-reviews')->first()) {
                // check if manual override is enabled
                if ($entry->get('manual_override')) {
                    $this->warn("- Skipping entry for review from \"$authorName\" due to manual override");
                }
                else {
                    $this->info("* Updating entry for review from \"$authorName\"");
                    $entry->data($data);
                    $entry->save();
                }
            }
            else {
                $this->info("+ Creating new entry for review from \"$authorName\"");
                Entry::make()
                    ->collection('google-reviews')
                    ->blueprint('review')
                    ->slug($slug)
                    ->data($data)
                    ->save();
            }
        }
    }

    private function getReviewsForPlace(string $placeId, string $lang) {
        $useLegacyApi = config('statamic-google-reviews.legacy_api', false);

        if ($useLegacyApi) {
            return $this->getReviewsForPlaceLegacyAPI($placeId, $lang);
        }
        else {
            return $this->getReviewsForPlaceNewAPI($placeId, $lang);
        }
    }

    private function getReviewsForPlaceNewAPI(string $placeId, string $lang) {
        $apiKey = config('statamic-google-reviews.api_key');

        if (!$apiKey) {
            throw new Exception("No Google Maps API key found. Please set a GOOGLE_REVIEWS_API_KEY in your .env file.");
        }

        $response = Http::get(self::API_URL . $placeId, [
            'fields' => 'reviews',
            'key' => $apiKey,
            'languageCode' => $lang,
        ]);

        $reviews = $response->json()['reviews'];

        return array_map(function($review) {
            return [
                'author_name' => $review['authorAttribution']['displayName'],
                'author_url' => $review['authorAttribution']['uri'],
                'time' => $review['publishTime'],
                'rating' => $review['rating'],
                'profile_photo_url' => $review['authorAttribution']['photoUri'],
                'text' => $review['text']['text'],
            ];
        }, $reviews);
    }

    private function getReviewsForPlaceLegacyAPI(string $placeId, string $lang) {
        $apiKey = config('statamic-google-reviews.api_key');

        if (!$apiKey) {
            throw new Exception("No Google Maps API key found. Please set a GOOGLE_REVIEWS_API_KEY in your .env file.");
        }

        $response = Http::get(self::API_URL_LEGACY, [
            'place_id' => $placeId,
            'key' => $apiKey,
            'language' => $lang,
            'reviews_sort' => 'newest',
        ]);

        $result = $response->json()['result'];
        $reviews = $result['reviews'];

        return $reviews;
    }
}
