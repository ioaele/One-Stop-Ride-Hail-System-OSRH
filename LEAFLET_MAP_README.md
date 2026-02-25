# Leaflet Map Implementation

This folder contains a Leaflet.js-based map implementation that replaces the Google Maps API. Leaflet is an open-source, lightweight JavaScript library for building interactive maps.

## Files

- **`map.js`** - Main Leaflet map implementation with vehicle tracking
- **`map_example.html`** - Example page demonstrating map usage
- **`map.jsx`** - Original Google Maps React component (deprecated)

## Features

✅ Vehicle marker placement and updates  
✅ Interactive popups on marker click  
✅ Custom marker colors based on vehicle type  
✅ Map panning and zooming  
✅ Fit all markers to view  
✅ Add custom markers (pickup/dropoff)  
✅ Real-time vehicle position updates  
✅ No API key required (uses OpenStreetMap)  

## Setup

### 1. Include Leaflet in your HTML

Add these to your `<head>`:

```html
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

<!-- Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<!-- Your map script -->
<script src="map.js"></script>
```

### 2. Add a map container

```html
<div id="map" style="height: 500px; width: 100%;"></div>
```

### 3. Initialize and use

```javascript
// Initialize the map
initMap('map', { 
  center: { lat: 53.54, lng: 10 }, 
  zoom: 12 
});

// Add vehicles
const vehicles = [
  { id: 1, lat: 53.54, lng: 10, label: 'Taxi 1', type: 'taxi' },
  { id: 2, lat: 53.545, lng: 10.01, label: 'Premium 1', type: 'premium' }
];
updateVehicles(vehicles);
```

## API Reference

### `initMap(containerId, options)`
Initialize the map in a DOM element.

**Parameters:**
- `containerId` (string): ID of the container element (default: 'map')
- `options` (object): Map configuration
  - `center` (object): `{lat, lng}` (default: `{lat: 53.54, lng: 10}`)
  - `zoom` (number): Initial zoom level (default: 12)

**Example:**
```javascript
initMap('map-container', { 
  center: { lat: 40.7128, lng: -74.0060 }, 
  zoom: 13 
});
```

---

### `updateVehicles(vehicles)`
Add or update vehicles on the map.

**Parameters:**
- `vehicles` (array): Array of vehicle objects
  - `id` (string|number): Unique vehicle identifier
  - `lat` (number): Latitude
  - `lng` (number): Longitude
  - `label` (string): Display name for popup
  - `type` (string): Vehicle type ('taxi', 'premium', 'economy', etc.)

**Example:**
```javascript
updateVehicles([
  { id: 1, lat: 53.54, lng: 10, label: 'Taxi #1', type: 'taxi' }
]);
```

---

### `selectVehicle(vehicle)`
Manually select a vehicle and show its popup.

**Parameters:**
- `vehicle` (object): Vehicle object with id, lat, lng, label, type

**Example:**
```javascript
selectVehicle({ id: 1, lat: 53.54, lng: 10, label: 'Taxi #1', type: 'taxi' });
```

---

### `deselectVehicle()`
Close the current vehicle popup and deselect.

**Example:**
```javascript
deselectVehicle();
```

---

### `removeVehicle(vehicleId)`
Remove a specific vehicle from the map.

**Parameters:**
- `vehicleId` (string|number): ID of the vehicle to remove

**Example:**
```javascript
removeVehicle(1);
```

---

### `clearAllVehicles()`
Remove all vehicles from the map.

**Example:**
```javascript
clearAllVehicles();
```

---

### `panTo(lat, lng, zoom)`
Pan map to a specific location.

**Parameters:**
- `lat` (number): Latitude
- `lng` (number): Longitude
- `zoom` (number, optional): Zoom level

**Example:**
```javascript
panTo(40.7128, -74.0060, 14); // New York, zoom 14
```

---

### `fitToMarkers()`
Automatically zoom and pan to fit all markers on screen.

**Example:**
```javascript
fitToMarkers();
```

---

### `addCustomMarker(lat, lng, options)`
Add a custom marker (e.g., pickup/dropoff point).

**Parameters:**
- `lat` (number): Latitude
- `lng` (number): Longitude
- `options` (object, optional):
  - `label` (string): Marker label
  - `color` (string): Marker color (default: 'blue')

**Returns:** Marker object

**Example:**
```javascript
addCustomMarker(53.54, 10, { 
  label: 'Pickup Point', 
  color: 'green' 
});
```

---

### `getMapCenter()`
Get the current map center.

**Returns:** `{lat, lng}`

**Example:**
```javascript
const center = getMapCenter();
console.log(center); // {lat: 53.54, lng: 10}
```

---

### `getZoomLevel()`
Get the current zoom level.

**Returns:** number

**Example:**
```javascript
const zoom = getZoomLevel();
console.log(zoom); // 12
```

---

## Real-World Example

```html
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
</head>
<body>
  <div id="map" style="height: 600px;"></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="map.js"></script>
  <script>
    initMap('map');

    // Simulate real-time vehicle updates
    setInterval(() => {
      const vehicles = [
        {
          id: 1,
          lat: 53.54 + (Math.random() - 0.5) * 0.01,
          lng: 10 + (Math.random() - 0.5) * 0.01,
          label: 'Taxi #1',
          type: 'taxi'
        }
      ];
      updateVehicles(vehicles);
    }, 2000);
  </script>
</body>
</html>
```

## Advantages over Google Maps

| Feature | Leaflet | Google Maps |
|---------|---------|------------|
| **API Key Required** | ❌ No | ✅ Yes |
| **Cost** | Free | Paid after quota |
| **Library Size** | ~39KB | ~200KB+ |
| **Open Source** | ✅ Yes | ❌ No |
| **Privacy** | OpenStreetMap | Google |
| **Customization** | ✅ Highly customizable | Limited |

## Tile Providers

The current implementation uses **OpenStreetMap**. Other options:

- **CartoDB**: `https://{s}.basemaps.cartocdn.com/positron/{z}/{x}/{y}.png`
- **USGS Imagery**: `https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}`
- **Stamen TonerLite**: `https://tiles.stadiamaps.com/tiles/stamen_toner_lite/{z}/{x}/{y}.png`

To change the tile layer in `map.js`, modify:

```javascript
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors',
  maxZoom: 19
}).addTo(map);
```

## Testing

Open `map_example.html` in your browser to see a working demo with:
- Sample vehicle markers
- Click-to-select functionality
- Real-time update capability
- Control buttons

## Migration from Google Maps

If migrating from `map.jsx`:

1. Replace React component imports with `<script src="map.js"></script>`
2. Replace `<Map>` container with `<div id="map"></div>`
3. Call `initMap('map')` instead of rendering React component
4. Call `updateVehicles(vehicles)` instead of passing vehicles as props

## Notes

- Leaflet uses latitude/longitude format: `[lat, lng]` (note: reversed from GeoJSON)
- Map container must have a defined height
- Use real-time WebSocket or polling to update vehicle positions
- Popup content supports HTML

## License

Leaflet: BSD 2-Clause License  
OpenStreetMap: ODbL License
