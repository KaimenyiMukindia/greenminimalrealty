import { laravelRequest } from '../../../utils/laravel'

export default defineEventHandler(async (event) => {
  const body = await readBody<{ id?: unknown; hash?: unknown; expires?: unknown; signature?: unknown }>(event)
  if (!body || typeof body.id !== 'string' || !/^\d+$/.test(body.id)
    || typeof body.hash !== 'string' || !/^[a-f0-9]{40}$/.test(body.hash)
    || typeof body.expires !== 'string' || !/^\d+$/.test(body.expires)
    || typeof body.signature !== 'string' || !/^[a-f0-9]{64}$/.test(body.signature)) {
    throw createError({ statusCode: 422, message: 'The verification link is invalid.' })
  }
  const query = new URLSearchParams({ expires: body.expires, signature: body.signature })
  return laravelRequest(event, `/email/verify/${body.id}/${body.hash}?${query}`)
})
