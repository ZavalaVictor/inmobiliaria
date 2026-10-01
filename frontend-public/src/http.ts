const apiUrl = (import.meta.env.VITE_API_URL?.trim() || '/api').replace(/\/+$/, '')
let csrfRequest: Promise<void> | null = null

export class PublicApiError extends Error {
  readonly status: number
  readonly payload: unknown

  constructor(status: number, payload: unknown) {
    super(getPayloadMessage(payload) || `La solicitud falló (${status}).`)
    this.name = 'PublicApiError'
    this.status = status
    this.payload = payload
  }
}

function getPayloadMessage(payload: unknown): string | null {
  if (!payload || typeof payload !== 'object' || !('message' in payload)) return null
  return typeof payload.message === 'string' ? payload.message : null
}

function getCookie(name: string): string | null {
  const cookie = document.cookie.split('; ').find((part) => part.startsWith(`${name}=`))
  return cookie ? decodeURIComponent(cookie.slice(name.length + 1)) : null
}

async function readPayload(response: Response): Promise<unknown> {
  const text = await response.text()
  if (!text) return null
  try { return JSON.parse(text) as unknown } catch { return { message: text } }
}

async function ensureCsrfCookie(): Promise<void> {
  if (!csrfRequest) {
    csrfRequest = fetch('/sanctum/csrf-cookie', { credentials: 'include', headers: { Accept: 'application/json' } })
      .then(async (response) => { if (!response.ok) throw new PublicApiError(response.status, await readPayload(response)) })
      .finally(() => { csrfRequest = null })
  }
  await csrfRequest
}

export async function publicApiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const method = options.method?.toUpperCase() ?? 'GET'
  if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) await ensureCsrfCookie()

  const xsrfToken = getCookie('XSRF-TOKEN')
  const response = await fetch(`${apiUrl}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      ...options.headers,
    },
  })
  const payload = await readPayload(response)
  if (!response.ok) throw new PublicApiError(response.status, payload)
  return payload as T
}
