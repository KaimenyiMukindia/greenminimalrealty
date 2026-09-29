import { laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  return laravelRequest(event, '/register', { method: 'POST', body })
})
