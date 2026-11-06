import { clearSessionCookie, laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const result = await laravelRequest(event, '/logout-all', { method: 'POST' })
  clearSessionCookie(event)
  return result
})
