const accessToken = String(import.meta.env.VITE_MAPBOX_ACCESS_TOKEN || '')

export const requireMapboxToken = () => {
  if (!accessToken) throw new Error('Mapbox is not configured. Set VITE_MAPBOX_ACCESS_TOKEN.')
  return accessToken
}

export const mapboxTileUrl = () =>
  `https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/512/{z}/{x}/{y}@2x?access_token=${encodeURIComponent(requireMapboxToken())}`

export const mapboxAttribution =
  '&copy; <a href="https://www.mapbox.com/about/maps/">Mapbox</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'

export const fetchMapboxRoadRoute = async (
  start: [number, number],
  end: [number, number],
): Promise<[number, number][]> => {
  const coordinates = `${start[1]},${start[0]};${end[1]},${end[0]}`
  const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${coordinates}?overview=full&geometries=geojson&steps=false&access_token=${encodeURIComponent(requireMapboxToken())}`
  const response = await fetch(url)
  if (!response.ok) throw new Error('Mapbox road route unavailable.')
  const geometry = (await response.json())?.routes?.[0]?.geometry?.coordinates || []
  return geometry.map((point: number[]) => [Number(point[1]), Number(point[0])] as [number, number])
}

export const reverseGeocodeMapbox = async (latitude: number, longitude: number): Promise<string> => {
  const url = `https://api.mapbox.com/search/geocode/v6/reverse?longitude=${longitude}&latitude=${latitude}&language=en&country=PH&access_token=${encodeURIComponent(requireMapboxToken())}`
  const response = await fetch(url)
  if (!response.ok) throw new Error('Unable to identify the current address.')
  const feature = (await response.json())?.features?.[0]
  return String(feature?.properties?.full_address || feature?.place_name || feature?.properties?.name || '')
}

export const forwardGeocodeMapbox = async (query: string): Promise<{ latitude: number; longitude: number; address: string } | null> => {
  const url = `https://api.mapbox.com/search/geocode/v6/forward?q=${encodeURIComponent(query)}&limit=1&country=PH&language=en&access_token=${encodeURIComponent(requireMapboxToken())}`
  const response = await fetch(url)
  if (!response.ok) throw new Error('Unable to find that address.')
  const feature = (await response.json())?.features?.[0]
  const coordinates = feature?.geometry?.coordinates
  if (!Array.isArray(coordinates) || coordinates.length < 2) return null
  return {
    latitude: Number(coordinates[1]),
    longitude: Number(coordinates[0]),
    address: String(feature?.properties?.full_address || feature?.place_name || feature?.properties?.name || query),
  }
}
