import { laravelRequest } from '../../utils/laravel'

export default defineEventHandler(async (event) => laravelRequest(event, '/me', { method: 'PATCH', body: await readBody(event) }))
