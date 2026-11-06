import { createHash, randomBytes } from 'node:crypto'

export default defineEventHandler((event) => {
  const config = useRuntimeConfig(event)
  const verifier = randomBytes(32).toString('hex')
  setCookie(event, `${config.public.sessionCookieName}_oauth`, verifier, {
    httpOnly: true, secure: config.sessionCookieSecure, sameSite: 'lax', path: '/', maxAge: 600,
  })
  setHeader(event, 'Cache-Control', 'no-store')
  const challenge = createHash('sha256').update(verifier).digest('hex')
  return sendRedirect(event, `${config.laravelApiUrl.replace(/\/$/, '')}/auth/google?challenge=${challenge}`)
})
