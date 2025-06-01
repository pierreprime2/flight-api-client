<?php

namespace App\Service;

use SerpApi\GoogleSearch;

class SerpApiClient
{
    private string $apiKey;

    public function __construct(string $serpApiKey)
    {
        $this->apiKey = $serpApiKey;
    }

    public function getDeparturesFromAirport(string $origin, int $limit = 20): array
    {
        $params = [
            'engine' => 'google_flights',
            'departure' => $origin,
            'api_key' => $this->apiKey
        ];

        $search = new GoogleSearch($params);
        $results = $search->get_json();

        return array_slice($results['flight_results'] ?? [], 0, $limit);
    }

    public function getFlightDetails(string $flightId): ?array
    {
        $params = [
            'engine' => 'google_flights',
            'q' => $flightId,
            'api_key' => $this->apiKey
        ];

        $search = new GoogleSearch($params);
        $results = $search->get_json();

        // simulate single flight retrieval
        foreach($results['flights_results'] ?? [] as $flight) {
            if(strpos($flight['flight_number'] ?? '', $flightId) !== false) {
                return $flight;
            }
        }

        return null;
    }

    /**
     * Next flights arriving at given destination
     */
    public function getArrivals(string $destination, int $limit = 5): array
    {
        $params = [
            'engine' => 'google_flights',
            'arrival_id' => $destination,
            'api_key' => $this->apiKey
        ];

        $search = new GoogleSearch($params);
        $results = $search->get_json();

        return array_slice($results['flights_results'] ?? [], 0, $limit);
    }
}
