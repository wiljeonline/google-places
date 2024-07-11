# Wilje Online Google Places 
Just a simple PHP class we use in our projects for the Google Places API. 

License: Apache 2.0

# Installation 
## 1. Set up the Google API 
**Set up the Google Places API** 
We need to enable the **Places API**. 
Setup: https://developers.google.com/maps/documentation/places/web-service/cloud-setup

**Get Google Place ID** 
Get the Google Place ID for the place ID you want to use. 

The easiest option is to use this website: 
https://developers.google.com/maps/documentation/places/web-service/place-id.

## 2. Set the API keys in your project 
When you have your API and keys setup, we use roughly one of two types of settings.

### Option 1. Set API keys via PHP Constants (safest)*

Follow these steps: 
- Set a constant named WO_GOOGLE_PLACE_ID in wp-config.php and fill in your Place ID
- Set a constant named WO_GOOGLE_PLACES_API_KEY in wp-config.php and fill in your API key

### Option 2. Set API keys via ACF Settings fields (safe enough)

Follow these steps: 
- Install and active Advanced Custom Fields Pro 
- Set up two ACF settings fields named wo_google_place_id & wo_google_places_api_key
- Set them as options in an ACF options page
- Fill in the Google Place ID and API key in the options pages and save the settings 

## Example usage of the class
You can now use the class as follows: 

### Get all the place details
```php
use WiljeOnline\GooglePlaces;

$googlePlaces = new GooglePlaces;
$placeDetails = $googlePlaces->getPlaceDetails(); 
```

### Get the reviews
```php
use WiljeOnline\GooglePlaces;

$googlePlaces = new GooglePlaces;
$reviews = $googlePlaces->getReviews();
```

### Get the aggregated rating 
```php
use WiljeOnline\GooglePlaces;

$googlePlaces = new GooglePlaces;
$aggregatedRating = $googlePlaces->getAggregatedRating();
```
## Usage in your theme 
You can now get the reviews and aggregated ratings through the WordPress options. 
The settings are saved as options and will be deleted when cron runs (once daily). The class is set up with a cron job that runs twice daily to fetch the new data.

**Get the options** 
```php 
$reviews = get_option('wo_reviews');

if( $reviews ) { 
    // Do something with the reviews.
}
```

## Authors
- [@jeroen-mulder](https://www.github.com/jeroen-mulder)

## License
[Apache 2.0](https://choosealicense.com/licenses/apache-2.0)
