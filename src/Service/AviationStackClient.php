<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AviationStackClient
{
    private HttpClientInterface $httpClient;
    private string $accessKey;
    private string $baseUrl;

    public function __construct(HttpClientInterface $httpClient, string $aviationStackKey, string $aviationUrl)
    {
        $this->httpClient = $httpClient;
        $this->accessKey = $aviationStackKey;
        $this->baseUrl = rtrim($aviationUrl, '/');
    }

    public function getDeparturesFromAirport(string $origin, int $limit = 20): array
    {
        $query = [
            'access_key' => $this->accessKey,
            'dep_iata' => $origin,
            'limit' => $limit,
            'flight_status' => 'scheduled'
        ];

        $response = $this->httpClient->request('GET', $this->baseUrl . '/flights', [
            'query' => $query
        ]);

        $data = $response->toArray();
        return $data['data'] ?? [];
    }

    public function getFlightDetails(string $flightIata): ?array
    {
        $query = [
            'access_key' => $this->accessKey,
            'flight_iata' => $flightIata,
            'limit' => 1,
        ];

        $response = $this->httpClient->request('GET', $this->baseUrl . '/flights', [
            'query' => $query
        ]);

        $data = $response->toArray();

        return $data['data'][0] ?? null;
    }

    /**
     * Next flights arriving at given destination
     */
    public function getArrivals(string $destination, int $limit = 5): array
    {
        $query = [
            'access_key' => $this->accessKey,
            'arr_iata' => $destination,
            'status' => 'scheduled',
            'limit' => $limit
        ];

        $response = $this->httpClient->request('GET', $this->baseUrl . '/flights', [
            'query' => $query
        ]);

        $data = $response->toArray();
        return $data['data'] ?? [];
    }
}
