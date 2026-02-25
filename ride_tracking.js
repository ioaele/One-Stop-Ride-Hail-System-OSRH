// Ride Tracking Manager
let map;
let pickupMarker;
let dropoffMarker;
let driverMarker;
let routeLine;
let rideData = null;
let updateInterval = null;
let rideStartTime = null;

const UPDATE_INTERVAL = 5000; // Update driver location every 5 seconds

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadRideData();
    // Poll for ride completion every 5 seconds
    setInterval(async () => {
        try {
            const response = await fetch('get_user_active_ride.php', { credentials: 'same-origin' });
            const data = await response.json();
            if (!data.success || !data.data || data.data.status === 'Completed' || data.data.status === 'Finished' || data.data.status === 'Ended') {
                window.location.href = 'ride_payment.html';
            }
        } catch (e) {
            // Ignore errors, try again next poll
        }
    }, 5000);
});

// Load ride data
async function loadRideData() {
    try {
        const response = await fetch('get_user_active_ride.php', {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (!data.success) {
            showMessage(data.error || 'No active ride found', 'error');
            document.getElementById('loadingContainer').style.display = 'none';
            return;
        }

        rideData = data.data;
        displayRideInfo();
        initializeMap();
        
        // Start tracking if ride is in progress
        if (rideData.status === 'InProgress') {
            rideStartTime = new Date(rideData.request_time);
            startTracking();
            startDurationTimer();
        } else if (rideData.status === 'Accepted') {
            // Driver accepted but hasn't started yet
            showMessage('Driver is on the way to pick you up!', 'info');
            startTracking(); // Still track driver location
        }
        
        document.getElementById('loadingContainer').style.display = 'none';
        document.getElementById('contentContainer').style.display = 'flex';

    } catch (error) {
        showMessage('Error loading ride data: ' + error.message, 'error');
        document.getElementById('loadingContainer').style.display = 'none';
    }
}

// Display ride information
function displayRideInfo() {
    // Status badge
    const statusEl = document.getElementById('rideStatus');
    let statusClass = 'status-pending';
    let statusText = rideData.status;
    
    if (rideData.status === 'Accepted') {
        statusClass = 'status-accepted';
        statusText = 'Accepted';
    } else if (rideData.status === 'InProgress') {
        statusClass = 'status-inprogress';
        statusText = 'In Progress';
    }
    
    statusEl.innerHTML = `<span class="${statusClass}">${statusText}</span>`;
    
    // Driver info
    document.getElementById('driverName').textContent = rideData.driver_username || 'Waiting for driver...';
    document.getElementById('vehicleType').textContent = rideData.vehicle_type_name || '-';
    document.getElementById('requestTime').textContent = new Date(rideData.request_time).toLocaleString();
}

// Initialize map
function initializeMap() {
    // Center on pickup location
    map = L.map('map').setView([rideData.pickup_latitude, rideData.pickup_longitude], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
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
    pickupMarker.bindPopup('<b>Pickup Location</b>').openPopup();

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
    dropoffMarker.bindPopup('<b>Dropoff Location</b>');

    // Calculate and display estimated distance
    const distance = calculateDistance(
        rideData.pickup_latitude,
        rideData.pickup_longitude,
        rideData.dropoff_latitude,
        rideData.dropoff_longitude
    );
    updateEstimatedDistance(distance);

    // Draw route between pickup and dropoff
    drawRoute(rideData.pickup_latitude, rideData.pickup_longitude, 
              rideData.dropoff_latitude, rideData.dropoff_longitude);
}

// Draw route on map
function drawRoute(fromLat, fromLng, toLat, toLng) {
    L.Routing.control({
        waypoints: [
            L.latLng(fromLat, fromLng),
            L.latLng(toLat, toLng)
        ],
        routeWhileDragging: false,
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true,
        showAlternatives: false,
        show: false,
        lineOptions: {
            styles: [{
                color: '#667eea',
                opacity: 0.6,
                weight: 4,
                dashArray: '10, 10'
            }]
        },
        createMarker: function() { return null; }
    }).addTo(map);
}

// Start tracking driver location
function startTracking() {
    // Initial update
    updateDriverLocation();
    
    // Set interval for updates
    updateInterval = setInterval(updateDriverLocation, UPDATE_INTERVAL);
}

// Update driver location
async function updateDriverLocation() {
    try {
        const response = await fetch(`get_driver_location.php?ride_request_id=${rideData.ride_request_id}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success && data.data) {
            const driverLat = data.data.latitude;
            const driverLng = data.data.longitude;

            // Update or create driver marker (blue)
            if (driverMarker) {
                driverMarker.setLatLng([driverLat, driverLng]);
            } else {
                driverMarker = L.marker([driverLat, driverLng], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);
                driverMarker.bindPopup('<b>Driver Location</b>');
            }

            // Update line to driver
            if (routeLine) {
                map.removeLayer(routeLine);
            }

            const targetLat = rideData.status === 'InProgress' ? rideData.dropoff_latitude : rideData.pickup_latitude;
            const targetLng = rideData.status === 'InProgress' ? rideData.dropoff_longitude : rideData.pickup_longitude;

            routeLine = L.polyline([
                [driverLat, driverLng],
                [targetLat, targetLng]
            ], {
                color: '#28a745',
                weight: 3,
                opacity: 0.7,
                dashArray: '5, 10'
            }).addTo(map);

            // Calculate distance to driver
            const distance = calculateDistance(driverLat, driverLng, targetLat, targetLng);
            updateDriverDistance(distance);

            // Auto-fit map to show all markers
            const bounds = L.latLngBounds([
                [driverLat, driverLng],
                [targetLat, targetLng]
            ]);
            map.fitBounds(bounds, { padding: [50, 50] });

        }
    } catch (error) {
        console.error('Error updating driver location:', error);
    }
}

// Calculate distance using Haversine formula
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

// Update estimated distance display
function updateEstimatedDistance(distance) {
    const distanceEl = document.getElementById('estimatedDistance');
    if (distance < 1000) {
        distanceEl.textContent = Math.round(distance) + ' m';
    } else {
        distanceEl.textContent = (distance / 1000).toFixed(2) + ' km';
    }
}

// Update driver distance display
function updateDriverDistance(distance) {
    const distanceEl = document.getElementById('driverDistance');
    if (distance < 1000) {
        distanceEl.textContent = Math.round(distance) + ' m';
    } else {
        distanceEl.textContent = (distance / 1000).toFixed(2) + ' km';
    }
}

// Start duration timer
function startDurationTimer() {
    setInterval(() => {
        const elapsed = Date.now() - rideStartTime.getTime();
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        
        document.getElementById('rideDuration').textContent = 
            `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

// Show message
function showMessage(text, type) {
    const messageEl = document.getElementById('message');
    messageEl.textContent = text;
    messageEl.className = 'message ' + type;
    messageEl.style.display = 'block';

    if (type === 'success' || type === 'info') {
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});
