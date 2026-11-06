export default defineEventHandler((event) => {
  if (!event.path.startsWith('/api/auth/')) return
  setHeader(event, 'Cache-Control', 'no-store')
  if (['GET', 'HEAD', 'OPTIONS'].includes(event.method)) return
  const origin = getHeader(event, 'origin')
  const site = getHeader(event, 'sec-fetch-site')
  const config = useRuntimeConfig(event)
  const expectedOrigin = config.frontendUrl ? new URL(config.frontendUrl).origin : getRequestURL(event).origin
  if ((origin && origin !== expectedOrigin) || site === 'cross-site') {
    throw createError({ statusCode: 403, message: 'Cross-origin authentication requests are not allowed.' })
  }
})
