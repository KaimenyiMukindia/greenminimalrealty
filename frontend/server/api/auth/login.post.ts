import { laravelRequest, setSessionCookie } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const result = await laravelRequest<{ data: { access_token: string } }>(event, '/login', { method: 'POST', body })
  if (result.data?.access_token) setSessionCookie(event, result.data.access_token)
  const { access_token: _accessToken, ...safeData } = result.data
  return { ...result, data: safeData }
})
