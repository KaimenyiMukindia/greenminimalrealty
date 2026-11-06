export interface AuthUser {
  id: number
  name: string
  email: string
  role: string
  email_verified_at: string | null
  has_password: boolean
  dashboard_path: string
}

export interface AuthResponse {
  data: { user: AuthUser; message: string }
}

export interface StatusResponse {
  data: { message: string; code: string }
}

export function authErrorMessage(error: unknown, fallback: string): string {
  if (!error || typeof error !== 'object' || !('data' in error)) return fallback
  const outer = error.data
  if (!outer || typeof outer !== 'object') return fallback
  const data = 'data' in outer && outer.data && typeof outer.data === 'object' ? outer.data : outer
  if ('errors' in data && data.errors && typeof data.errors === 'object') {
    const messages = Object.values(data.errors).flat().filter((value): value is string => typeof value === 'string')
    if (messages.length) return messages.join(' ')
  }
  return 'message' in data && typeof data.message === 'string' ? data.message : fallback
}

export function createAuthService(fetcher: ReturnType<typeof useRequestFetch>) {
  return {
    session: () => fetcher<{ present: boolean }>('/api/auth/session'),
    me: () => fetcher<{ data: AuthUser }>('/api/auth/me'),
    login: (body: { email: string; password: string }) => fetcher<AuthResponse>('/api/auth/login', { method: 'POST', body }),
    register: (body: { name: string; email: string; password: string; password_confirmation: string }) => fetcher<AuthResponse>('/api/auth/register', { method: 'POST', body }),
    exchangeGoogleCode: (code: string) => fetcher<AuthResponse>('/api/auth/google/exchange', { method: 'POST', body: { code } }),
    logout: (all = false) => fetcher<StatusResponse>(all ? '/api/auth/logout-all' : '/api/auth/logout', { method: 'POST' }),
    forgotPassword: (email: string) => fetcher<StatusResponse>('/api/auth/forgot-password', { method: 'POST', body: { email } }),
    resetPassword: (body: { email: string; token: string; password: string; password_confirmation: string }) => fetcher<StatusResponse>('/api/auth/reset-password', { method: 'POST', body }),
    resendVerification: () => fetcher<StatusResponse>('/api/auth/email/verification-notification', { method: 'POST' }),
    verifyEmail: (body: { id: string; hash: string; expires: string; signature: string }) => fetcher<StatusResponse>('/api/auth/email/verify', { method: 'POST', body }),
    updateProfile: (body: { name: string; email: string; current_password?: string }) => fetcher<{ data: AuthUser }>('/api/auth/me', { method: 'PATCH', body }),
    deleteAccount: (body: { current_password: string; confirmed: boolean }) => fetcher<StatusResponse>('/api/auth/me', { method: 'DELETE', body }),
  }
}
