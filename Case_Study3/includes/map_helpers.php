<?php
/**
 * Hỗ trợ parse tọa độ và geocode địa chỉ (OpenStreetMap Nominatim).
 */

function parse_motel_coordinates(?string $latlng): ?array
{
    if ($latlng === null || trim($latlng) === '') {
        return null;
    }

    $latlng = trim($latlng);

    if (preg_match('/^([-+]?\d+(?:\.\d+)?)\s*[,;]\s*([-+]?\d+(?:\.\d+)?)$/', $latlng, $m)) {
        $lat = (float) $m[1];
        $lng = (float) $m[2];
    } else {
        $parts = preg_split('/\s+/', $latlng);
        if (count($parts) < 2) {
            return null;
        }
        $lat = (float) $parts[0];
        $lng = (float) $parts[1];
    }

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }

    return ['lat' => $lat, 'lng' => $lng];
}

function geocode_motel_address(string $address): ?array
{
    $address = trim($address);
    if ($address === '') {
        return null;
    }

    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'format' => 'json',
        'limit' => 1,
        'q' => $address,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: GTPT-CaseStudy3/1.0 (motel finder)\r\nAccept: application/json\r\n",
            'timeout' => 8,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || count($data) === 0) {
        return null;
    }

    $lat = (float) ($data[0]['lat'] ?? 0);
    $lng = (float) ($data[0]['lon'] ?? 0);

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }

    return ['lat' => $lat, 'lng' => $lng];
}

function resolve_motel_map_position(array $motel): ?array
{
    $coords = parse_motel_coordinates($motel['latlng'] ?? null);
    if ($coords !== null) {
        $coords['source'] = 'gps';
        return $coords;
    }

    if (!empty($motel['address'])) {
        $geocoded = geocode_motel_address($motel['address']);
        if ($geocoded !== null) {
            $geocoded['source'] = 'address';
            return $geocoded;
        }
    }

    return null;
}
