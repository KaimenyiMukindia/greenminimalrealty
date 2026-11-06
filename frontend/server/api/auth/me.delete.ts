import { clearSessionCookie, laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const result = await laravelRequest(event, '/me', { method: 'DELETE', body: await readBody(event) })
  clearSessionCookie(event)
  return result
})
