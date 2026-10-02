import { laravelAdminRequest } from '../../utils/laravel'
import { rewriteAssetUrls } from '../../utils/asset-urls'

export default defineEventHandler(async (event) => {
  const query = getRequestURL(event).search
  const result = await laravelAdminRequest(event, `/media${query}`)
  return rewriteAssetUrls(event, result)
})
