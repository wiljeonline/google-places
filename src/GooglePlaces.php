<?php

/**
 * Class to get the reviews and aggregated rating from the Google Places API
 * 
 */

namespace WiljeOnline;

class GooglePlaces
{

    private $apiKey;
    private $placeId;

    public function __construct()
    {
        $this->apiKey = $this->getApiKey();
        $this->placeId = $this->getPlaceId();

        // Only regsiter if not exists
        add_action('wp_loaded', [$this, 'register_cron_job']);
    }

    /**
     * Register the cron job
     *
     * @return void
     */
    public function register_cron_job()
    {
        if (!wp_next_scheduled('wo_fetch_google_places_data')) {
            wp_schedule_event(time(), 'daily', 'wo_fetch_google_places_data');
        }

        add_action('wo_fetch_google_places_data', [$this, 'fetchData']);
    }

    public function getPlaceDetails()
    {
        if (!$this->apiKey || !$this->placeId) {
            // Throw an exception if the api key or place id is not set
            throw new \Exception('No valid API key or place id found');

            // Is it wise to delete the cron job here?
            wp_clear_scheduled_hook('wo_fetch_google_places_data');
        }

        $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=$this->placeId&key=$this->apiKey&language=nl";

        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            
            $response = curl_exec($curl);
            error_log( print_r( 'Google API is running.', true ));

            curl_close($curl);

            if ($response) {
                set_transient('wo_google_place_details', $response, DAY_IN_SECONDS); // Cache for a day

                $data = json_decode($response, true);

                if (!isset($data['result'])) {
                    throw new \Exception('No valid place details found');
                }

                return $data;
            }
        } catch (\Exception $e) {
            // Handle the exception
            error_log(print_r($e->getMessage(), true));
        }
    }

    /**
     * Get the reviews from the transient
     * If the transient is not set, get the reviews from the Google Places API
     *
     * @param  array   $data
     * @return array
     */
    public function getReviews()
    {
        $reviews_transient = get_transient('wo_reviews');

        if ($reviews_transient) {
            return $reviews_transient;
        }

        if (!get_transient('wo_google_place_details')) {
            $this->getPlaceDetails();
        }

        $placeDetails = get_transient('wo_google_place_details');
        $placeDetails = json_decode($placeDetails, true);

        $reviews_transient = $placeDetails['result']['reviews'];
        set_transient('wo_reviews', $reviews_transient, DAY_IN_SECONDS); // Cache for a day
        return $reviews_transient;
    }

    /**
     * Get the aggregated rating from the transient
     * If the transient is not set, get the aggregated rating from the Google Places API
     *
     * @param  array    $data
     * @return array
     */
    public function getAggregatedRating()
    {
        $aggregated_rating_transient = get_transient('wo_aggregated_rating');

        if ($aggregated_rating_transient) {
            return $aggregated_rating_transient;
        }

        if (!get_transient('wo_google_place_details')) {
            $this->getPlaceDetails();
        }

        $placeDetails = get_transient('wo_google_place_details');
        $placeDetails = json_decode($placeDetails, true);

        $aggregated_rating_transient = [];
        if (isset($placeDetails['result']['rating'])) {
            $aggregated_rating_transient['rating'] = $placeDetails['result']['rating'];
        }

        if (isset($placeDetails['result']['user_ratings_total'])) {
            $aggregated_rating_transient['user_ratings_total'] = $placeDetails['result']['user_ratings_total'];
        }

        set_transient('wo_aggregated_rating', $aggregated_rating_transient, DAY_IN_SECONDS);
        return $aggregated_rating_transient;
    }

    /** 
     * Fetch the data from the Google Places API through a cron job
     */
    public function fetchData()
    {
        // Delete the transients
        if (get_transient('wo_reviews') || get_transient('wo_aggregated_rating')) {
            return;
        }

        // Fetch the data
        try {
            $this->getReviews();
            $this->getAggregatedRating();
        } catch (\Exception $e) {
            error_log('Error fetching data: ' . $e->getMessage());
        }
    }

    /**
     * Get the place id from the options page
     *
     * @return  string
     */
    private function getPlaceId()
    {
        if (!function_exists('get_field')) {
            return;
        }

        if (defined('WO_GOOGLE_PLACE_ID')) {
            return WO_GOOGLE_PLACE_ID;
        }

        if (!get_field('wo_google_place_id', 'option')) {
            return false;
        }

        // Get the place id from the options page
        return get_field('wo_google_place_id', 'option');
    }

    /**
     *  Get the api key from the options page
     *
     * @return  string  
     */
    private function getApiKey()
    {
        if (!function_exists('get_field')) {
            return;
        }

        if (defined('WO_GOOGLE_PLACES_API_KEY')) {
            return WO_GOOGLE_PLACES_API_KEY;
        }

        if (!get_field('wo_google_places_api_key', 'option')) {
            return false;
        }

        // Get the api key from the options page
        return get_field('wo_google_places_api_key', 'option');
    }
}
