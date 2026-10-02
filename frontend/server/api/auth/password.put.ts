import { laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => laravelRequest(event, '/me/password', { method: 'PUT', body: await readBody(event) }))
