import { useEffect, useRef } from 'react'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

interface PropertyLocationMapProps {
  latitud: string
  longitud: string
  onChange: (latitud: string, longitud: string) => void
}

const DEFAULT_CENTER: L.LatLngExpression = [23.6345, -102.5528]

function coordinatesFromValues(latitud: string, longitud: string): L.LatLngExpression | null {
  const latitude = Number(latitud)
  const longitude = Number(longitud)
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null
  if (latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return null
  return [latitude, longitude]
}

function markerIcon(): L.DivIcon {
  return L.divIcon({ className: 'admin-map-marker', html: '<span></span>', iconSize: [28, 36], iconAnchor: [14, 36] })
}

export function PropertyLocationMap({ latitud, longitud, onChange }: PropertyLocationMapProps): React.JSX.Element {
  const containerRef = useRef<HTMLDivElement>(null)
  const mapRef = useRef<L.Map | null>(null)
  const markerRef = useRef<L.Marker | null>(null)
  const initialCoordinatesRef = useRef<L.LatLngExpression | null>(coordinatesFromValues(latitud, longitud))
  const onChangeRef = useRef(onChange)

  useEffect(() => {
    onChangeRef.current = onChange
  }, [onChange])

  useEffect(() => {
    const container = containerRef.current
    if (!container) return undefined

    const initialCoordinates = initialCoordinatesRef.current
    const map = L.map(container, { center: initialCoordinates ?? DEFAULT_CENTER, zoom: initialCoordinates ? 15 : 5, zoomControl: true })
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map)

    const setMarker = (coordinates: L.LatLngExpression, center = false): void => {
      const latLng = L.latLng(coordinates)
      if (!markerRef.current) {
        markerRef.current = L.marker(latLng, { draggable: true, icon: markerIcon() }).addTo(map)
        markerRef.current.on('dragend', () => {
          const position = markerRef.current?.getLatLng()
          if (position) onChangeRef.current(position.lat.toFixed(7), position.lng.toFixed(7))
        })
      } else {
        markerRef.current.setLatLng(latLng)
      }

      if (center) map.setView(latLng, Math.max(map.getZoom(), 15), { animate: true })
    }

    map.on('click', (event) => {
      setMarker(event.latlng, false)
      onChangeRef.current(event.latlng.lat.toFixed(7), event.latlng.lng.toFixed(7))
    })

    if (initialCoordinates) setMarker(initialCoordinates)

    mapRef.current = map
    const resizeObserver = new ResizeObserver(() => map.invalidateSize())
    resizeObserver.observe(container)
    window.setTimeout(() => map.invalidateSize(), 0)

    return () => {
      resizeObserver.disconnect()
      markerRef.current = null
      map.remove()
      mapRef.current = null
    }
  }, [])

  useEffect(() => {
    const map = mapRef.current
    const coordinates = coordinatesFromValues(latitud, longitud)
    if (!map) return
    if (!coordinates) {
      markerRef.current?.removeFrom(map)
      markerRef.current = null
      return
    }
    if (!markerRef.current) return

    markerRef.current.setLatLng(coordinates)
  }, [latitud, longitud])

  const hasCoordinates = coordinatesFromValues(latitud, longitud) !== null

  return <div className="space-y-3"><div className="admin-location-map" ref={containerRef} /><p className="text-xs leading-5 text-[var(--app-text-muted)]">{hasCoordinates ? 'Arrastra el marcador para ajustar la ubicación. La ubicación pública se mostrará de forma aproximada.' : 'Haz clic en el mapa para colocar el marcador. También puedes arrastrarlo después.'}</p></div>
}
