import { laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const result = await laravelRequest(event, '/register', { method: 'POST', body })
  setResponseStatus(event, 201)
  return result
})
