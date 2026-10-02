import { laravelAdminRequest } from '../../../utils/laravel'

export default defineEventHandler(async (event) => laravelAdminRequest(event, `/${getRouterParam(event, 'resource')}/${getRouterParam(event, 'id')}`, { method: 'DELETE' }))