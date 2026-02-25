// MapSection.jsx
import { useState } from "react";
import {
  APIProvider,
  Map,
  AdvancedMarker,
  Pin,
  InfoWindow,
} from "@vis.gl/react-google-maps";

const center = { lat: 53.54, lng: 10 };

export default function MapSection({ vehicles }) {
  const [selected, setSelected] = useState(null);

  return (
    <APIProvider apiKey={process.env.REACT_APP_GOOGLE_MAPS_API_KEY}>
      <div style={{ height: "500px", width: "100%" }}>
        <Map defaultZoom={12} defaultCenter={center}>
          {vehicles.map((v) => (
            <AdvancedMarker
              key={v.id}
              position={{ lat: v.lat, lng: v.lng }}
              onClick={() => setSelected(v)}
            >
              <Pin
                background={v.type === "taxi" ? "yellow" : "purple"}
                borderColor={"black"}
                glyphColor={"black"}
              />
            </AdvancedMarker>
          ))}

          {selected && (
            <InfoWindow
              position={{ lat: selected.lat, lng: selected.lng }}
              onCloseClick={() => setSelected(null)}
            >
              <div>
                <strong>{selected.label}</strong>
                <br />
                Type: {selected.type}
              </div>
            </InfoWindow>
          )}
        </Map>
      </div>
    </APIProvider>
  );
}
