import { defineStore } from 'pinia'
import { createAuthService, type AuthUser } from '~/services/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const present = ref(false)
  const service = createAuthService(useRequestFetch())

  function clear() {
    user.value = null
    present.value = false
  }

  async function initialize() {
    const session = await service.session()
    present.value = session.present
    if (!session.present) {
      clear()
      return
    }
    user.value = (await service.me()).data
  }

  async function login(body: { email: string; password: string }) {
    user.value = (await service.login(body)).data.user
    present.value = true
  }

  async function exchangeGoogleCode(code: string) {
    user.value = (await service.exchangeGoogleCode(code)).data.user
    present.value = true
  }

  async function logout(all = false) {
    await service.logout(all)
    clear()
  }

  const register = service.register

  async function updateProfile(body: { name: string; email: string; current_password?: string }) {
    user.value = (await service.updateProfile(body)).data
  }

  async function deleteAccount(body: { current_password: string; confirmed: boolean }) {
    await service.deleteAccount(body)
    clear()
  }

  return { user, present, clear, initialize, login, register, exchangeGoogleCode, logout, updateProfile, deleteAccount }
})
