<?php

namespace App\Service;

final class Geocoder
{
    /** @return array{latitude: float, longitude: float, label: string}|null */
    public function locate(string $address, string $town): ?array
    {
        $query = trim($address.', '.$town);
        if (mb_strlen($query) < 4 || mb_strlen($query) > 300) {
            return null;
        }

        $url = 'https://nominatim.openstreetmap.org/search?'.http_build_query([
            'q' => $query,
            'format' => 'jsonv2',
            'limit' => 1,
            'addressdetails' => 0,
            'accept-language' => 'fr',
        ]);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: GenealogieVille/1.0 (https://geneaolgie-ville.vercel.app)',
            ],
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (!is_string($body) || $status !== 200) {
            return null;
        }

        $results = json_decode($body, true);
        $result = is_array($results) ? ($results[0] ?? null) : null;
        if (!is_array($result) || !is_numeric($result['lat'] ?? null) || !is_numeric($result['lon'] ?? null)) {
            return null;
        }

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'label' => mb_substr((string) ($result['display_name'] ?? $query), 0, 255),
        ];
    }
}
