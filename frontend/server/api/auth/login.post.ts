import { laravelRequest, setSessionCookie } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const result = await laravelRequest<{ data: { access_token: string } }>(event, '/login', { method: 'POST', body })
  if (!result.data || typeof result.data.access_token !== 'string' || !result.data.access_token) {
    throw createError({ statusCode: 502, message: 'The Laravel API returned an invalid authentication response.' })
  }
  setSessionCookie(event, result.data.access_token)
  const { access_token: _accessToken, ...safeData } = result.data
  return { ...result, data: safeData }
})
