import { useContext } from 'react'
import { themeContext, type ThemeContextValue } from '../context/theme-context.ts'

export function useTheme(): ThemeContextValue {
  const context = useContext(themeContext)

  if (!context) {
    throw new Error('useTheme debe utilizarse dentro de ThemeProvider.')
  }

  return context
}
