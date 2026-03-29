import { useEffect, useRef, useState } from 'react';

// Fix Leaflet default marker icons (Vite build issue)
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl:       'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl:     'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

// Custom SVG icons
const userIcon = L.divIcon({
  className: '',
  html: `<div class="map-user-pin">
    <svg viewBox="0 0 24 24" fill="none" width="22" height="22">
      <circle cx="12" cy="12" r="10" fill="#2563eb" stroke="white" stroke-width="2"/>
      <circle cx="12" cy="12" r="4" fill="white"/>
    </svg>
    <div class="map-pin-pulse"></div>
  </div>`,
  iconSize: [36, 36],
  iconAnchor: [18, 18],
  popupAnchor: [0, -20],
});

const centerIcon = L.divIcon({
  className: '',
  html: `<div class="map-center-pin">
    <svg viewBox="0 0 32 40" fill="none" width="32" height="40">
      <path d="M16 0C7.164 0 0 7.164 0 16c0 10 16 24 16 24S32 26 32 16C32 7.164 24.836 0 16 0z" fill="#ef4444"/>
      <circle cx="16" cy="16" r="7" fill="white"/>
      <path d="M16 11v10M11 16h10" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
    </svg>
  </div>`,
  iconSize: [32, 40],
  iconAnchor: [16, 40],
  popupAnchor: [0, -42],
});

// Haversine distance calculation
function calcDistance(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) ** 2;
  return (R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))).toFixed(2);
}

export default function MapModal({ center, onClose }) {
  const mapRef = useRef(null);
  const mapInstance = useRef(null);
  const lineRef = useRef(null);
  const userMarkerRef = useRef(null);

  const [gpsStatus, setGpsStatus] = useState('loading'); // loading | success | denied | error
  const [userCoords, setUserCoords] = useState(null);
  const [distance, setDistance] = useState(null);
  const [eta, setEta] = useState(null);

  // Initialize map once
  useEffect(() => {
    if (mapInstance.current) return;
    mapInstance.current = L.map(mapRef.current, {
      center: [center.coords.lat, center.coords.lng],
      zoom: 14,
      zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(mapInstance.current);

    // Place the medical center marker
    L.marker([center.coords.lat, center.coords.lng], { icon: centerIcon })
      .addTo(mapInstance.current)
      .bindPopup(
        `<div style="font-family:Inter,sans-serif;min-width:160px">
          <strong style="font-size:0.9rem;color:#111">${center.name}</strong><br/>
          <span style="font-size:0.78rem;color:#555">${center.type}</span><br/>
          <span style="font-size:0.78rem;color:#888">${center.address}</span>
        </div>`,
        { maxWidth: 220 }
      )
      .openPopup();

    // Get user GPS
    if (!navigator.geolocation) {
      setGpsStatus('error');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        setUserCoords({ lat: latitude, lng: longitude });
        setGpsStatus('success');

        // Add user marker
        userMarkerRef.current = L.marker([latitude, longitude], { icon: userIcon })
          .addTo(mapInstance.current)
          .bindPopup(
            `<div style="font-family:Inter,sans-serif">
              <strong style="color:#2563eb">📍 Your Location</strong>
            </div>`
          );

        // Draw dashed line between user and center
        lineRef.current = L.polyline(
          [[latitude, longitude], [center.coords.lat, center.coords.lng]],
          { color: '#2563eb', weight: 3, dashArray: '8 6', opacity: 0.7 }
        ).addTo(mapInstance.current);

        // Fit both markers in view
        const bounds = L.latLngBounds(
          [latitude, longitude],
          [center.coords.lat, center.coords.lng]
        );
        mapInstance.current.fitBounds(bounds, { padding: [60, 60] });

        // Calculate distance & ETA
        const km = calcDistance(latitude, longitude, center.coords.lat, center.coords.lng);
        setDistance(km);
        const minutes = Math.round((km / 30) * 60); // ~30 km/h city speed
        setEta(minutes < 60 ? `~${minutes} min` : `~${(minutes / 60).toFixed(1)} hr`);
      },
      (err) => {
        setGpsStatus(err.code === 1 ? 'denied' : 'error');
        // Still show center on map
        mapInstance.current.setView([center.coords.lat, center.coords.lng], 15);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );

    return () => {
      if (mapInstance.current) {
        mapInstance.current.remove();
        mapInstance.current = null;
      }
    };
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // Close on Escape key
  useEffect(() => {
    const handler = (e) => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [onClose]);

  const googleMapsUrl = userCoords
    ? `https://www.google.com/maps/dir/?api=1&origin=${userCoords.lat},${userCoords.lng}&destination=${center.coords.lat},${center.coords.lng}&travelmode=driving`
    : `https://www.google.com/maps/search/?api=1&query=${center.coords.lat},${center.coords.lng}`;

  return (
    <div className="map-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
      <div className="map-modal">
        {/* Header */}
        <div className="map-modal-header">
          <div className="map-modal-title">
            <div className="map-header-icon">
              <svg viewBox="0 0 32 32" fill="none" width="28" height="28">
                <rect width="32" height="32" rx="8" fill={center.tag === 'govt' ? '#dbeafe' : '#ede9fe'} />
                <path d="M16 7v18M7 16h18" stroke={center.tag === 'govt' ? '#2563eb' : '#7c3aed'}
                  strokeWidth="2.5" strokeLinecap="round" />
              </svg>
            </div>
            <div>
              <h2 className="map-modal-name">{center.name}</h2>
              <p className="map-modal-addr">📍 {center.area} · {center.address}</p>
            </div>
          </div>
          <button className="map-close-btn" onClick={onClose} aria-label="Close map">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
              <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
            </svg>
          </button>
        </div>

        {/* Info Bar */}
        <div className="map-info-bar">
          {/* GPS Status */}
          <div className={`map-gps-chip ${gpsStatus === 'success' ? 'gps-ok' : gpsStatus === 'loading' ? 'gps-loading' : 'gps-error'}`}>
            {gpsStatus === 'loading' && <><span className="map-spinner"></span> Getting your location…</>}
            {gpsStatus === 'success' && <><span className="gps-dot"></span> Live GPS Active</>}
            {gpsStatus === 'denied' && <>⚠️ Location access denied</>}
            {gpsStatus === 'error'  && <>⚠️ Location unavailable</>}
          </div>

          {/* Distance */}
          {distance && (
            <div className="map-distance-chip">
              <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
                <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd"/>
              </svg>
              <strong>{distance} km</strong> from you · {eta} by car
            </div>
          )}

          {/* Hours & Phone */}
          <div className="map-hours-chip">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd"/>
            </svg>
            {center.hours}
          </div>
        </div>

        {/* Legend */}
        <div className="map-legend">
          <span className="legend-item">
            <span className="legend-dot legend-user"></span> Your Location
          </span>
          <span className="legend-item">
            <span className="legend-dot legend-center"></span> {center.name}
          </span>
          {userCoords && <span className="legend-item legend-line">— — Route Line</span>}
        </div>

        {/* Map Container */}
        <div ref={mapRef} className="map-container" />

        {/* Footer Actions */}
        <div className="map-modal-footer">
          <a href={googleMapsUrl} target="_blank" rel="noopener noreferrer" className="map-btn-navigate">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
              <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd"/>
            </svg>
            {userCoords ? 'Open Directions in Google Maps' : 'Open in Google Maps'}
          </a>
          <a href={`tel:${center.phone}`} className="map-btn-call-footer">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
              <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
            </svg>
            {center.phone}
          </a>
        </div>
      </div>
    </div>
  );
}
