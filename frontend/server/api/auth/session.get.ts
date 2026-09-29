export default defineEventHandler((event) => {
  const config = useRuntimeConfig(event)
  return { present: Boolean(getCookie(event, config.public.sessionCookieName)) }
})
