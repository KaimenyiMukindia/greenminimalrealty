import { laravelPublicRequest } from '../../utils/laravel'
import { rewriteAssetUrls } from '../../utils/asset-urls'

export default defineEventHandler((event) => {
  const resource = getRouterParam(event, 'resource')
  const query = getQuery(event)
  const search = new URLSearchParams()
  for (const [key, value] of Object.entries(query)) {
    if (Array.isArray(value)) value.forEach(item => search.append(key, String(item)))
    else if (value !== undefined) search.set(key, String(value))
  }
  return laravelPublicRequest(event, `/${resource}${search.toString() ? `?${search}` : ''}`).then(result => rewriteAssetUrls(event, result))
})
