import { useEffect, useRef } from "react";

export default function SalonMap({ salons }) {
  const mapRef = useRef(null);

  useEffect(() => {
    // If Google Maps already loaded, just init
    if (window.google) {
      initMap();
      return;
    }

    // Create script tag
    const script = document.createElement("script");
    script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyBr6gxB7-rwSZ7YgoDUZgTvRhUyOowWEEI`;
    script.async = true;
    script.defer = true;
    script.onload = initMap;
    document.head.appendChild(script);

    function initMap() {
      const map = new window.google.maps.Map(mapRef.current, {
        zoom: 8,
        center: { lat: 6.9271, lng: 79.8612 }, // Colombo
      });

      const geocoder = new window.google.maps.Geocoder();

      salons.forEach((salon) => {
        geocoder.geocode({ address: salon.address }, (results, status) => {
          if (status === "OK") {
            new window.google.maps.Marker({
              map,
              position: results[0].geometry.location,
              title: salon.name,
            });
          } else {
            console.error("Geocode failed:", salon.name, status);
          }
        });
      });
    }

    return () => {
      // Optional: remove script on unmount
      document.head.removeChild(script);
    };
  }, [salons]);

  return <div ref={mapRef} style={{ height: "500px", width: "100%" }} />;
}
