import { useEffect, useRef } from 'react'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const LABEL_ZOOM = 13

export interface PropertyMapPoint {
  id: number
  latitud: number
  longitud: number
  label: string
  price: string
  imageUrl: string | null
  detailUrl: string
}

interface PropertyMapProps {
  points: PropertyMapPoint[]
  selectedPointId: number | null
  onPointSelect: (id: number) => void
}

function escapeHtml(value: string): string {
  return value.replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;',
  })[character] ?? character)
}

function createPopupContent(point: PropertyMapPoint): string {
  const image = point.imageUrl
    ? `<img alt="" class="property-map-popup-image" src="${escapeHtml(point.imageUrl)}" />`
    : ''

  return `<div class="property-map-popup-content">${image}<p class="property-map-popup-code">Propiedad disponible</p><strong class="property-map-popup-title">${escapeHtml(point.label)}</strong><p class="property-map-popup-price">${escapeHtml(point.price)}</p><a class="property-map-popup-link" href="${escapeHtml(point.detailUrl)}">Ver más</a></div>`
}

function createLabelIcon(point: PropertyMapPoint, selected: boolean): L.DivIcon {
  return L.divIcon({
    className: `property-map-label-marker${selected ? ' property-map-label-marker-selected' : ''}`,
    html: `<span class="property-map-label-card"><strong>${escapeHtml(point.label)}</strong><span>${escapeHtml(point.price)}</span></span>`,
    iconAnchor: [105, 90],
    iconSize: [210, 90],
  })
}

function createPointMarker(point: PropertyMapPoint, showLabels: boolean, selected: boolean): L.Marker | L.CircleMarker {
  if (showLabels) return L.marker([point.latitud, point.longitud], { icon: createLabelIcon(point, selected), keyboard: true, title: point.label })

  return L.circleMarker([point.latitud, point.longitud], {
    color: selected ? '#fff' : '#a27c43',
    fillColor: selected ? '#d2ae71' : '#a27c43',
    fillOpacity: selected ? 1 : .9,
    radius: selected ? 10 : 7,
    weight: selected ? 3 : 2,
  })
}

export function PropertyMap({ points, selectedPointId, onPointSelect }: PropertyMapProps): React.JSX.Element {
  const containerRef = useRef<HTMLDivElement | null>(null)
  const mapRef = useRef<L.Map | null>(null)
  const layerRef = useRef<L.LayerGroup | null>(null)
  const markersRef = useRef<Map<number, L.Marker | L.CircleMarker>>(new Map())
  const renderMarkersRef = useRef<() => void>(() => undefined)
  const onPointSelectRef = useRef(onPointSelect)

  useEffect(() => {
    onPointSelectRef.current = onPointSelect
  }, [onPointSelect])

  useEffect(() => {
    if (!containerRef.current || mapRef.current) return undefined

    const map = L.map(containerRef.current, {
      center: [23.6345, -102.5528],
      zoom: 5,
      minZoom: 3,
      zoomControl: true,
    })

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map)

    mapRef.current = map
    layerRef.current = L.layerGroup().addTo(map)

    const resizeObserver = new ResizeObserver(() => map.invalidateSize())
    resizeObserver.observe(containerRef.current)
    const onZoomEnd = (): void => renderMarkersRef.current()
    map.on('zoomend', onZoomEnd)

    return () => {
      resizeObserver.disconnect()
      map.off('zoomend', onZoomEnd)
      map.remove()
      mapRef.current = null
      layerRef.current = null
      markersRef.current.clear()
    }
  }, [])

  useEffect(() => {
    const map = mapRef.current
    const layer = layerRef.current
    if (!map || !layer) return

    const renderMarkers = (): void => {
      layer.clearLayers()
      markersRef.current.clear()

      const showLabels = map.getZoom() >= LABEL_ZOOM

      for (const point of points) {
        const marker = createPointMarker(point, showLabels, point.id === selectedPointId)
        marker.bindPopup(createPopupContent(point), {
          className: 'property-map-popup',
          maxWidth: 260,
          minWidth: 230,
        })

        if (!showLabels) {
          marker.bindTooltip(point.label, {
            direction: 'top',
            offset: [0, -8],
            opacity: .95,
          })
        }

        marker.on('click', () => {
          onPointSelectRef.current(point.id)
          marker.openPopup()
        })
        marker.addTo(layer)
        markersRef.current.set(point.id, marker)
      }
    }

    renderMarkersRef.current = renderMarkers
    renderMarkers()

    if (points.length > 1) {
      const bounds = L.latLngBounds(points.map((point) => [point.latitud, point.longitud] as [number, number]))
      map.fitBounds(bounds, { padding: [48, 48], maxZoom: 12 })
    } else if (points.length === 1) {
      map.setView([points[0].latitud, points[0].longitud], Math.max(map.getZoom(), 10))
    } else {
      map.setView([23.6345, -102.5528], 5)
    }
  }, [points, selectedPointId])

  useEffect(() => {
    const map = mapRef.current
    if (!map || selectedPointId === null) return

    const point = points.find((item) => item.id === selectedPointId)
    if (!point) return

    map.flyTo([point.latitud, point.longitud], Math.max(map.getZoom(), 14), {
      duration: 1.15,
      easeLinearity: .25,
    })

    const openSelectedPopup = (): void => {
      markersRef.current.get(point.id)?.openPopup()
    }
    map.once('moveend', openSelectedPopup)

    return () => {
      map.off('moveend', openSelectedPopup)
    }
  }, [points, selectedPointId])

  return <div aria-label="Mapa interactivo de propiedades" className="property-map" ref={containerRef} role="application" />
}
