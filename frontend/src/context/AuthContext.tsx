import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type PropsWithChildren,
} from 'react'
import { getCurrentUser, login as loginRequest, logout as logoutRequest } from '../services/auth.ts'
import { ApiError, setUnauthorizedHandler } from '../services/http.ts'
import { navigate } from '../router/navigation.ts'
import { authContext, type AuthContextValue } from './auth-context.ts'
import type { AuthStatus, AuthUser } from '../types/auth.ts'

const AUTH_SESSION_SEEN_KEY = 'auth_session_seen'

function hadAuthenticatedSession(): boolean {
  return sessionStorage.getItem(AUTH_SESSION_SEEN_KEY) === '1'
}

function markAuthenticatedSession(): void {
  sessionStorage.setItem(AUTH_SESSION_SEEN_KEY, '1')
}

function clearAuthenticatedSession(): void {
  sessionStorage.removeItem(AUTH_SESSION_SEEN_KEY)
}

export function AuthProvider({ children }: PropsWithChildren): React.JSX.Element {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [status, setStatus] = useState<AuthStatus>('loading')
  const [notice, setNotice] = useState<string | null>(null)
  const [bootstrapError, setBootstrapError] = useState<string | null>(null)
  const isInitialSessionCheck = useRef(true)
  const currentUserRequest = useRef<Promise<AuthUser> | null>(null)
  const clearNotice = useCallback(() => setNotice(null), [])

  const handleUnauthorized = useCallback(() => {
    const sessionHadBeenAuthenticated = hadAuthenticatedSession()

    if (sessionHadBeenAuthenticated) {
      clearAuthenticatedSession()
      setNotice('Tu sesión ha expirado. Inicia sesión nuevamente.')
    } else {
      setNotice(null)
    }

    setUser(null)
    setStatus('unauthenticated')
    setBootstrapError(null)

    if (!isInitialSessionCheck.current && window.location.pathname !== '/login') {
      navigate('/login')
    }
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(handleUnauthorized)

    return () => setUnauthorizedHandler(null)
  }, [handleUnauthorized])

  useEffect(() => {
    let active = true

    currentUserRequest.current ??= getCurrentUser()

    currentUserRequest.current
      .then((currentUser) => {
        if (active) {
          setUser(currentUser)
          setStatus('authenticated')
          setBootstrapError(null)
          markAuthenticatedSession()
        }
      })
      .catch((error: unknown) => {
        if (!active) {
          return
        }

        setUser(null)
        if (error instanceof ApiError && error.status === 401) {
          setBootstrapError(null)
          setStatus('unauthenticated')
        } else {
          setBootstrapError('No pudimos verificar tu sesión. Intenta nuevamente.')
          setStatus('unavailable')
        }
      })
      .finally(() => {
        isInitialSessionCheck.current = false
      })

    return () => {
      active = false
    }
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    try {
      const authenticatedUser = await loginRequest(email, password)
      setUser(authenticatedUser)
      setStatus('authenticated')
      setBootstrapError(null)
      setNotice(null)
      markAuthenticatedSession()
      return authenticatedUser
    } catch (error: unknown) {
      if (error instanceof ApiError && error.status >= 400 && error.status < 500 && error.status !== 404) {
        setStatus('unauthenticated')
        setBootstrapError(null)
      }

      throw error
    }
  }, [])

  const logout = useCallback(async () => {
    clearAuthenticatedSession()

    try {
      await logoutRequest()
    } finally {
      setUser(null)
      setStatus('unauthenticated')
      setBootstrapError(null)
      setNotice(null)
      navigate('/login')
    }
  }, [])

  const value = useMemo<AuthContextValue>(() => ({
    user,
    status,
    notice,
    bootstrapError,
    login,
    logout,
    can: (permission: string) => user?.permisos.includes(permission) ?? false,
    clearNotice,
  }), [bootstrapError, clearNotice, login, logout, notice, status, user])

  return <authContext.Provider value={value}>{children}</authContext.Provider>
}
