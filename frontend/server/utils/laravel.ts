import type { H3Event } from 'h3'

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
  if (!(options.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  try {
    return await $fetch<T>(`${config.laravelApiUrl.replace(/\/$/, '')}/api/v1${path}`, {
      ...options,
      headers,
    })
  } catch (error: any) {
    const response = error?.response
    const status = response?.status ?? error?.statusCode ?? 502
    const data = response?._data ?? error?.data ?? { message: 'The Laravel API is unavailable.', code: 'api_unavailable' }
    if (status === 401 && token) {
      deleteCookie(event, config.public.sessionCookieName, { httpOnly: true, secure: config.sessionCookieSecure, sameSite: 'lax', path: '/' })
    }
    throw createError({ statusCode: status, statusMessage: data.message ?? 'Authentication request failed.', data })
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
