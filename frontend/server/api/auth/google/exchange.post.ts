import { laravelRequest, setSessionCookie } from '../../../utils/laravel'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const body = await readBody<{ code?: unknown }>(event)
  const cookieName = `${config.public.sessionCookieName}_oauth`
  const verifier = getCookie(event, cookieName)
  setHeader(event, 'Cache-Control', 'no-store')
  try {
    const result = await laravelRequest<{ data: { access_token: string; token_type: string } }>(event, '/google/exchange', {
      method: 'POST', body: { code: body?.code, verifier },
    })
    if (!result.data || typeof result.data.access_token !== 'string' || !result.data.access_token) {
      throw createError({ statusCode: 502, message: 'The Laravel API returned an invalid authentication response.' })
    }
    setSessionCookie(event, result.data.access_token)
    const { access_token: _token, token_type: _type, ...data } = result.data
    return { data }
  } finally {
    deleteCookie(event, cookieName, { httpOnly: true, secure: config.sessionCookieSecure, sameSite: 'lax', path: '/' })
  }
})
