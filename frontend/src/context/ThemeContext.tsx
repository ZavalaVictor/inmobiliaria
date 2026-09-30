import {
  useCallback,
  useEffect,
  useMemo,
  useState,
  type PropsWithChildren,
} from 'react'
import { themeContext, type ResolvedTheme, type ThemeMode } from './theme-context.ts'

const STORAGE_KEY = 'sotytech-theme'

function getStoredMode(): ThemeMode {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system'
  } catch {
    return 'system'
  }
}

function getSystemTheme(): ResolvedTheme {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function resolveTheme(mode: ThemeMode): ResolvedTheme {
  return mode === 'system' ? getSystemTheme() : mode
}

export function ThemeProvider({ children }: PropsWithChildren): React.JSX.Element {
  const [mode, setModeState] = useState<ThemeMode>(getStoredMode)
  const [resolvedTheme, setResolvedTheme] = useState<ResolvedTheme>(() => resolveTheme(getStoredMode()))

  const setMode = useCallback((nextMode: ThemeMode): void => {
    setModeState(nextMode)
    try {
      localStorage.setItem(STORAGE_KEY, nextMode)
    } catch {
      // La preferencia visual puede continuar en memoria si el storage está bloqueado.
    }
  }, [])

  const cycleMode = useCallback((): void => {
    setMode(mode === 'light' ? 'dark' : mode === 'dark' ? 'system' : 'light')
  }, [mode, setMode])

  useEffect(() => {
    const applyTheme = (): void => {
      const nextTheme = resolveTheme(mode)
      setResolvedTheme(nextTheme)
      document.documentElement.dataset.theme = nextTheme
      document.documentElement.style.colorScheme = nextTheme
    }

    applyTheme()

    if (mode !== 'system') {
      return undefined
    }

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handleChange = (): void => applyTheme()
    mediaQuery.addEventListener('change', handleChange)

    return () => mediaQuery.removeEventListener('change', handleChange)
  }, [mode])

  const value = useMemo(() => ({ mode, resolvedTheme, setMode, cycleMode }), [cycleMode, mode, resolvedTheme, setMode])

  return <themeContext.Provider value={value}>{children}</themeContext.Provider>
}
