# Statamic Google Reviews

[![Statamic v4](https://img.shields.io/badge/Statamic-4.0-FF269E?style=for-the-badge&link=https://statamic.com)](https://statamic.com)
[![Statamic v5](https://img.shields.io/badge/Statamic-5.0-FF269E?style=for-the-badge&link=https://statamic.com)](https://statamic.com)

Display Google Reviews on your Statamic site using the Google Places API.

## Features

This Statamic addon fetches Google customer reviews for one or multiple locations using the Google Places API.

> [!NOTE]
> This addon does not require the Google My Business API, which makes it easier to set up and use.

✅ Simple configuration<br>
✅ Multi location support<br>
✅ Reviews are cached in a collection<br>
✅ Periodic synchronization<br>
✅ Manual review editing and removal<br>

## 🛠️ How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or
run the following command from your project root:

``` bash
composer require emplify-software/statamic-google-reviews
```

After the addon is installed, publish the configuration file by running:

``` bash
php artisan vendor:publish --tag=statamic-google-reviews
```


## 💡 How to Use

### Generating an API Key for Google Places API

1. Use an existing Google Cloud project or create a new one at [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new API key for the project:
   * Go to the [Google Maps Platform > Credentials](https://console.cloud.google.com/project/_/google/maps-apis/credentials) section and select the project.
   * Click **Create credentials** and select **API key**.
   * Under **API restrictions**, select **Places API** or **Places API (New)**.
   * The API key will be displayed on the screen.

### Configuration

1. Add your Google Places API key to the `.env` file:

    ```dotenv
    GOOGLE_REVIEWS_API_KEY=your-api-key
    ```
    
2. Reviews can be retrieved with translated texts. Per default, the current app locale is used as the language for the reviews. If you want to use a different language, set `GOOGLE_REVIEWS_LANGUAGE` in the `.env` file:
    
   ```dotenv
   GOOGLE_REVIEWS_LANGUAGE=de
   ```

3. If you are using the legacy "Places API" (not "Places API (New)"), set `GOOGLE_REVIEWS_LEGACY_API` to `true` in the `.env` file:

    ```dotenv
    GOOGLE_REVIEWS_LEGACY_API=true
    ```
   
### Adding Places

The addon automatically generates a taxonomy called `Google Review Places`.
To register a new place, follow these steps:

1. Go to `Taxonomies > Google Review Places` in the control panel 
2. Click **Create Term**
3. Enter a name for the place. You can name it whatever you want, e.g. "My Restaurant" or "Berlin Office".
4. Enter the Google Place ID for the location. You can find the Place ID by searching for the location on [Google Maps](https://www.google.com/maps).
5. Click **Save & Publish**

### Fetching Review

Per default, reviews for all registered places are fetched every hour.
You can also manually fetch the reviews by running the following command:

``` bash
php artisan google-reviews:crawl
```


### Removing and editing reviews

The reviews are stored in a collection called `Google Reviews` (`Collections > Google Reviews`).
To remove a review, simply unpublish it.

> [!NOTE]
> Do not completely delete reviews from the collection. Deleted reviews will be re-fetched on the next crawl. 

It is also possible to edit the review content in the entry (e.g. to fix spelling mistakes or adjust the translation).
To make sure the review is not overwritten on the next crawl, activate the `Manual Override` switch for the entry.


### Displaying Reviews

You can display the reviews on your site by iterating over the `Google Reviews` collection using the `collection:google-reviews` tag:

```antlers
{{ collection:google-reviews }}
    <div>
        <div>
            Author: {{ author_name }}
        </div>
        <img src="{{ profile_photo_url }}">
        <div>
            Rating: {{ rating }}
        </div>
        <div>
            Time: {{ time }}
        </div>
        <div>
            Text: {{ text }}
        </div>
        <div>
            Place: {{ place['title'] }}
        </div>
    </div>
{{ /collection:google-reviews }}
```

Since the reviews are stored in a collection, you can also use the collection tag to filter and sort the reviews as needed (See https://statamic.dev/tags/collection).

*Example: Only display the reviews for a specific place:*

```antlers
{{ collection:google-reviews place:is="location-a" }}
    ...
{{ /collection:google-reviews }}
```

## ⚠️ Known Issues

* Due to a limitation in the Google Places API, only the last 5 reviews can be fetched. This is not a problem for new reviews, because all reviews are fetched and stored in the collection.
  However, if you already have more than 5 reviews on a place and you add it to the addon, only the last 5 reviews will be fetched.
  If you also want to display older reviews, you can manually add them to the `Google Reviews` collection.
* When using the "Places API (New)", it can also no longer be guaranteed that all new reviews will be fetched. 
  This is due to the fact that the new Places API does not return the latest reviews, but only a selection of reviews that are
  considered relevant by Google.
