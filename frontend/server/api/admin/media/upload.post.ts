import { laravelAdminRequest } from '../../../utils/laravel'
import { rewriteAssetUrls } from '../../../utils/asset-urls'

export default defineEventHandler(async (event) => {
  const form = await readMultipartFormData(event)
  const body = new FormData()
  for (const part of form || []) if (part.name) body.append(part.name, part.data, part.filename)
  return rewriteAssetUrls(event, await laravelAdminRequest(event, '/media', { method: 'POST', body }))
})