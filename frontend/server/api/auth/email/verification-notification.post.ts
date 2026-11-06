import { laravelRequest } from '../../../utils/laravel'

export default defineEventHandler(event => laravelRequest(event, '/email/verification-notification', { method: 'POST' }))
