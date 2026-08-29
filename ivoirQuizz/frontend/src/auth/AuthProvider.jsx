import { useCallback, useEffect, useMemo, useState } from 'react'
import { ApiError, setUnauthenticatedHandler, tokenStore } from '../api/client'
import { auth as authApi, dashboard } from '../api/endpoints'
import { AuthContext } from './AuthContext'

/**
 * `/me` does not expose `role`, so being an admin is established the only way
 * the API allows: by asking for an admin resource and seeing whether it answers.
 */
async function assertAdmin() {
  try {
    await dashboard.summary()
    return true
  } catch (error) {
    if (error instanceof ApiError && error.isForbidden) return false
    throw error
  }
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [status, setStatus] = useState('checking')

  const signOut = useCallback(async ({ notifyServer = true } = {}) => {
    if (notifyServer && tokenStore.get()) {
      // A dead token cannot be revoked; dropping it locally is what matters.
      try {
        await authApi.logout()
      } catch {
        /* ignore */
      }
    }

    tokenStore.clear()
    setUser(null)
    setStatus('signed-out')
  }, [])

  useEffect(() => {
    setUnauthenticatedHandler(() => {
      tokenStore.clear()
      setUser(null)
      setStatus('signed-out')
    })
  }, [])

  // Restore an existing session on first paint.
  useEffect(() => {
    let cancelled = false

    async function restore() {
      if (!tokenStore.get()) {
        if (!cancelled) setStatus('signed-out')
        return
      }

      try {
        const profile = await authApi.me()
        const isAdmin = await assertAdmin()

        if (cancelled) return

        if (!isAdmin) {
          tokenStore.clear()
          setStatus('forbidden')
          return
        }

        setUser(profile)
        setStatus('signed-in')
      } catch {
        if (cancelled) return
        tokenStore.clear()
        setStatus('signed-out')
      }
    }

    restore()
    return () => {
      cancelled = true
    }
  }, [])

  const signIn = useCallback(async (email, password) => {
    const result = await authApi.login(email, password)
    const token = result?.token

    if (!token) {
      throw new ApiError('Réponse de connexion inattendue : aucun jeton reçu.', { status: 0 })
    }

    tokenStore.set(token)

    if (!(await assertAdmin())) {
      tokenStore.clear()
      throw new ApiError(
        'Ce compte existe mais n’a pas les droits administrateur. Demande à un admin de te promouvoir.',
        { status: 403 },
      )
    }

    setUser(result.user ?? (await authApi.me()))
    setStatus('signed-in')
  }, [])

  const value = useMemo(
    () => ({ user, status, signIn, signOut }),
    [user, status, signIn, signOut],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
