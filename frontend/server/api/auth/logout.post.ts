import { clearSessionCookie, laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const result = await laravelRequest(event, '/logout', { method: 'POST' })
  clearSessionCookie(event)
  return result
})
