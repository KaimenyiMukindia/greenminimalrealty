import { laravelPublicRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => {
  return laravelPublicRequest(event, '/contact', { method: 'POST', body: await readBody(event) })
})