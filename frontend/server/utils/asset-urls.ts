import type { H3Event } from 'h3'

export function rewriteAssetUrls(event: H3Event, value: any): any {
  if (typeof value === 'string' && value.includes('/storage/')) {
    let path = value
    try { path = new URL(value).pathname } catch { /* Already a path from the Laravel API. */ }
    if (path.startsWith('/storage/')) return `/api/public/assets?path=${encodeURIComponent(path)}`
    return value
  }
  if (Array.isArray(value)) return value.map(item => rewriteAssetUrls(event, item))
  if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, rewriteAssetUrls(event, item)]))
  return value
}
