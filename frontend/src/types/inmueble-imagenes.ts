export interface InmuebleImagen {
  id: number
  inmueble_id: number
  url_publica: string | null
  nombre_original: string | null
  mime_type: string | null
  tamano_bytes: number | null
  es_principal: boolean
  orden: number
  texto_alternativo: string | null
  created_at: string | null
  updated_at: string | null
}

export interface InmuebleImagenUpdatePayload {
  texto_alternativo?: string | null
}

export interface ReordenarInmuebleImagenesPayload {
  imagenes: number[]
}
