<?php

namespace EmplifySoftware\StatamicGoogleReviews\Console\Commands;
use EmplifySoftware\StatamicGoogleReviews\Helpers\GoogleReviewsHelper;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\YAML;
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
    protected $signature = 'emplify-software:google-reviews:crawl';

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
        $placesData = [];

        try {
            $places = Taxonomy::find('google-review-places')->queryTerms()->get();
            $lang = GoogleReviewsHelper::getLocale();

            foreach ($places as $place) {
                $placeData = [];
                try {
                    $totalReviews = $this->crawlPlace($place, $lang);
                    $placeData['total_reviews'] = $totalReviews;
                }
                catch (Exception $e) {
                    $placeError = $e->getMessage();
                    $this->error("Crawling place failed: $placeError");
                    $placeData['total_reviews'] = 0;
                    $placeData['error'] = $placeError;
                }
                finally {
                    $placeData = array_merge($placeData, $this->getPlaceStats($place));
                    $placesData []= $placeData;
                }
            }
            $this->info("Crawling finished.");
            $this->saveStatus($placesData);
        }
        catch (Exception $e) {
            $error = $e->getMessage();
            $this->error("Crawling failed: $error");
            $this->saveStatus($placesData, $error);
            throw new Exception("Review crawling failed: $error");
        }

        // if any place has an error, throw an exception
        foreach ($placesData as $placeData) {
            if (isset($placeData['error'])) {
                throw new Exception("Crawling failed for place: " . $placeData['name'] . " - " . $placeData['error']);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function crawlPlace(LocalizedTerm $place, string $lang): int {

        $name = $place->get('title');
        $placeId = $place->get('place_id');

        $this->info("\nCrawling place \"$name\" (place ID: $placeId)");

        $reviewData = $this->getReviewsForPlace($placeId, $lang);
        $reviews = $reviewData['reviews'];
        $totalReviews = $reviewData['total_reviews'];

        foreach ($reviews as $review) {
            // get slug from author_url: https://www.google.com/maps/contrib/x/reviews -> x
            $slug = $placeId . '-' . explode('/', $review['author_url'])[5];
            // invalid slug name: multiple underscore, replace with single underscore
            $slug = preg_replace('/_+/', '_', $slug);
            $authorName = $review['author_name'];

            $data = [
                'title' => $authorName,
                'author_name' => $authorName,
                'time' => $review['time'],
                'rating' => $review['rating'],
                'profile_photo_url' => $review['profile_photo_url'],
                'text' => $review['text'],
                'place' => $place->slug(),
                'is_from_crawler' => true,
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

        return $totalReviews;
    }

    private function getPlaceStats(LocalizedTerm $place) {
        // get total number of reviews for this place currently in the collection
        $reviewsQuery = Entry::query()
            ->where('collection', 'google-reviews')
            ->where('place', $place->slug());

        $storedReviews = $reviewsQuery->count();
        $storedCrawledReviews = $reviewsQuery->where('is_from_crawler', true)->count();

        return [
            'place_id' => $place->get('place_id'),
            'name' => $place->get('title'),
            'slug' => $place->slug(),
            'stored_reviews' => $storedReviews,
            'stored_crawled_reviews' => $storedCrawledReviews
        ];
    }

    /**
     * @throws Exception
     */
    private function getReviewsForPlace(string $placeId, string $lang): array {
        $useLegacyApi = config('statamic-google-reviews.legacy_api', false);

        if ($useLegacyApi) {
            return $this->getReviewsForPlaceLegacyAPI($placeId, $lang);
        }
        else {
            return $this->getReviewsForPlaceNewAPI($placeId, $lang);
        }
    }

    /**
     * @throws Exception
     */
    private function getReviewsForPlaceNewAPI(string $placeId, string $lang): array {
        $apiKey = config('statamic-google-reviews.api_key');

        if (!$apiKey) {
            throw new Exception("No Google Maps API key found. Please set a GOOGLE_REVIEWS_API_KEY in your .env file.");
        }

        $response = Http::get(self::API_URL . $placeId, [
            'fields' => 'userRatingCount,reviews',
            'key' => $apiKey,
            'languageCode' => $lang,
        ]);


        if ($response->failed()) {
            throw new Exception($response->json()['error']['message']);
        }

        $result = $response->json();
        $reviews = $result['reviews'];

        return [
            'reviews' => array_map(function($review) {
                return [
                    'author_name' => $review['authorAttribution']['displayName'],
                    'author_url' => $review['authorAttribution']['uri'],
                    'time' => $review['publishTime'],
                    'rating' => $review['rating'],
                    'profile_photo_url' => $review['authorAttribution']['photoUri'],
                    'text' => $review['text']['text'],
                ];
            }, $reviews),
            'total_reviews' => $result['userRatingCount']
        ];
    }

    /**
     * @throws Exception
     */
    private function getReviewsForPlaceLegacyAPI(string $placeId, string $lang): array {
        $apiKey = config('statamic-google-reviews.api_key');

        if (!$apiKey) {
            throw new Exception("No Google Maps API key found. Please set a GOOGLE_REVIEWS_API_KEY in your .env file.");
        }

        $response = Http::get(self::API_URL_LEGACY, [
            'place_id' => $placeId,
            'key' => $apiKey,
            'language' => $lang,
            'reviews_sort' => 'newest',
        ])->json();

        if (array_key_exists('error_message', $response)) {
            throw new Exception($response['error_message']);
        }

        $result = $response['result'];
        return [
            'reviews' => $result['reviews'],
            'total_reviews' => $result['user_ratings_total'],
        ];
    }

    /**
     * Save the status of the crawler to a yaml file.
     *
     * @param array $placesData  Current status of the places
     * @param string|null $error  error message if the crawler failed
     */
    protected function saveStatus(array $placesData, ?string $error = null): void
    {
        $status = [
            'lastUpdate' => now()->timestamp,
            'places' => $placesData,
            'error' => $error,
        ];

        // make sure the directory exists
        if (!file_exists(storage_path('google-reviews'))) {
            mkdir(storage_path('google-reviews'));
        }
        // save yaml file in google-reviews/status.yaml
        $statusFile = storage_path('google-reviews/status.yaml');
        file_put_contents($statusFile, YAML::dump($status));
    }
}
