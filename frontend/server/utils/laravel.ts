import type { H3Event } from 'h3'
import { FetchError } from 'ofetch'

export async function laravelRequest<T>(event: H3Event, path: string, options: Record<string, unknown> = {}): Promise<T> {
  return requestLaravel<T>(event, `/auth${path}`, options)
}

export async function laravelPublicRequest<T>(event: H3Event, path: string, options: Record<string, unknown> = {}): Promise<T> {
  return requestLaravel<T>(event, `/public${path}`, options)
}

export async function laravelAdminRequest<T>(event: H3Event, path: string, options: Record<string, unknown> = {}): Promise<T> {
  return requestLaravel<T>(event, `/admin${path}`, options)
}

async function requestLaravel<T>(event: H3Event, path: string, options: Record<string, unknown> = {}): Promise<T> {
  const config = useRuntimeConfig(event)
  const token = getCookie(event, config.public.sessionCookieName)
  const headers = new Headers(options.headers as HeadersInit | undefined)
  headers.set('Accept', 'application/json')
  const userAgent = getHeader(event, 'user-agent')
  if (userAgent) headers.set('User-Agent', userAgent)
  const trustedProxies = config.trustedProxyIps.split(',').map(ip => ip.trim()).filter(Boolean)
  let clientIp = getRequestIP(event)
  const forwarded = (getHeader(event, 'x-forwarded-for') || '').split(',').map(ip => ip.trim()).filter(Boolean)
  while (clientIp && trustedProxies.includes(clientIp) && forwarded.length) {
    clientIp = forwarded.pop()
  }
  if (clientIp) headers.set('X-Forwarded-For', clientIp)
  if (!(options.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  try {
    const response = await $fetch.raw<T>(`${config.laravelApiUrl.replace(/\/$/, '')}/api/v1${path}`, {
      ...options,
      headers,
      timeout: 15000,
    })
    if (!response.headers.get('content-type')?.includes('application/json') || response._data === undefined) {
      console.error('Laravel returned an invalid API response.', { endpoint: path.split('?')[0], status: response.status })
      throw createError({ statusCode: 502, message: 'The Laravel API returned an invalid response.' })
    }
    return response._data
  } catch (error: unknown) {
    if (!(error instanceof FetchError)) throw error
    const status = error.response?.status ?? 502
    const errorData: unknown = error.response?._data ?? error.data
    const data = errorData && typeof errorData === 'object' ? errorData : { message: 'The Laravel API is unavailable.', code: 'api_unavailable' }
    if (status === 401 && token) {
      deleteCookie(event, config.public.sessionCookieName, { httpOnly: true, secure: config.sessionCookieSecure, sameSite: 'lax', path: '/' })
    }
    if (status >= 500) console.error('Laravel API request failed.', { endpoint: path.split('?')[0], status })
    throw createError({ statusCode: status, statusMessage: 'message' in data && typeof data.message === 'string' ? data.message : 'Authentication request failed.', data })
  }
}

export function setSessionCookie(event: H3Event, token: string): void {
  const config = useRuntimeConfig(event)
  setCookie(event, config.public.sessionCookieName, token, {
    httpOnly: true,
    secure: config.sessionCookieSecure,
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 14,
    path: '/',
  })
}

export function clearSessionCookie(event: H3Event): void {
  const config = useRuntimeConfig(event)
  deleteCookie(event, config.public.sessionCookieName, { httpOnly: true, secure: config.sessionCookieSecure, sameSite: 'lax', path: '/' })
}
