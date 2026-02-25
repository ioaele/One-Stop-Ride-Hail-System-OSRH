<?php
session_start();
require_once 'connect.php';

// Prepare session data to be passed to JavaScript
$sessionData = array(
  'service' => $_SESSION['selected_service'] ?? null,
  'serviceType' => $_SESSION['selected_service_type'] ?? null,
  'pickupLat' => $_SESSION['pickup_lat'] ?? null,
  'pickupLng' => $_SESSION['pickup_lng'] ?? null,
  'dropoffLat' => $_SESSION['dropoff_lat'] ?? null,
  'dropoffLng' => $_SESSION['dropoff_lng'] ?? null
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Select Vehicle Type | OSRH</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ride_map_options.css">
  <!-- maps not required on this page (leaflet removed) -->
</head>

<body>

<header class="navbar">
  <a href="homepage_pas.html" class="OSRH">One-Stop Ride-Hail</a>
  <div class="profile-text">
    <span class="profile-label">Welcome</span>
    <span class="profile-username" id="navbar-username"></span>
  </div>
</header>

<main class="main">
  <section class="hero-card">
    <h1 class="hero-title">Choose a Vehicle Type</h1>

    <div class="summary-box" id="summary">
      <strong>Service:</strong> <span id="service-name">Loading...</span><br>
      <strong>Ride Type:</strong> <span id="ride-type">Loading...</span><br><br>

      <strong>📍 Pickup:</strong> <span id="pickup-address" class="address-loading">Loading address...</span><br>
      <strong>📍 Dropoff:</strong> <span id="dropoff-address" class="address-loading">Loading address...</span>
    </div>

    <h2 class="section-title">Available Vehicle Types</h2>

    <div id="vehicle-list">
      Loading vehicle types...
    </div>

  </section>
</main>

<script>
  // Pass session data from PHP to JavaScript
  const sessionData = <?php echo json_encode($sessionData); ?>;
  const service = sessionData.service;
  const serviceType = sessionData.serviceType;
  const serviceTypeName = sessionData.serviceTypeName;
  const pickupLat = sessionData.pickupLat;
  const pickupLng = sessionData.pickupLng;
  const dropoffLat = sessionData.dropoffLat;
  const dropoffLng = sessionData.dropoffLng;
</script>

<!-- Page scripts -->
<script src="ride_map_options.js"></script>

<script>
  // Initialize page with session data and load content
  initializePageData(sessionData);
  loadAddresses(pickupLat, pickupLng, dropoffLat, dropoffLng);
  loadVehicleTypes(serviceType, pickupLat, pickupLng);
</script>

</body>
</html>

</body>
</html>
