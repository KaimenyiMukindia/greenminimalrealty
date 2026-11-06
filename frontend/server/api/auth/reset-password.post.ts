import { clearSessionCookie, laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const result = await laravelRequest(event, '/reset-password', { method: 'POST', body })
  clearSessionCookie(event)
  return result
})
