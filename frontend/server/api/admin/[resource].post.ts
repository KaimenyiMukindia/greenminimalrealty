import { laravelAdminRequest } from '../../utils/laravel'
import { rewriteAssetUrls } from '../../utils/asset-urls'

export default defineEventHandler(async (event) => rewriteAssetUrls(event, await laravelAdminRequest(event, `/${getRouterParam(event, 'resource')}`, { method: 'POST', body: await readBody(event) })))