import { clearSessionCookie, laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  try {
    return await laravelRequest(event, '/logout', { method: 'POST' })
  } finally {
    clearSessionCookie(event)
  }
})
