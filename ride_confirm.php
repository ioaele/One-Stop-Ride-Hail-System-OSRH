<?php
session_start();
require_once 'connect.php';

$service              = $_SESSION['selected_service'] ?? null;
$serviceTypeId        = $_SESSION['selected_service_type'] ?? null;
$vehicleTypeId        = $_SESSION['selected_vehicle_type_id'] ?? null;
$pickupLat            = $_SESSION['pickup_lat'] ?? null;
$pickupLng            = $_SESSION['pickup_lng'] ?? null;
$dropoffLat           = $_SESSION['dropoff_lat'] ?? null;
$dropoffLng           = $_SESSION['dropoff_lng'] ?? null;
$usersId              = $_SESSION['users_id'] ?? null;

// Map service name to actual service_id (Service table has IDs 6-10)
$serviceIdMap = [
  'driver' => 6,
  'teledrive' => 7,
  'autonomous' => 8,
  'mini_van' => 9
];
$serviceId = isset($serviceIdMap[$service]) ? $serviceIdMap[$service] : null;

$serviceTypeName = null;
$vehicleTypeName = null;
$hasPendingRequest = false;
$rideRequestStartTime = null;

try {
  $db = new Database();
  $conn = $db->getConnection();
  
  if ($conn) {
    // Look up service type name using SP
    if ($serviceTypeId) {
      $stmt = $conn->prepare('{CALL GetServiceTypeName(?, ?)}');
      $stmt->bindParam(1, $serviceTypeId, PDO::PARAM_INT);
      $stmt->bindParam(2, $serviceTypeName, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 100);
      $stmt->execute();
      $stmt->closeCursor();
    }
    
    // Look up vehicle type name using SP
    if ($vehicleTypeId) {
      $stmt = $conn->prepare('{CALL GetVehicleTypeName(?)}');
      $stmt->bindParam(1, $vehicleTypeId, PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row && isset($row['vehicle_type'])) {
        $vehicleTypeName = $row['vehicle_type'];
      }
      $stmt->closeCursor();
    }
    
    // Check for pending request
    if ($usersId) {
      $stmt = $conn->prepare('{CALL CheckUserPendingRequest(?, ?, ?)}');
      $hasPending = 0;
      $rideRequestId = null;
      $stmt->bindParam(1, $usersId, PDO::PARAM_INT);
      $stmt->bindParam(2, $hasPending, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 4);
      $stmt->bindParam(3, $rideRequestId, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 4);
      $stmt->execute();
      $stmt->closeCursor();
      
      if ($hasPending) {
        $hasPendingRequest = true;
        // Get elapsed seconds
        $stmt = $conn->prepare('{CALL GetRideRequestElapsedTime(?, ?)}');
        $stmt->bindParam(1, $usersId, PDO::PARAM_INT);
        $stmt->bindParam(2, $rideRequestStartTime, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 4);
        $stmt->execute();
        $stmt->closeCursor();
      }
    }
  }
} catch (Exception $e) {
  error_log('ride_confirm.php error: ' . $e->getMessage());
}

// Build data for JS
$sessionData = [
  'service' => $service,
  'serviceId' => $serviceId,
  'serviceTypeId' => $serviceTypeId,
  'serviceTypeName' => $serviceTypeName,
  'vehicleTypeId' => $vehicleTypeId,
  'vehicleTypeName' => $vehicleTypeName,
  'pickupLat' => $pickupLat,
  'pickupLng' => $pickupLng,
  'dropoffLat' => $dropoffLat,
  'dropoffLng' => $dropoffLng,
  'usersId' => $usersId,
  'hasPendingRequest' => $hasPendingRequest,
  'rideRequestStartTime' => $rideRequestStartTime
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Confirm Ride | OSRH</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="style.css">
  <style>
    .confirmation-card {background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px;}
    .info-row {display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee;}
    .info-label {font-weight:600;color:#666;}
    .info-value {color:#333;}
    .price-box {background:#f0f8ff;padding:20px;border-radius:10px;text-align:center;margin:20px 0;}
    .price-amount {font-size:2rem;font-weight:bold;color:#0066ff;}
    .confirm-btn {width:100%;background:#0066ff;color:#fff;border:none;padding:15px;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:.2s;}
    .confirm-btn:hover {background:#004bcc;}
    .confirm-btn:disabled {background:#ccc;cursor:not-allowed;}
    .back-btn {width:100%;background:#f5f5f5;color:#333;border:none;padding:12px;border-radius:8px;font-size:14px;cursor:pointer;margin-top:10px;}
    .error-message {background:#fee;color:#c33;padding:15px;border-radius:8px;margin-bottom:20px;}
    .success-message {background:#efe;color:#3c3;padding:15px;border-radius:8px;margin-bottom:20px;}
  </style>
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
    <h1 class="hero-title">Confirm Your Ride</h1>
    <div id="message-area"></div>
    <div class="confirmation-card">
      <h2>Ride Details</h2>
      <div class="info-row"><span class="info-label">Service:</span><span class="info-value" id="service-name">-</span></div>
      <div class="info-row"><span class="info-label">Ride Type:</span><span class="info-value" id="service-type">-</span></div>
      <div class="info-row"><span class="info-label">Vehicle Type:</span><span class="info-value" id="vehicle-type">-</span></div>
      <div class="info-row"><span class="info-label">Pickup:</span><span class="info-value" id="pickup-address">Loading...</span></div>
      <div class="info-row"><span class="info-label">Dropoff:</span><span class="info-value" id="dropoff-address">Loading...</span></div>
      <div class="info-row"><span class="info-label">Distance:</span><span class="info-value" id="distance">-</span></div>
      <div class="info-row"><span class="info-label">ETA:</span><span class="info-value" id="eta">-</span></div>
    </div>
    <div class="price-box"><div style="font-size:14px;color:#666;margin-bottom:5px;">Estimated Price</div><div class="price-amount" id="price">€0.00</div></div>
    <button class="confirm-btn" id="confirm-btn" onclick="confirmRide()">Confirm and Request Ride</button>
    <button class="back-btn" onclick="goBack()">Go Back</button>
  </section>
</main>

<script>
  // Inject PHP session data
  const sessionData = <?php echo json_encode($sessionData); ?>;

  const service         = sessionData.service;
  const serviceId       = sessionData.serviceId || null;
  const serviceTypeId   = sessionData.serviceTypeId ? parseInt(sessionData.serviceTypeId) : null;
  const serviceTypeName = sessionData.serviceTypeName || '-';
  const vehicleTypeId   = sessionData.vehicleTypeId ? parseInt(sessionData.vehicleTypeId) : null;
  const vehicleTypeName = sessionData.vehicleTypeName || '-';
  const pickupLat       = sessionData.pickupLat ? parseFloat(sessionData.pickupLat) : null;
  const pickupLng       = sessionData.pickupLng ? parseFloat(sessionData.pickupLng) : null;
  const dropoffLat      = sessionData.dropoffLat ? parseFloat(sessionData.dropoffLat) : null;
  const dropoffLng      = sessionData.dropoffLng ? parseFloat(sessionData.dropoffLng) : null;
  const hasPendingRequest = sessionData.hasPendingRequest || false;

  // Initialize polling variables globally
  let pollingInterval = null;
  let pollingAttempts = 0;
  const maxPollingAttempts = 120; // 120 attempts × 1 second = 2 minutes

  // Check if user already has pending request and show popup
  if (hasPendingRequest) {
    showExistingRequestPopup();
  }

  // Display base info
  document.getElementById('service-name').textContent = service || 'N/A';
  document.getElementById('service-type').textContent = serviceTypeName;
  document.getElementById('vehicle-type').textContent = vehicleTypeName;

  function calculateDistance(lat1, lng1, lat2, lng2) {
    if (lat1==null||lng1==null||lat2==null||lng2==null) return 0;
    const R = 6371;
    const dLat = (lat2-lat1) * Math.PI/180;
    const dLng = (lng2-lng1) * Math.PI/180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }

  const distance = calculateDistance(pickupLat, pickupLng, dropoffLat, dropoffLng);
  const estimatedPrice = distance ? (3 + distance * 1.5).toFixed(2) : '0.00';
  const eta = distance ? Math.max(5, Math.floor((distance/40)*60)) : 0;
  document.getElementById('distance').textContent = distance ? distance.toFixed(2)+' km' : 'N/A';
  document.getElementById('eta').textContent = eta ? eta + ' min' : 'N/A';
  document.getElementById('price').textContent = '€' + estimatedPrice;

  // Show vehicle type if known
  if (vehicleTypeId && vehicleTypeName !== '-') {
    document.getElementById('vehicle-type').textContent = vehicleTypeName;
  } else if (!vehicleTypeId) {
    document.getElementById('vehicle-type').textContent = 'Not selected';
  }

  async function getAddress(lat,lng){
    if(lat==null||lng==null) return 'Not set';
    try { const r = await fetch(`reverse_geocode.php?lat=${lat}&lng=${lng}`); const d = await r.json(); return d.success? d.address : `${lat.toFixed(4)}°, ${lng.toFixed(4)}°`; } catch(e){ return `${lat.toFixed(4)}°, ${lng.toFixed(4)}°`; }
  }

  async function loadAddresses(){
    document.getElementById('pickup-address').textContent = await getAddress(pickupLat, pickupLng);
    document.getElementById('dropoff-address').textContent = await getAddress(dropoffLat, dropoffLng);
  }

  async function confirmRide(){
    const btn = document.getElementById('confirm-btn');
    const msg = document.getElementById('message-area');
    let missing = [];
    if(!serviceId) missing.push('service');
    if(!serviceTypeId) missing.push('service_type');
    if(!vehicleTypeId) missing.push('vehicle_type');
    if(pickupLat==null || pickupLng==null) missing.push('pickup');
    if(dropoffLat==null || dropoffLng==null) missing.push('dropoff');
    const usersId = sessionData.usersId;
    console.log('Current users_id from session:', usersId);
    if(!usersId) missing.push('users_id');
    if(missing.length){
      msg.innerHTML = `<div class="error-message">Missing: ${missing.join(', ')}. Please go back.</div>`;
      return;
    }
    
    // Check if user already has a pending request before proceeding
    btn.disabled = true; btn.textContent='Checking for existing request...'; msg.innerHTML='';
    try {
      console.log('Fetching check_driver_status.php for users_id:', usersId);
      const checkRes = await fetch(`check_driver_status.php?users_id=${usersId}`);
      console.log('checkRes.ok:', checkRes.ok, 'checkRes.status:', checkRes.status);
      const checkData = await checkRes.json();
      console.log('Check existing request response:', checkData);
      
      // Show popup only if there's actually a pending or accepted request (not 'none')
      if (checkData.status === 'pending') {
        console.log('User has pending request - showing popup');
        btn.disabled = false; btn.textContent='Confirm and Request Ride';
        showExistingRequestPopup();
        return;
      } else if (checkData.status === 'accepted') {
        console.log('User has accepted request - showing popup');
        btn.disabled = false; btn.textContent='Confirm and Request Ride';
        showExistingRequestPopup();
        return;
      } else {
        console.log('No pending request, continuing with creation. Status was:', checkData.status);
      }
    } catch (err) {
      console.error('Error checking existing request:', err);
      btn.disabled = false; btn.textContent='Confirm and Request Ride';
      // Continue with request creation if check fails
    }
    btn.textContent='Creating ride request...';
    try {
      btn.textContent='Creating pickup point...';
      const pickupRes = await fetch('create_point.php',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({latitude:pickupLat, longitude:pickupLng, start:0})});
      const pickupData = await pickupRes.json();
      if(pickupData.status!=='success') throw new Error('Pickup point failed: '+pickupData.message);
      const pickupPointId = pickupData.point_id;

      btn.textContent='Creating dropoff point...';
      const dropRes = await fetch('create_point.php',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({latitude:dropoffLat, longitude:dropoffLng, start:1})});
      const dropData = await dropRes.json();
      if(dropData.status!=='success') throw new Error('Dropoff point failed: '+dropData.message);
      const dropoffPointId = dropData.point_id;

      btn.textContent='Requesting ride...';
      console.log('Sending to create_ride_request:', {
        users_id: usersId,
        service_id: serviceId,
        service_type_id: serviceTypeId,
        vehicle_type_id: vehicleTypeId,
        pickup_point_id: pickupPointId,
        dropoff_point_id: dropoffPointId,
        estimated_price: parseFloat(estimatedPrice)
      });
      const rideRes = await fetch('create_ride_request.php',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({
        users_id: usersId,
        service_id: serviceId,
        service_type_id: serviceTypeId,
        vehicle_type_id: vehicleTypeId,
        pickup_point_id: pickupPointId,
        dropoff_point_id: dropoffPointId,
        estimated_price: parseFloat(estimatedPrice)
      })});
      
      console.log('Response status:', rideRes.status, 'OK:', rideRes.ok);
      
      if (!rideRes.ok) {
        throw new Error(`Server error: ${rideRes.status} ${rideRes.statusText}`);
      }
      
      const responseText = await rideRes.text();
      console.log('Raw response:', responseText);
      
      let rideData;
      try {
        rideData = JSON.parse(responseText);
      } catch (parseErr) {
        console.error('JSON parse error:', parseErr);
        throw new Error('Invalid response from server: ' + responseText.substring(0, 100));
      }
      
      console.log('Parsed response:', rideData);
      
      // If user already has a pending request, show waiting screen
      if(rideData.status==='error' && rideData.message && rideData.message.includes('already have a pending')) {
        console.log('User has pending request, showing existing request popup');
        showExistingRequestPopup();
        return;
      }
      
      if(rideData.status==='success') {
        console.log('Success! Calling showWaitingScreen()');
        // Show waiting screen
        showWaitingScreen();
        console.log('showWaitingScreen() completed');
      } else {
        const debugInfo = rideData.debug ? `\n\nDebug: ${JSON.stringify(rideData.debug)}` : '';
        throw new Error(rideData.message + debugInfo);
      }
    } catch(err){
      console.error(err);
      msg.innerHTML = `<div class='error-message'><strong>Error:</strong> ${err.message || 'Network error'}</div>`;
      btn.disabled=false; btn.textContent='Confirm and Request Ride';
    }
  }

  function showWaitingScreen() {
    console.log('showWaitingScreen() called');
    const container = document.querySelector('main.main');
    console.log('Container found:', container);
    if (!container) {
      console.error('Main container element not found');
      alert('Error: Main container element not found. Cannot show waiting screen.');
      return;
    }
    console.log('Setting container innerHTML...');
    container.innerHTML = `
      <div style="text-align:center; padding:40px;">
        <h2>🚕 Waiting for Driver</h2>
        <p style="font-size:18px; margin:20px 0;">Your ride request has been sent. Waiting for a driver to accept...</p>
        
        <div style="margin:40px auto; max-width:300px;">
          <div style="font-size:48px; font-weight:bold; color:#0066ff; margin-bottom:10px;">
            <span id="waiting-time">0:00</span>
          </div>
          <div style="font-size:14px; color:#999;">
            Time waiting
          </div>
        </div>
        
        <div id="waiting-status" style="font-size:16px; color:#666; margin-top:20px;">
          Searching for available drivers...
        </div>
        
        <div style="margin-top:30px;">
          <button onclick="cancelRequest()" style="padding:10px 20px; background:#f44336; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">
            Cancel Request
          </button>
        </div>
      </div>
    `;
    
    // Start polling
    startPolling();
  }

  function startPolling() {
    pollingAttempts = 0; // Reset attempts when starting
    
    // Start with the elapsed seconds from server (could be 0 if new request)
    let initialElapsedSeconds = sessionData.rideRequestStartTime || 0;
    const clientStartTime = Date.now();
    
    pollingInterval = setInterval(async () => {
      pollingAttempts++;
      
      // Calculate total elapsed seconds: initial + time since page load
      const clientElapsed = Math.floor((Date.now() - clientStartTime) / 1000);
      const elapsedSeconds = initialElapsedSeconds + clientElapsed;
      const minutes = Math.floor(elapsedSeconds / 60);
      const seconds = elapsedSeconds % 60;
      const timeEl = document.getElementById('waiting-time');
      if (timeEl) {
        timeEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
      }
      
      // Update status message with animated dots
      const statusEl = document.getElementById('waiting-status');
      if (statusEl) {
        const dots = '.'.repeat((pollingAttempts % 3) + 1);
        statusEl.textContent = `Searching for available drivers${dots}`;
      }
      
      // Check if driver accepted
      try {
        const usersId = sessionData.usersId;
        const response = await fetch(`check_driver_status.php?users_id=${usersId}`);
        const data = await response.json();
        
        if (data.status === 'accepted') {
          clearInterval(pollingInterval);
          showDriverAccepted(data);
        } else if (data.status === 'cancelled') {
          clearInterval(pollingInterval);
          showRequestCancelled();
        }
      } catch (err) {
        console.error('Polling error:', err);
      }
      
      // Timeout after max attempts
      if (pollingAttempts >= maxPollingAttempts) {
        clearInterval(pollingInterval);
        showTimeout();
      }
    }, 1000); // Poll every 1 second
  }

  function showDriverAccepted(data) {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';
    // Create popup
    const popup = document.createElement('div');
    popup.style.cssText = 'background:white;padding:30px 40px;border-radius:15px;box-shadow:0 4px 20px rgba(0,0,0,0.3);max-width:400px;text-align:center;';
    popup.innerHTML = `
      <h2 style="color:#4CAF50;">✓ Driver Accepted!</h2>
      <p style="font-size:18px; margin:20px 0;">A driver has accepted your ride request.</p>
      <p>Redirecting to ride tracking...</p>
    `;
    overlay.appendChild(popup);
    document.body.appendChild(overlay);
    localStorage.setItem('current_ride_id', data.ride_id);
    setTimeout(() => { window.location.href = 'ride_tracking.html'; }, 2000);
  }

  function showTimeout() {
    const container = document.querySelector('main.main');
    container.innerHTML = `
      <div style="text-align:center; padding:40px;">
        <h2 style="color:#ff9800;">⏱️ Request Timeout</h2>
        <p style="font-size:18px; margin:20px 0;">No driver accepted your request within the time limit.</p>
        <button onclick="location.reload()" style="padding:10px 20px; background:#2196F3; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">
          Try Again
        </button>
      </div>
    `;
  }

  function showRequestCancelled() {
    const container = document.querySelector('main.main');
    container.innerHTML = `
      <div style="text-align:center; padding:40px;">
        <h2 style="color:#f44336;">✕ Request Cancelled</h2>
        <p style="font-size:18px; margin:20px 0;">Your ride request has been cancelled.</p>
        <button onclick="window.history.back()" style="padding:10px 20px; background:#2196F3; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">
          Go Back
        </button>
      </div>
    `;
  }

  function showCancelConfirmation() {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:9999;';
    
    const popup = document.createElement('div');
    popup.style.cssText = 'background:white; padding:30px; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.3); max-width:400px; text-align:center;';
    popup.innerHTML = `
      <h3 style="margin:0 0 15px 0; color:#333; font-size:22px;">Cancel Ride Request?</h3>
      <p style="margin:0 0 25px 0; color:#666; font-size:16px;">Are you sure you want to cancel your ride request?</p>
      <div style="display:flex; gap:15px; justify-content:center;">
        <button id="cancel-no" style="flex:1; padding:12px 24px; background:#f5f5f5; color:#333; border:none; border-radius:8px; font-size:16px; cursor:pointer; font-weight:600;">
          No, Keep Waiting
        </button>
        <button id="cancel-yes" style="flex:1; padding:12px 24px; background:#f44336; color:white; border:none; border-radius:8px; font-size:16px; cursor:pointer; font-weight:600;">
          Yes, Cancel
        </button>
      </div>
    `;
    
    overlay.appendChild(popup);
    document.body.appendChild(overlay);
    
    document.getElementById('cancel-no').onclick = () => {
      document.body.removeChild(overlay);
    };
    
    document.getElementById('cancel-yes').onclick = async () => {
      document.body.removeChild(overlay);
      clearInterval(pollingInterval);
      try {
        const usersId = sessionData.usersId;
        await fetch('cancel_ride_request.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ users_id: usersId })
        });
        showRequestCancelled();
      } catch (err) {
        console.error('Cancel error:', err);
        showRequestCancelled();
      }
    };
  }

  async function cancelRequest() {
    showCancelConfirmation();
  }

  function showExistingRequestPopup() {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:9999;';
    
    const popup = document.createElement('div');
    popup.style.cssText = 'background:white; padding:30px; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.3); max-width:450px; text-align:center;';
    popup.innerHTML = `
      <h3 style="margin:0 0 15px 0; color:#ff9800; font-size:22px;">⚠️ Request Already Exists</h3>
      <p style="margin:0 0 25px 0; color:#666; font-size:16px;">You already have a pending ride request. What would you like to do?</p>
      <div style="display:flex; flex-direction:column; gap:12px;">
        <button id="existing-view" style="padding:14px 24px; background:#0066ff; color:white; border:none; border-radius:8px; font-size:16px; cursor:pointer; font-weight:600;">
          View Request
        </button>
        <button id="existing-cancel" style="padding:14px 24px; background:#f44336; color:white; border:none; border-radius:8px; font-size:16px; cursor:pointer; font-weight:600;">
          Cancel Request
        </button>
        <button id="existing-back" style="padding:14px 24px; background:#f5f5f5; color:#333; border:none; border-radius:8px; font-size:16px; cursor:pointer; font-weight:600;">
          Go Back
        </button>
      </div>
    `;
    
    overlay.appendChild(popup);
    document.body.appendChild(overlay);
    
    document.getElementById('existing-back').onclick = () => {
      document.body.removeChild(overlay);
      window.history.back();
    };
    
    document.getElementById('existing-view').onclick = () => {
      document.body.removeChild(overlay);
      showWaitingScreen();
    };
    
    document.getElementById('existing-cancel').onclick = () => {
      document.body.removeChild(overlay);
      showCancelConfirmation();
    };
  }

  function goBack(){ window.history.back(); }

  loadAddresses();
  // vehicle type name already injected if available
</script>
</body>
</html>