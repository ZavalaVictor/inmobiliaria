const configuredApiUrl = import.meta.env.VITE_API_URL?.trim() || '/api'
const apiUrl = (configuredApiUrl.startsWith('/') ? configuredApiUrl : '/api').replace(/\/+$/, '')
let csrfRequest: Promise<void> | null = null
let unauthorizedHandler: (() => void) | null = null

export class ApiError extends Error {
  readonly status: number
  readonly payload: unknown

  constructor(status: number, payload: unknown) {
    super(getPayloadMessage(payload) || `La solicitud falló (${status}).`)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

interface ApiRequestOptions extends Omit<RequestInit, 'body' | 'headers'> {
  body?: unknown
  headers?: Record<string, string>
  handleUnauthorized?: boolean
}

function getPayloadMessage(payload: unknown): string | null {
  if (!payload || typeof payload !== 'object' || !('message' in payload)) {
    return null
  }

  const message = payload.message
  return typeof message === 'string' ? message : null
}

function getCookie(name: string): string | null {
  const cookie = document.cookie
    .split('; ')
    .find((part) => part.startsWith(`${name}=`))

  return cookie ? decodeURIComponent(cookie.slice(name.length + 1)) : null
}

async function ensureCsrfCookie(): Promise<void> {
  if (!csrfRequest) {
    csrfRequest = fetch('/sanctum/csrf-cookie', {
      credentials: 'include',
      headers: { Accept: 'application/json' },
    }).then(async (response) => {
      if (!response.ok) {
        throw new ApiError(response.status, await readPayload(response))
      }
    }).finally(() => {
      csrfRequest = null
    })
  }

  await csrfRequest
}

async function readPayload(response: Response): Promise<unknown> {
  const text = await response.text()

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text) as unknown
  } catch {
    return { message: text }
  }
}

export function setUnauthorizedHandler(handler: (() => void) | null): void {
  unauthorizedHandler = handler
}

export async function apiRequest<T>(path: string, options: ApiRequestOptions = {}): Promise<T> {
  const method = options.method?.toUpperCase() ?? 'GET'
  const isMutating = !['GET', 'HEAD', 'OPTIONS'].includes(method)

  if (isMutating) {
    await ensureCsrfCookie()
  }

  const isFormData = options.body instanceof FormData
  const body: BodyInit | undefined = options.body === undefined
    ? undefined
    : options.body instanceof FormData
      ? options.body
      : JSON.stringify(options.body)
  const xsrfToken = getCookie('XSRF-TOKEN')
  const response = await fetch(`${apiUrl}${path}`, {
    ...options,
    body,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      ...options.headers,
    },
  })

  const payload = await readPayload(response)

  if (!response.ok) {
    if (response.status === 401 && options.handleUnauthorized !== false) {
      unauthorizedHandler?.()
    }

    throw new ApiError(response.status, payload)
  }

  return payload as T
}

export function getValidationErrors(payload: unknown): Record<string, string[]> {
  if (
    !payload ||
    typeof payload !== 'object' ||
    !('errors' in payload) ||
    !payload.errors ||
    typeof payload.errors !== 'object'
  ) {
    return {}
  }

  return Object.fromEntries(
    Object.entries(payload.errors).map(([field, messages]) => [
      field,
      Array.isArray(messages)
        ? messages.filter((message): message is string => typeof message === 'string')
        : [],
    ]),
  )
}
