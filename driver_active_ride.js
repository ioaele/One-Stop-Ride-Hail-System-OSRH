// Driver Active Ride Manager
let map;
let driverMarker;
let dropoffMarker;
let routeControl;
let routeLine; // Simple line for frequent updates
let rideData = null;
let driverLocation = null;
let locationWatchId = null;
let rideStartTime = null;
let lastSpeed = 0;
let lastRouteUpdate = 0;
let lastLocationUpdate = 0;

// Distance threshold to complete ride (100m)
const COMPLETE_THRESHOLD = 100;
const ROUTE_UPDATE_INTERVAL = 60000; // Update full route every 1 minute (less frequent)

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadRideData();
    rideStartTime = Date.now();
});

// Load active ride data
async function loadRideData() {
    try {
        const response = await fetch('get_driver_active_ride.php');
        const data = await response.json();

        if (!data.success) {
            showMessage(data.error || 'No active ride found', 'error');
            setTimeout(() => {
                window.location.href = 'get_driver_requests.html';
            }, 3000);
            return;
        }

        rideData = data.data;
        displayRideInfo();
        initializeMap();
        startLocationTracking();
        startDurationTimer();
        
        document.getElementById('loadingContainer').style.display = 'none';
        document.getElementById('contentContainer').style.display = 'flex';

    } catch (error) {
        showMessage('Error loading ride data: ' + error.message, 'error');
    }
}

// Display ride information
function displayRideInfo() {
    document.getElementById('riderName').textContent = rideData.rider_username;
    document.getElementById('riderPhone').textContent = rideData.rider_phone;
}

// Initialize map
function initializeMap() {
    // Create map centered on dropoff location
    map = L.map('map').setView([rideData.dropoff_latitude, rideData.dropoff_longitude], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add dropoff marker (red)
    dropoffMarker = L.marker([rideData.dropoff_latitude, rideData.dropoff_longitude], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);
    dropoffMarker.bindPopup('<b>Dropoff Location</b>').openPopup();

    // Add completion threshold circle
    L.circle([rideData.dropoff_latitude, rideData.dropoff_longitude], {
        color: '#28a745',
        fillColor: '#28a745',
        fillOpacity: 0.1,
        radius: COMPLETE_THRESHOLD
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
            timeout: 10000,
            maximumAge: 5000  // Allow cached position up to 5 seconds old
        }
    );
}

// Update driver location
function updateDriverLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    lastSpeed = position.coords.speed || 0;
    const now = Date.now();

    // Throttle all updates to once per minute
    if (now - lastLocationUpdate < 60000) return;
    lastLocationUpdate = now;

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

    // Update simple line
    if (routeLine) {
        routeLine.setLatLngs([
            [lat, lng],
            [rideData.dropoff_latitude, rideData.dropoff_longitude]
        ]);
    } else {
        routeLine = L.polyline([
            [lat, lng],
            [rideData.dropoff_latitude, rideData.dropoff_longitude]
        ], {
            color: '#667eea',
            weight: 3,
            opacity: 0.3,
            dashArray: '5, 10'
        }).addTo(map);
    }

    // Only update full route every 60 seconds (expensive operation)
    if (now - lastRouteUpdate > ROUTE_UPDATE_INTERVAL) {
        updateFullRoute(lat, lng);
        lastRouteUpdate = now;
    }

    // Calculate distance to dropoff using SQL Server (STDistance)
    fetch('get_distance_to_dropoff.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const distance = data.distance_meters;
            updateDistanceDisplay(distance);
            updateSpeedDisplay(lastSpeed);
            checkProximity(distance);
        } else {
            showMessage(data.error || 'Failed to calculate distance', 'error');
        }
    })
    .catch(() => {
        showMessage('Network error while calculating distance', 'error');
    });
}

// Update route between driver and dropoff (expensive - called infrequently)
function updateFullRoute(lat, lng) {
    // Remove previous route control if it exists
    if (routeControl) {
        try {
            map.removeControl(routeControl);
        } catch (e) {
            // Defensive: ignore if already removed
        }
        routeControl = null;
    }

    // Add new route control with instructions panel visible

    routeControl = L.Routing.control({
        waypoints: [
            L.latLng(lat, lng),
            L.latLng(rideData.dropoff_latitude, rideData.dropoff_longitude)
        ],
        routeWhileDragging: false,
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: false, // Don't auto-fit to avoid jarring map movements
        showAlternatives: false,
        show: true, // Show turn-by-turn instructions panel
        lineOptions: {
            styles: [{
                color: '#667eea',
                opacity: 0.8,
                weight: 6
            }]
        },
        createMarker: function() { return null; }, // Don't create markers, we have our own
        containerClassName: '',
        routeDragInterval: 0
    }).addTo(map);

    // Move the routing instructions panel into our custom div
    setTimeout(() => {
        const routingContainer = document.querySelector('.leaflet-routing-container');
        const instructionsDiv = document.getElementById('routing-instructions');
        if (routingContainer && instructionsDiv) {
            instructionsDiv.appendChild(routingContainer);
            routingContainer.style.display = '';
        }
        // Force map to recalculate size/layout in case container changes
        if (map && typeof map.invalidateSize === 'function') {
            map.invalidateSize();
        }
    }, 100);

    // Hide the dashed line when we have the full route
    if (routeLine) {
        routeLine.setStyle({ opacity: 0 });
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
}

// Update speed display
function updateSpeedDisplay(speed) {
    const speedElement = document.getElementById('speedValue');
    
    if (speed > 0) {
        const kmh = (speed * 3.6).toFixed(0); // Convert m/s to km/h
        speedElement.textContent = kmh + ' km/h';
    } else {
        speedElement.textContent = '0 km/h';
    }
}

// Start duration timer
function startDurationTimer() {
    setInterval(() => {
        const elapsed = Date.now() - rideStartTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        
        document.getElementById('durationValue').textContent = 
            `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

// Check if driver is close enough to complete ride
function checkProximity(distance) {
    const completeBtn = document.getElementById('completeRideBtn');
    
    if (distance <= COMPLETE_THRESHOLD) {
        completeBtn.disabled = false;
        completeBtn.textContent = '🏁 Complete Ride';
        completeBtn.onclick = completeRide;
        
        if (!completeBtn.classList.contains('ready-notified')) {
            showMessage('You are at the dropoff point! You can now complete the ride.', 'success');
            completeBtn.classList.add('ready-notified');
        }
    } else {
        completeBtn.disabled = true;
        const distanceLeft = Math.round(distance - COMPLETE_THRESHOLD);
        completeBtn.textContent = `🚗 Get ${distanceLeft}m closer to complete`;
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

// Complete the ride
async function completeRide() {
    if (!driverLocation) {
        showMessage('Location not available. Please wait for GPS signal.', 'error');
        return;
    }

    const completeBtn = document.getElementById('completeRideBtn');
    completeBtn.disabled = true;
    completeBtn.textContent = '⏳ Completing ride...';

    try {
        const formData = new FormData();
        formData.append('ride_request_id', rideData.ride_request_id);
        formData.append('current_lat', driverLocation.lat);
        formData.append('current_long', driverLocation.lng);

        const response = await fetch('driver_complete_ride.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showMessage('Ride completed successfully!', 'success');
            
            // Stop location tracking
            if (locationWatchId) {
                navigator.geolocation.clearWatch(locationWatchId);
            }

            // Redirect to driver dashboard after 2 seconds
            setTimeout(() => {
                window.location.href = 'get_driver_requests.html';
            }, 2000);
        } else {
            showMessage(data.error || 'Failed to complete ride', 'error');
            completeBtn.disabled = false;
            completeBtn.textContent = '🏁 Complete Ride';
        }

    } catch (error) {
        showMessage('Error completing ride: ' + error.message, 'error');
        completeBtn.disabled = false;
        completeBtn.textContent = '🏁 Complete Ride';
    }
}

// Show message
function showMessage(text, type) {
    const messageEl = document.getElementById('message');
    messageEl.textContent = text;
    messageEl.className = 'message ' + type;
    messageEl.style.display = 'block';

    if (type === 'success') {
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }
}
