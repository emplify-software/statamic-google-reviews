@php use Statamic\Support\Arr  @endphp

@extends('statamic::layout')
@section('title', Statamic::crumb(__('Google Reviews'), __('Utilities')))

@section('content')

    <header class="mb-6">
        @include('statamic::partials.breadcrumb', [
            'url' => cp_route('utilities.index'),
            'title' => __('Utilities')
        ])
        <h1>{{ __('Google Reviews') }}</h1>
    </header>

    <div class="mt-3 text-sm card">
        <p class="text-gray-700">
            This view shows the current status of the Google Review imports from the Google Places API.
            You can manually trigger a sync of the latest reviews by clicking the "Update Reviews" button.
        </p>

        <hr class="my-4">

        <div class="flex items-start justify-between gap-3">

            <div>
                <b>Last update:</b> <span id="last-update"></span><br>
                <b>Locale:</b> <span>{{ strtoupper($locale) }}</span>
            </div>

            <div>
                <button id="crawl-button" onclick="crawl()" class="btn btn-primary mr-1">
                    Update Reviews
                    <svg id="button-spinner" class="ml-3" style="display: none; overflow: visible"
                         xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 40 40"
                         stroke="#ffffff">
                        <g fill="none" fill-rule="evenodd">
                            <g transform="translate(2 2)" stroke-width="4">
                                <circle stroke-opacity=".5" cx="18" cy="18" r="18"></circle>
                                <path d="M36 18c0-9.94-8.06-18-18-18">
                                    <animateTransform attributeName="transform" type="rotate" from="0 18 18"
                                                      to="360 18 18" dur="1s"
                                                      repeatCount="indefinite">
                                    </animateTransform>
                                </path>
                            </g>
                        </g>
                    </svg>
                </button>
                <a href="/cp/collections/google-reviews">
                    <button class="btn">
                        Show all Reviews
                        <div class="svg-icon using-svg">
                            @cp_svg('icons/micro/chevron-right')
                        </div>
                    </button>
                </a>
            </div>
        </div>


        @if($error)
            <div class="mt-2">
                <div style="background: #ef44451a; color: #ef4445"
                     class="px-2 py-1 text-xs rounded">
                    <b>Error:</b> {{ $error }}
                </div>
            </div>
        @endif
    </div>

    <div class="mt-3 text-sm card">

        <h2 class="mb-3">Places</h2>

        <p class="text-gray-700">
            You can add or remove places under <a href="/cp/taxonomies/google-review-places">Taxonomies > Google Review Places</a>.
        </p>

        <div class="mt-5 flex flex-col gap-2">
            @forelse($places as $place)
                <div class="p-3 badge-pill-sm border bg-white dark:bg-dark-700 dark:border-dark-900"
                    @if(array_key_exists('error', $place))
                        style="border-color: #ef4445!important;background-color: #ef444511!important"
                    @endif>
                    <div>
                        <div class="flex w-full justify-between">
                            <a href="/cp/taxonomies/google-review-places/terms/{{ $place['slug'] }}">
                                <h3 class="font-bold">{{ $place['name'] }}</h3>
                                <p class="text-gray dark:text-dark-150 text-sm">
                                    Place ID: {{ $place['place_id'] }}
                                </p>
                            </a>
                            @if(!array_key_exists('error', $place))
                            <div class="flex gap-6 items-center">
                                <div class="">
                                    <div class="font-bold">{{ $place['stored_reviews'] }} / {{ $place['total_reviews'] }}</div>
                                    <div class="text-gray dark:text-dark-150 text-sm">Reviews fetched</div>
                                </div>
                                <a data-place-id="{{ $place['place_id'] }}">
                                    <button class="btn">
                                        Show Reviews
                                        <div class="svg-icon using-svg">
                                            @cp_svg('icons/micro/chevron-right')
                                        </div>
                                    </button>
                                </a>
                            </div>
                            @endif
                        </div>
                        @if(array_key_exists('error', $place))
                        <div class="mt-2">
                            <div style="background: #ef44451a; color: #ef4445"
                                 class="px-2 py-1 text-xs rounded">
                                <b>Error:</b> {{ $place['error'] }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            @empty
                <div>
                    <p>No places found.</p>
                </div>
            @endforelse
        </div>

    </div>

    <script>

        /**
         * Trigger a new crawl and reload the page when finished
         */
        async function crawl() {
            setButtonLoading();
            await fetch("/!/statamic-google-reviews/update");
            window.location.reload();
        }

        /**
         * Set the button to a loading state
         */
        function setButtonLoading() {
            document.getElementById('crawl-button').disabled = true;
            document.getElementById('button-spinner').style.display = 'inline-block';
        }

        const rtf = new Intl.RelativeTimeFormat('en', {numeric: 'auto'})
        const units = {
            year: 24 * 60 * 60 * 1000 * 365,
            month: 24 * 60 * 60 * 1000 * 365 / 12,
            day: 24 * 60 * 60 * 1000,
            hour: 60 * 60 * 1000,
            minute: 60 * 1000,
            second: 1000
        }

        function getRelativeTime(d1, d2 = new Date()) {
            if (d1 < 0) return "-";
            const elapsed = d1 - d2
            for (const u in units)
                if (Math.abs(elapsed) > units[u] || u === 'second')
                    return rtf.format(Math.round(elapsed / units[u]), u)
        }


        function updateRelativeTime(time) {
            document.getElementById('last-update').innerText = getRelativeTime(time * 1000);
        }

        setInterval(() => {
            updateRelativeTime({{ $lastUpdate }});
        }, 1000);
        updateRelativeTime({{ $lastUpdate }});

        function createLinks() {
            const links = document.querySelectorAll('[data-place-id]');
            for (const link of links) {
                const place = link.getAttribute('data-place-id').replace(/_+/g, '_');
                const filterJSON = {
                    "fields":{
                        is_from_crawler: {value: "true"},
                        // Currently not working with place selector, checking slug as fallback
                        //place: {operator:"like", term: location}
                        slug: {operator: "like", value: place},
                    }
                }
                const filters = btoa(JSON.stringify(filterJSON));
                link.href = `/cp/collections/google-reviews?filters=${filters}`;
            }
        }

        createLinks();

    </script>

@stop
