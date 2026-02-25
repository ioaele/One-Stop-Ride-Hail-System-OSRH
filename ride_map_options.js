// Load stored data from PHP session (fallback to null)
// These variables will be injected by the PHP file via inline script
// const service, serviceType, serviceTypeName, pickupLat, pickupLng, dropoffLat, dropoffLng;

// Display service info
function initializePageData(sessionData) {
  document.getElementById("service-name").textContent = sessionData.service || "N/A";
  document.getElementById("ride-type").textContent = sessionData.serviceTypeName || sessionData.serviceType || "N/A";
}

// Reverse geocoding function using PHP backend
async function getAddress(lat, lng) {
  try {
    const response = await fetch(`reverse_geocode.php?lat=${lat}&lng=${lng}`);
    
    if (!response.ok) {
      throw new Error('Geocoding failed');
    }
    
    const data = await response.json();
    
    if (data.success && data.address) {
      return data.address;
    } else {
      return `${lat}, ${lng}`;
    }
    
  } catch (error) {
    console.error('Geocoding error:', error);
    return `${lat}, ${lng}`; // Fallback to coordinates
  }
}

// Load addresses
async function loadAddresses(pickupLat, pickupLng, dropoffLat, dropoffLng) {
  if (pickupLat && pickupLng) {
    const pickupAddress = await getAddress(pickupLat, pickupLng);
    document.getElementById("pickup-address").textContent = pickupAddress;
    document.getElementById("pickup-address").classList.remove("address-loading");
  } else {
    document.getElementById("pickup-address").textContent = "Not set";
  }

  if (dropoffLat && dropoffLng) {
    const dropoffAddress = await getAddress(dropoffLat, dropoffLng);
    document.getElementById("dropoff-address").textContent = dropoffAddress;
    document.getElementById("dropoff-address").classList.remove("address-loading");
  } else {
    document.getElementById("dropoff-address").textContent = "Not set";
  }
}

// Price estimation (dummy)


// Fetch distance in meters from backend (uses SQL Server STDistance)
async function fetchDistanceMeters(lat1, lng1, lat2, lng2) {
  if (lat1==null||lng1==null||lat2==null||lng2==null) return 0;
  try {
    const res = await fetch(`get_distance_between_points.php?lat1=${lat1}&lng1=${lng1}&lat2=${lat2}&lng2=${lng2}`);
    const data = await res.json();
    if (data.success && typeof data.distance_meters === 'number') {
      return data.distance_meters;
    }
    return 0;
  } catch (e) {
    return 0;
  }
}

// Price estimation based on distance (same as ride_confirm.php)
function estimatePriceByDistance(distanceKm) {
  return (3 + distanceKm * 1.5).toFixed(2);
}

// ETA estimation based on distance (same as ride_confirm.php)
function estimateETAByDistance(distanceKm) {
  return distanceKm ? Math.max(5, Math.floor((distanceKm/40)*60)) : 0;
}

// Fetch available vehicle types
function loadVehicleTypes(serviceType, pickupLat, pickupLng) {
  const container = document.getElementById("vehicle-list");
  container.innerHTML = "";

  if (!serviceType) {
    container.innerHTML = "<p>No ride type selected (service_type missing).</p>";
    return;
  }
  if (!pickupLat || !pickupLng) {
    container.innerHTML = "<p>Pickup coordinates missing.</p>";
    return;
  }

  fetch("get_available_vehicle_types.php?_=" + Date.now(), {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ service_type_id: serviceType, lat: pickupLat, lng: pickupLng })
  })
  .then(res => {
    if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
    return res.json();
  })
  .then(data => {
    if (!data || data.status !== 'success') {
      container.innerHTML = "<p>No vehicle types available.</p>";
      return;
    }

    // normalize returned rows so `vehicle_type` is always available
    data.vehicle_types = (data.vehicle_types || []).map(v => {
      v.vehicle_type = v.vehicle_type || v.name || v.vehicle_type_name || v.vehicleType || v.vehicle_type_id || 'Unknown';
      return v;
    });

    // Calculate distance between pickup and dropoff for price/ETA using backend
    const dropoffLat = window.dropoffLat !== undefined ? window.dropoffLat : (typeof sessionData !== 'undefined' ? sessionData.dropoffLat : null);
    const dropoffLng = window.dropoffLng !== undefined ? window.dropoffLng : (typeof sessionData !== 'undefined' ? sessionData.dropoffLng : null);

    fetchDistanceMeters(pickupLat, pickupLng, dropoffLat, dropoffLng).then(distanceMeters => {
      const distanceKm = distanceMeters / 1000;
      (data.vehicle_types || []).forEach(v => {
        const price = estimatePriceByDistance(distanceKm);
        const eta   = estimateETAByDistance(distanceKm);

        const card = document.createElement("div");
        card.className = "vehicle-card";

        const displayName = v.vehicle_type;
        // Choose an icon based on vehicle_type (local server images)
        const type = (v.vehicle_type || '').toLowerCase();
        let iconUrl = 'vehicle_icons/default.png';
        if (type.includes('hatchback')) iconUrl = 'vehicle_icons/hatchback.png';
        else if (type.includes('suv')) iconUrl = 'vehicle_icons/suv.png';
        else if (type.includes('sedan')) iconUrl = 'vehicle_icons/sedan.png';
        else if (type.includes('van')) iconUrl = 'vehicle_icons/van.png';


        card.innerHTML = `
          <div class="vehicle-info">
            <img src="${iconUrl}" alt="${displayName}">
            <div>
              <strong>${displayName}</strong><br>
              ETA: ${eta} min<br>
              Price: &euro;${price}
            </div>
          </div>

          <button class="choose-btn" onclick="chooseVehicle(${v.vehicle_type_id})">
            Choose
          </button>
        `;

        container.appendChild(card);
      });
    });
  })
  .catch(err => {
    container.innerHTML = `<p>Error loading vehicle types: ${err.message}</p>`;
    console.error('Vehicle types fetch error', err);
  });
}

function chooseVehicle(vehicleTypeId) {
  // save selection to session via POST then go to confirmation
  fetch('set_session.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `selected_vehicle_type_id=${encodeURIComponent(vehicleTypeId)}`
  }).finally(() => {
    window.location.href = 'ride_confirm.php';
  });
}
