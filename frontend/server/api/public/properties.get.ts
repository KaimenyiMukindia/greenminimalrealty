import { laravelPublicRequest } from '../../utils/laravel'
import { rewriteAssetUrls } from '../../utils/asset-urls'

export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const search = new URLSearchParams()

  for (const [key, value] of Object.entries(query)) {
    const parameter = key === 'tags' || key === 'tags[]' ? 'tags[]' : key
    if (Array.isArray(value)) value.forEach(item => search.append(parameter, String(item)))
    else if (value !== undefined) search.set(parameter, String(value))
  }

  const result = await laravelPublicRequest(event, `/properties${search.toString() ? `?${search}` : ''}`)
  return rewriteAssetUrls(event, result)
})
