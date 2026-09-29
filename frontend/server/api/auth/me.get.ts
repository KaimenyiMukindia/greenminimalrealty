import { laravelRequest } from '../../utils/laravel'

export default defineEventHandler((event) => laravelRequest(event, '/me'))
