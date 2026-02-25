// Driver Start Ride Manager
let map;
let driverMarker;
let pickupMarker;
let rideData = null;
let driverLocation = null;
let lastFitBounds = 0;
let lastLatLng = null;
let locationWatchId = null;

// Distance threshold in meters (300m)
const DISTANCE_THRESHOLD = 300;

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadRideData();
});

// Load accepted ride data
async function loadRideData() {
    try {
        const response = await fetch('get_driver_accepted_ride.php');
        const data = await response.json();

        if (!data.success) {
            showMessage(data.error || 'No accepted ride found', 'error');
            setTimeout(() => {
                window.location.href = 'get_driver_requests.html';
            }, 3000);
            return;
        }

        rideData = data.data;
        displayRideInfo();
        initializeMap();
        startLocationTracking();
        
        document.getElementById('loadingContainer').style.display = 'none';
        document.getElementById('contentContainer').style.display = 'block';

    } catch (error) {
        showMessage('Error loading ride data: ' + error.message, 'error');
    }
}

// Display ride information
function displayRideInfo() {
    document.getElementById('riderName').textContent = rideData.rider_username;
    document.getElementById('riderPhone').textContent = rideData.rider_phone;
    document.getElementById('vehicleType').textContent = rideData.vehicle_type_requested || 'Any';
    
    const requestDate = new Date(rideData.request_time);
    document.getElementById('requestTime').textContent = requestDate.toLocaleString();
}

// Initialize map
function initializeMap() {
    // Create map centered on pickup location
    map = L.map('map').setView([rideData.pickup_latitude, rideData.pickup_longitude], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add pickup marker (green)
    pickupMarker = L.marker([rideData.pickup_latitude, rideData.pickup_longitude], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);
    pickupMarker.bindPopup('<b>Pickup Location</b><br>Navigate here to start ride').openPopup();

    // Add circle showing the 200m threshold
    L.circle([rideData.pickup_latitude, rideData.pickup_longitude], {
        color: '#28a745',
        fillColor: '#28a745',
        fillOpacity: 0.1,
        radius: DISTANCE_THRESHOLD
    }).addTo(map);
}

// Start tracking driver location
function startLocationTracking() {
    if (!navigator.geolocation) {
        showMessage('Geolocation is not supported by your browser', 'error');
        return;
    }

    locationWatchId = navigator.geolocation.watchPosition(
        updateDriverLocation,
        handleLocationError,
        {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        }
    );
}

// Update driver location
function updateDriverLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    
    driverLocation = { lat, lng };

    // Update or create driver marker (blue)
    if (driverMarker) {
        driverMarker.setLatLng([lat, lng]);
    } else {
        driverMarker = L.marker([lat, lng], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(map);
        driverMarker.bindPopup('<b>Your Location</b>');
    }

    // Calculate distance to pickup
    const distance = calculateDistance(
        lat, lng,
        rideData.pickup_latitude, rideData.pickup_longitude
    );

    updateDistanceDisplay(distance);
    checkProximity(distance);

    // Only fit map on first update or if driver moves >200m
    const now = Date.now();
    const fitInterval = 20000; // 20 seconds
    let shouldFit = false;
    if (!lastLatLng) {
        shouldFit = true;
    } else {
        const moved = calculateDistance(lat, lng, lastLatLng.lat, lastLatLng.lng);
        if (moved > 200) shouldFit = true;
    }
    if (shouldFit && driverMarker && pickupMarker && (now - lastFitBounds > fitInterval)) {
        map.fitBounds([
            [lat, lng],
            [rideData.pickup_latitude, rideData.pickup_longitude]
        ], { padding: [50, 50] });
        lastFitBounds = now;
        lastLatLng = { lat, lng };
    }
}

// Calculate distance between two points (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c; // Distance in meters
}

// Update distance display
function updateDistanceDisplay(distance) {
    const distanceElement = document.getElementById('distanceValue');
    
    if (distance < 1000) {
        distanceElement.textContent = Math.round(distance) + ' m';
    } else {
        distanceElement.textContent = (distance / 1000).toFixed(2) + ' km';
    }

    // Change color based on distance
    if (distance <= DISTANCE_THRESHOLD) {
        distanceElement.style.color = '#28a745';
    } else if (distance <= 500) {
        distanceElement.style.color = '#ffc107';
    } else {
        distanceElement.style.color = '#667eea';
    }
}

// Check if driver is close enough to start ride
function checkProximity(distance) {
    const startBtn = document.getElementById('startRideBtn');
    
    if (distance <= DISTANCE_THRESHOLD) {
        startBtn.disabled = false;
        startBtn.textContent = '🚦 Start Ride';
        startBtn.onclick = startRide;
        
        if (!startBtn.classList.contains('ready-notified')) {
            showMessage('You are within range! You can now start the ride.', 'success');
            startBtn.classList.add('ready-notified');
        }
    } else {
        startBtn.disabled = true;
        const distanceLeft = Math.round(distance - DISTANCE_THRESHOLD);
        startBtn.textContent = `🚗 Get ${distanceLeft}m closer to start`;
    }
}

// Handle location errors
function handleLocationError(error) {
    let message = 'Location error: ';
    switch(error.code) {
        case error.PERMISSION_DENIED:
            message += 'Please enable location access';
            break;
        case error.POSITION_UNAVAILABLE:
            message += 'Location information unavailable';
            break;
        case error.TIMEOUT:
            message += 'Location request timed out';
            break;
        default:
            message += 'Unknown error';
    }
    showMessage(message, 'error');
}

// Start the ride
async function startRide() {
    const startBtn = document.getElementById('startRideBtn');
    startBtn.disabled = true;
    startBtn.textContent = '⏳ Starting ride...';

    try {
        const formData = new FormData();
        formData.append('ride_request_id', rideData.ride_request_id);
        formData.append('rider_users_id', rideData.rider_users_id);
        formData.append('pickup_point_id', rideData.pickup_point_id);
        formData.append('dropoff_point_id', rideData.dropoff_point_id);
        formData.append('vehicle_type_requested', rideData.vehicle_type_requested);

        const response = await fetch('driver_create_ride.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showMessage('Ride started successfully!', 'success');
            
            // Stop location tracking
            if (locationWatchId) {
                navigator.geolocation.clearWatch(locationWatchId);
            }

            // Redirect to active ride page after 2 seconds
            setTimeout(() => {
                window.location.href = 'driver_active_ride.html';
            }, 2000);
        } else {
            showMessage(data.error || 'Failed to start ride', 'error');
            startBtn.disabled = false;
            startBtn.textContent = '🚦 Start Ride';
        }

    } catch (error) {
        showMessage('Error starting ride: ' + error.message, 'error');
        startBtn.disabled = false;
        startBtn.textContent = '🚦 Start Ride';
    }
}

// Show message
function showMessage(text, type) {
    const messageEl = document.getElementById('message');
    messageEl.textContent = text;
    messageEl.className = 'message ' + type;
    messageEl.style.display = 'block';

    if (type === 'success' || type === 'warning') {
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }
}
