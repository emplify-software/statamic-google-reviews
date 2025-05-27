<?php

namespace EmplifySoftware\StatamicGoogleReviews\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Statamic\Facades\YAML;

class GoogleReviewsUtilityController extends Controller
{
    /**
     * Display the Google Reviews utility.
     */
    public function utility()
    {
        $status = $this->getStatus();
        $lastUpdate = $status['lastUpdate'] ?? -1;
        $places = $status['places'] ?? [];
        $error = $status['error'] ?? null;

        return view('statamic-google-reviews::google-reviews-utility', [
            'lastUpdate' => $lastUpdate,
            'places' => $places,
            'error' => $error,
        ]);

    }

    /**
     * Crawls the latest Google Reviews.
     */
    public function update(): JsonResponse
    {
        $exitCode = Artisan::call('google-reviews:crawl');
        $success = $exitCode === 0;

        return response()
            ->json([
                'status' => $success ? 'success' : 'error',
            ], $success ? 200 : 500);
    }

    /*
     * Get the crawler status from the status file.
     * @return array|null The status data or null if the file does not exist.
     */
    protected function getStatus(): ?array
    {
        $statusFile = storage_path('google-reviews/status.yaml');
        if (! file_exists($statusFile)) {
            return null;
        }

        return YAML::parse(file_get_contents($statusFile));
    }

}
