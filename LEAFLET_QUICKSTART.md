# Leaflet Integration - Quick Start

✅ **Leaflet is now fully integrated into your application!**

## What's Ready

Your **taxi.html** page is now using Leaflet instead of Google Maps.

### Key Improvements:
- ✅ **No API Key Required** — Uses OpenStreetMap (free, public)
- ✅ **Faster Loading** — Lighter library (39KB vs 200KB+)
- ✅ **Same Functionality** — Click to set pickup/dropoff, geolocation, markers
- ✅ **Open Source** — Customizable and transparent

---

## How It Works

1. **User opens taxi.html**
   ↓
2. **Leaflet map loads** (with OpenStreetMap tiles)
   ↓
3. **Browser asks for location** (geolocation)
   ↓
4. **User clicks map for Pickup** (green marker placed)
   ↓
5. **Mode auto-switches to Dropoff**
   ↓
6. **User clicks map for Dropoff** (red marker placed)
   ↓
7. **User clicks "Continue"** → Form submits to `set_session.php`

---

## Files Involved

| File | Purpose |
|------|---------|
| `taxi.html` | Main ride selection page (uses map) |
| `taxi.js` | Handles map interactions & form submission |
| `map.js` | Leaflet utility functions (reusable) |
| `set_session.php` | Saves data to server session |
| `ride_map_options.php` | Next page (vehicle selection) |

---

## Testing Locally

1. Open in browser: `http://localhost/342/taxi.html`
2. Allow geolocation (bottom popup)
3. Click on map for pickup location
4. Click on map for dropoff location
5. Click "Continue" button

The map should work without any errors! ✅

---

## Map Features

### Markers
- **Green circle** = Pickup location
- **Red circle** = Dropoff location
- **Click any marker** for more info

### Controls
- **Zoom in/out** = Scroll wheel or +/- buttons
- **Pan map** = Click & drag
- **Get current location** = Geolocation button (bottom right)

### Display
- Uses OpenStreetMap tiles
- Dark mode optional (change tile URL)
- Responsive design (works on mobile)

---

## Customization

### Change Marker Colors

Edit `taxi.js`, find the marker creation code:

```javascript
// For pickup (around line 151)
pickupMarker = L.circleMarker([lat, lng], {
  fillColor: 'green',      // ← Change color
  color: 'darkgreen',      // ← Border color
  // ...
});

// For dropoff (around line 175)
dropoffMarker = L.circleMarker([lat, lng], {
  fillColor: 'red',        // ← Change color
  color: 'darkred',        // ← Border color
  // ...
});
```

Available colors: `red`, `blue`, `green`, `orange`, `purple`, `yellow`, etc.

---

### Change Map Tile Style

Edit `map.js`, find the tile layer (around line 33):

**Current (OpenStreetMap - light):**
```javascript
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
```

**Alternative options:**

CartoDB (minimal):
```javascript
L.tileLayer('https://{s}.basemaps.cartocdn.com/positron/{z}/{x}/{y}.png', {
```

Satellite imagery:
```javascript
L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
```

Stamen (monochrome):
```javascript
L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner_lite/{z}/{x}/{y}.png', {
```

---

## Using Map in Other Pages

Want to add a map to other pages? Easy!

### Step 1: Add to `<head>`
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="map.js"></script>
```

### Step 2: Add container
```html
<div id="my-map" style="height: 500px; width: 100%;"></div>
```

### Step 3: Initialize
```javascript
<script>
  initMap('my-map', { 
    center: {lat: 35.1264, lng: 33.4299}, 
    zoom: 16 
  });
</script>
```

---

## Available Functions (from map.js)

All these functions are available in any page using `map.js`:

```javascript
initMap(containerId, options)
updateVehicles(vehicles)
selectVehicle(vehicle)
deselectVehicle()
removeVehicle(vehicleId)
clearAllVehicles()
panTo(lat, lng, zoom)
fitToMarkers()
addCustomMarker(lat, lng, options)
getMapCenter()
getZoomLevel()
```

See `LEAFLET_MAP_README.md` for detailed docs on each function.

---

## Troubleshooting

### Map doesn't show
- Check browser console (F12) for errors
- Ensure map container has a height: `style="height: 500px;"`
- Wait 2 seconds for Leaflet to load from CDN

### Markers not appearing
- Make sure `map.js` is loaded before your code
- Check that coordinates are valid (lat: -90 to 90, lng: -180 to 180)

### Geolocation not working
- Must be on HTTPS (or localhost for testing)
- User must allow location permission in browser

### Slow loading
- Leaflet CDN should be fast (cached globally)
- If slow, consider hosting Leaflet files locally

---

## Browser Support

Leaflet works on:
- ✅ Chrome/Edge (all versions)
- ✅ Firefox (all versions)
- ✅ Safari (all versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ IE 11 (with polyfills)

---

## Resources

- **Leaflet Docs**: https://leafletjs.com/
- **OpenStreetMap**: https://www.openstreetmap.org/
- **Tile Providers**: https://leaflet-extras.github.io/leaflet-providers/
- **Your Docs**: See `LEAFLET_MAP_README.md` in this folder

---

## Summary

✅ Leaflet is installed and working in `taxi.html`  
✅ No API keys needed  
✅ Same functionality as Google Maps  
✅ Fully customizable  
✅ Ready for production  

**You're all set! 🚀**
