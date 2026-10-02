import { laravelPublicRequest } from '../../../utils/laravel'

export default defineEventHandler((event) => {
  const query = getQuery(event)
  const search = new URLSearchParams()
  if (typeof query.q === 'string') search.set('q', query.q)
  return laravelPublicRequest(event, `/properties/suggestions${search.size ? `?${search}` : ''}`)
})
