<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing coordinates'
    ]);
    exit;
}

$lat = $_GET['lat'];
$lng = $_GET['lng'];

// Using Nominatim API for reverse geocoding
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'OSRH-RideHail-App/1.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode([
        'success' => false,
        'error' => 'Geocoding service unavailable',
        'address' => "$lat, $lng"
    ]);
    exit;
}

$data = json_decode($response, true);

if (!$data || !isset($data['address'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response',
        'address' => "$lat, $lng"
    ]);
    exit;
}

// Build readable address
$address = $data['address'];
$formattedAddress = '';

// Street address
if (!empty($address['road']) || !empty($address['street'])) {
    $street = !empty($address['road']) ? $address['road'] : $address['street'];
    if (!empty($address['house_number'])) {
        $formattedAddress = $address['house_number'] . ' ' . $street;
    } else {
        $formattedAddress = $street;
    }
} elseif (!empty($address['neighbourhood'])) {
    $formattedAddress = $address['neighbourhood'];
} elseif (!empty($address['suburb'])) {
    $formattedAddress = $address['suburb'];
}

// City
if (!empty($address['city']) || !empty($address['town']) || !empty($address['village'])) {
    if ($formattedAddress) $formattedAddress .= ', ';
    $formattedAddress .= !empty($address['city']) ? $address['city'] : 
                         (!empty($address['town']) ? $address['town'] : $address['village']);
}

// Country
if (!empty($address['country'])) {
    if ($formattedAddress) $formattedAddress .= ', ';
    $formattedAddress .= $address['country'];
}

// Fallback
if (empty($formattedAddress) && !empty($data['display_name'])) {
    $formattedAddress = $data['display_name'];
}

if (empty($formattedAddress)) {
    $formattedAddress = "$lat, $lng";
}

echo json_encode([
    'success' => true,
    'address' => $formattedAddress,
    'full_data' => $data
]);
?>