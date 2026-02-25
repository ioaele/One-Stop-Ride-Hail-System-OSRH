/**
 * Leaflet Map Implementation
 * Replaces Google Maps with open-source Leaflet.js
 */

let map;
let markers = {};
let selectedMarker = null;
let selectedPopup = null;

/**
 * Initialize the map
 * @param {string} containerId - The ID of the container element
 * @param {Object} options - Configuration options
 *   - center: {lat, lng} - Map center (default: {lat: 53.54, lng: 10})
 *   - zoom: number - Initial zoom level (default: 12)
 */
function initMap(containerId = 'map', options = {}) {
  const defaultCenter = { lat: 53.54, lng: 10 };
  const center = options.center || defaultCenter;
  const zoom = options.zoom || 12;

  // Initialize the map
  map = L.map(containerId).setView([center.lat, center.lng], zoom);
  
  // Make map available globally
  window.map = map;

  // Add OpenStreetMap tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map);

  console.log('Leaflet map initialized');
  return map;
}

/**
 * Add or update vehicles on the map
 * @param {Array} vehicles - Array of vehicle objects with {id, lat, lng, label, type}
 */
function updateVehicles(vehicles = []) {
  if (!map) {
    console.error('Map not initialized. Call initMap() first.');
    return;
  }

  vehicles.forEach((vehicle) => {
    if (markers[vehicle.id]) {
      // Update existing marker position
      markers[vehicle.id].setLatLng([vehicle.lat, vehicle.lng]);
    } else {
      // Create new marker
      const markerColor = vehicle.type === 'taxi' ? 'yellow' : 'purple';
      const marker = L.circleMarker([vehicle.lat, vehicle.lng], {
        radius: 8,
        fillColor: markerColor,
        color: 'black',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8,
        vehicle: vehicle
      });

      marker.addTo(map);

      // Click handler to show info popup
      marker.on('click', function () {
        selectVehicle(vehicle);
      });

      markers[vehicle.id] = marker;
    }
  });
}

/**
 * Select a vehicle and show its info in a popup
 * @param {Object} vehicle - The vehicle object with {id, lat, lng, label, type}
 */
function selectVehicle(vehicle) {
  selectedMarker = vehicle;

  // Close previous popup if exists
  if (selectedPopup) {
    map.closePopup(selectedPopup);
  }

  // Create popup content
  const popupContent = `
    <div style="font-family: Arial, sans-serif; padding: 8px;">
      <strong>${vehicle.label || 'Vehicle'}</strong><br>
      Type: ${vehicle.type || 'N/A'}<br>
      <button onclick="deselectVehicle()" style="margin-top: 8px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
    </div>
  `;

  // Show popup at vehicle location
  selectedPopup = L.popup()
    .setLatLng([vehicle.lat, vehicle.lng])
    .setContent(popupContent)
    .openOn(map);
}

/**
 * Deselect the current vehicle and close the popup
 */
function deselectVehicle() {
  selectedMarker = null;
  if (selectedPopup) {
    map.closePopup(selectedPopup);
    selectedPopup = null;
  }
}

/**
 * Remove a vehicle from the map
 * @param {string|number} vehicleId - The ID of the vehicle to remove
 */
function removeVehicle(vehicleId) {
  if (markers[vehicleId]) {
    map.removeLayer(markers[vehicleId]);
    delete markers[vehicleId];
  }
}

/**
 * Clear all vehicles from the map
 */
function clearAllVehicles() {
  Object.keys(markers).forEach((id) => {
    map.removeLayer(markers[id]);
  });
  markers = {};
}

/**
 * Pan map to a specific location
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {number} zoom - Optional zoom level
 */
function panTo(lat, lng, zoom = null) {
  if (!map) {
    console.error('Map not initialized.');
    return;
  }
  if (zoom) {
    map.setView([lat, lng], zoom);
  } else {
    map.panTo([lat, lng]);
  }
}

/**
 * Fit map to show all markers
 */
function fitToMarkers() {
  if (!map || Object.keys(markers).length === 0) {
    console.warn('No markers to fit.');
    return;
  }
  const group = new L.featureGroup(Object.values(markers));
  map.fitBounds(group.getBounds(), { padding: [50, 50] });
}

/**
 * Add a custom marker at a specific location (e.g., pickup/dropoff point)
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {Object} options - Marker options {label, color, icon}
 * @returns {Object} The marker object
 */
function addCustomMarker(lat, lng, options = {}) {
  if (!map) {
    console.error('Map not initialized.');
    return null;
  }

  const defaultOptions = {
    label: 'Marker',
    color: 'blue',
    ...options
  };

  const marker = L.circleMarker([lat, lng], {
    radius: 8,
    fillColor: defaultOptions.color,
    color: 'black',
    weight: 2,
    opacity: 1,
    fillOpacity: 0.8
  });

  marker.bindPopup(defaultOptions.label);
  marker.addTo(map);

  return marker;
}

/**
 * Get the current map center
 * @returns {Object} {lat, lng}
 */
function getMapCenter() {
  if (!map) return null;
  const center = map.getCenter();
  return { lat: center.lat, lng: center.lng };
}

/**
 * Get the current zoom level
 * @returns {number} Zoom level
 */
function getZoomLevel() {
  if (!map) return null;
  return map.getZoom();
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    initMap,
    updateVehicles,
    selectVehicle,
    deselectVehicle,
    removeVehicle,
    clearAllVehicles,
    panTo,
    fitToMarkers,
    addCustomMarker,
    getMapCenter,
    getZoomLevel
  };
}
