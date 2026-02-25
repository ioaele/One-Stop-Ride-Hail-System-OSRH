
document.addEventListener('DOMContentLoaded', () => {
    const storedUsername = localStorage.getItem('username') || 'User';
  
    const navbarUsernameEl = document.getElementById('navbar-username');
    const heroUsernameEl   = document.getElementById('hero-username');
  
    if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
    if (heroUsernameEl) heroUsernameEl.textContent = storedUsername;

    // Initialize map and fetch vehicles
    initMap();
});

function initMap() {
  const mapDiv = document.getElementById("map");
  
  if (!mapDiv) {
    console.error('Map container not found');
    return;
  }

  // Cyprus center coordinates (fallback)
  const cyprusCenter = { lat: 35.1264, lng: 33.4299 };

  // Initialize Leaflet map
  const map = L.map('map').setView([cyprusCenter.lat, cyprusCenter.lng], 10);

  // Add OpenStreetMap tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map);

  // Try to get user's location and center map there with higher zoom
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;
        map.setView([userLat, userLng], 15);
        
        // Add a marker for user's location using Leaflet marker icon
        const userIcon = L.icon({
          iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        });
        
        L.marker([userLat, userLng], { icon: userIcon })
          .addTo(map)
          .bindPopup('<b>Your Location</b>')
          .openPopup();
        
        console.log('Map centered on user location:', { userLat, userLng });
      },
      (error) => {
        console.warn('Could not get user location, using Cyprus center:', error.message);
      }
    );
  }

  // Get rental data from session via PHP
  console.log('Fetching session data...');
  fetch('get_rental_session.php')
    .then(response => {
      console.log('Session response status:', response.status);
      return response.json();
    })
    .then(sessionData => {
      console.log('Rental data from session:', sessionData);

      const serviceTypeId = sessionData.service_type_id;
      const rentalStart = sessionData.rental_start;
      const rentalEnd = sessionData.rental_end;

      // Store globally for selectVehicle
      window.lastRentalStart = rentalStart;
      window.lastRentalEnd = rentalEnd;

      // Validate required data
      if (!serviceTypeId || !rentalStart || !rentalEnd) {
        showCustomPopup('Please go back and select rental dates and service type first.', 'error');
        console.error('Missing rental data:', { serviceTypeId, rentalStart, rentalEnd });
        setTimeout(() => {
          window.location.href = 'rent.html';
        }, 2000);
        return;
      }

      // Fetch available rental vehicles
      console.log('Fetching vehicles from get_available_rental_vehicles.php');
      return fetch('get_available_rental_vehicles.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          service_type_id: parseInt(serviceTypeId),
          rental_start: rentalStart,
          rental_end: rentalEnd
        })
      });
    })
    .then(response => {
      if (!response) return; // No response means validation failed above
      
      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers.get('content-type'));
      
      // Try to get response text first to see what we actually received
      return response.text().then(text => {
        console.log('Raw response:', text);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response was:', text);
          throw new Error('Invalid JSON response from server');
        }
      });
    })
    .then(data => {
      if (!data) return; // No data means error occurred above
      
      console.log('Received data:', data);
      
      if (data.status === 'error') {
        showCustomPopup(data.message || 'Error loading vehicles', 'error');
        return;
      }

      if (!data.vehicles || data.vehicles.length === 0) {
        showCustomPopup('No available vehicles found for the selected dates and service type.', 'info');
        return;
      }

      // Fit map to show all markers
      if (data.vehicles.length > 0) {
        const bounds = data.vehicles
          .filter(v => v.current_lat && v.current_lng)
          .map(v => [parseFloat(v.current_lat), parseFloat(v.current_lng)]);
        
        if (bounds.length > 0) {
          map.fitBounds(bounds, { padding: [50, 50] });
        }
      }

      // Store vehicles globally for selectVehicle
      window.lastLoadedVehicles = data.vehicles;
      // Add markers for each vehicle
      data.vehicles.forEach(vehicle => {
        if (!vehicle.current_lat || !vehicle.current_lng) {
          console.warn('Vehicle missing coordinates:', vehicle);
          return;
        }

        const lat = parseFloat(vehicle.current_lat);
        const lng = parseFloat(vehicle.current_lng);


        // Choose an icon based on vehicle_type_name (local server images)
        const type = (vehicle.vehicle_type_name || '').toLowerCase();
        let iconUrl = 'vehicle_icons/default.png';
        if (type.includes('hatchback')) iconUrl = 'vehicle_icons/hatchback.png';
        else if (type.includes('suv')) iconUrl = 'vehicle_icons/suv.png';
        else if (type.includes('sedan')) iconUrl = 'vehicle_icons/sedan.png';
        else if (type.includes('van')) iconUrl = 'vehicle_icons/van.png';
        // Add more mappings as needed

        const vehicleIcon = L.icon({
          iconUrl,
          iconSize: [40, 40],
          iconAnchor: [20, 40],
          popupAnchor: [0, -40],
        });

        // Add marker with custom icon
        const marker = L.marker([lat, lng], { icon: vehicleIcon }).addTo(map);


        // Add vehicle type as a tooltip (only visible on hover/tap, like 'my location')
        if (vehicle.vehicle_type_name) {
          marker.bindTooltip(
            vehicle.vehicle_type_name,
            {
              direction: 'top',
              offset: [0, -18],
              permanent: false,
              className: 'vehicle-type-tooltip smooth-fade-tooltip',
              opacity: 0.95
            }
          );
        }
// Add smooth fade-in for tooltips (let Leaflet handle opacity, just add transition)
const style = document.createElement('style');
style.innerHTML = `
.vehicle-type-tooltip.leaflet-tooltip {
  background: #1976d2;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(25, 118, 210, 0.15);
  padding: 2px 10px;
  border: none;
  min-width: 0;
  min-height: 0;
  line-height: 1.4;
  letter-spacing: 0.02em;
  transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1);
}
.smooth-fade-tooltip.leaflet-tooltip {
  transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1);
}
`;
document.head.appendChild(style);

        // Create popup content
        const popupContent = `
          <div style="font-family: Arial, sans-serif; padding: 8px;">
            <strong>Type:</strong> ${vehicle.vehicle_type_name || 'N/A'}<br>
            <strong>License:</strong> ${vehicle.license_plate || 'N/A'}<br>
            <button onclick="selectVehicle(${vehicle.vehicle_id}, '${vehicle.license_plate}')" 
                    style="margin-top: 8px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
              Select This Vehicle
            </button>
          </div>
        `;

        marker.bindPopup(popupContent);
      });

      console.log(`Loaded ${data.vehicles.length} vehicles`);
    })
    .catch(err => {
      console.error('Error:', err);
      showCustomPopup('Network error loading vehicles. Please check your connection.', 'error');
    });
}

function toSqlDatetimeString(dateStr) {
  // Try to parse and format as YYYY-MM-DD HH:MM:SS
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return null;
  const pad = n => n < 10 ? '0' + n : n;
  return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' '
    + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
}

async function selectVehicle(vehicleId, licensePlate) {
  // Find the selected vehicle's full info from the map data
  let selectedVehicle = null;
  if (window.lastLoadedVehicles && Array.isArray(window.lastLoadedVehicles)) {
    selectedVehicle = window.lastLoadedVehicles.find(v => v.vehicle_id == vehicleId);
  }
  if (!selectedVehicle || !selectedVehicle.current_lat || !selectedVehicle.current_lng) {
    alert('Vehicle location not found.');
    return;
  }
  // Use rental_start and rental_end from session globals
  let rental_start = window.lastRentalStart;
  let rental_end = window.lastRentalEnd;

  // 1. Create point for vehicle location
  let point_id = null;
  try {
    const res = await fetch('create_point.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        latitude: selectedVehicle.current_lat,
        longitude: selectedVehicle.current_lng,
        start: 0 // pickup
      })
    });
    const data = await res.json();
    if (data.status === 'success' && data.point_id) {
      point_id = data.point_id;
    } else {
      alert('Failed to create pickup point: ' + (data.message || 'Unknown error'));
      return;
    }
  } catch (err) {
    alert('Network error creating pickup point.');
    return;
  }

  // 2. Store everything in session
  const payload = {
    selected_vehicle_id: vehicleId,
    vehicle_type_name: selectedVehicle.vehicle_type_name,
    license_plate: selectedVehicle.license_plate,
    rental_start,
    rental_end,
    pickup_point_id: point_id,
    dropoff_point_id: point_id // placeholder, will be updated at rental end
  };
  fetch('set_session.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(() => {
    // Redirect to confirmation page
    window.location.href = 'rent_confirm.html';
  })
  .catch(err => {
    console.error('Error saving selection:', err);
    alert('Error saving vehicle selection. Please try again.');
  });
}

function showCustomPopup(message, type = 'info') {
  const popup = document.createElement('div');
  popup.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    z-index: 10000;
    max-width: 400px;
    text-align: center;
  `;
  
  const color = type === 'error' ? '#dc3545' : type === 'info' ? '#0dcaf0' : '#28a745';
  
  popup.innerHTML = `
    <div style="color: ${color}; font-weight: bold; margin-bottom: 10px;">
      ${type === 'error' ? '⚠ ' : 'ℹ '}${message}
    </div>
    <button onclick="this.parentElement.remove()" 
            style="padding: 8px 16px; background: ${color}; color: white; border: none; border-radius: 4px; cursor: pointer;">
      OK
    </button>
  `;
  
  document.body.appendChild(popup);
}
