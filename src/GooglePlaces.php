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
    private $placeDetails;

    public function __construct()
    {
        $this->apiKey = $this->getApiKey();
        $this->placeId = $this->getPlaceId();

        // Check if the transients exist
        $reviews_transient = get_transient('wo_reviews');
        $aggregated_rating_transient = get_transient('wo_aggregated_rating');

        // If the transients do not exist, get the place details
        if (!$reviews_transient || !$aggregated_rating_transient) {
            $this->placeDetails = $this->getPlaceDetails();
        }

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
            wp_schedule_event(time(), 'twicedaily', 'wo_fetch_google_places_data');
        }

        add_action('wo_fetch_google_places_data', [$this, 'fetchData']);
    }

    public function getPlaceDetails()
    {
        if (!$this->apiKey || !$this->placeId) {
            // Throw an exception if the api key or place id is not set
            throw new Exception('No valid API key or place id found');

            // Is it wise to delete the cron job here?
            wp_clear_scheduled_hook('wo_fetch_google_places_data');
        }

        $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=$this->placeId&key=$this->apiKey&language=nl";

        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);

            curl_close($curl);

            if ($response) {
                $data = json_decode($response, true);

                if (!isset($data['result'])) {
                    throw new Exception('No valid place details found');
                }

                return $data;
            }
        } catch (Exception $e) {
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

        if (!isset($this->placeDetails['result']['reviews'])) {
            return;
        }

        $reviews_transient = $this->placeDetails['result']['reviews'];
        set_transient('wo_reviews', $reviews_transient, 60 * 60 * 24);
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

        if (!isset($this->placeDetails['result']['rating']) || !isset($this->placeDetails['result']['user_ratings_total'])) {
            return;
        }

        $aggregated_rating_transient = [];
        if (isset($this->placeDetails['result']['rating'])) {
            $aggregated_rating_transient['rating'] = $this->placeDetails['result']['rating'];
        }

        if (isset($this->placeDetails['result']['user_ratings_total'])) {
            $aggregated_rating_transient['user_ratings_total'] = $this->placeDetails['result']['user_ratings_total'];
        }

        set_transient('wo_aggregated_rating', $aggregated_rating_transient, 60 * 60 * 24);
        return $aggregated_rating_transient;
    }

    /** 
     * Fetch the data from the Google Places API through a cron job
     */
    public function fetchData()
    {
        // Delete the transients
        delete_transient('wo_reviews');
        delete_transient('wo_aggregated_rating');

        // Fetch the data
        try {
            $this->getReviews();
            $this->getAggregatedRating();
        } catch (Exception $e) {
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
