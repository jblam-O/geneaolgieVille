<?php

namespace App\Service;

final class Geocoder
{
    /** @return array{latitude: float, longitude: float, label: string, approximate: bool}|null */
    public function locate(string $address, string $town): ?array
    {
        $address = trim($address);
        $town = trim($town);
        if ($town === '' || mb_strlen($address.', '.$town) > 300) {
            return null;
        }

        $normalizedAddress = mb_strtolower($address);
        $normalizedTown = mb_strtolower($town);
        $preciseQuery = str_contains($normalizedAddress, $normalizedTown)
            ? $address.', France'
            : $address.', '.$town.', France';
        $result = $this->search($preciseQuery);
        if ($result) {
            return [...$result, 'approximate' => false];
        }

        // Nominatim limits public clients to one request per second.
        // A vague or misspelled address falls back to the town centre.
        usleep(1_050_000);
        $result = $this->search($town.', France');
        if (!$result) {
            return null;
        }

        return [
            ...$result,
            'label' => 'Position approximative — centre de '.$town.'. Déplacez le marqueur pour préciser.',
            'approximate' => true,
        ];
    }

    /** @return array{latitude: float, longitude: float, label: string}|null */
    private function search(string $query): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search?'.http_build_query([
            'q' => $query,
            'format' => 'jsonv2',
            'limit' => 1,
            'addressdetails' => 0,
            'accept-language' => 'fr',
            'countrycodes' => 'fr',
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
