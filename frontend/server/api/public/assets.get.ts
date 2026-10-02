import { sendStream } from 'h3'
import { Readable } from 'node:stream'

export default defineEventHandler(async (event) => {
  const path = getQuery(event).path
  if (typeof path !== 'string' || !path.startsWith('/storage/')) throw createError({ statusCode: 400, statusMessage: 'Invalid asset path' })
  const config = useRuntimeConfig(event)
  const response = await $fetch.raw<ArrayBuffer>(`${config.laravelApiUrl.replace(/\/$/, '')}${path}`, { responseType: 'arrayBuffer' })
  const contentType = response.headers.get('content-type')
  if (contentType) setResponseHeader(event, 'content-type', contentType)
  setResponseHeader(event, 'cache-control', 'public, max-age=3600')
  return sendStream(event, Readable.from(Buffer.from(response._data)))
})