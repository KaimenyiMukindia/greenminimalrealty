import { laravelAdminRequest } from '../../../utils/laravel'

export default defineEventHandler(async (event) => laravelAdminRequest(event, `/${getRouterParam(event, 'resource')}/reorder`, { method: 'POST', body: await readBody(event) }))