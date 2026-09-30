import { AuthProvider } from './context/AuthContext.tsx'
import { ThemeProvider } from './context/ThemeContext.tsx'
import { AppRouter } from './router/AppRouter.tsx'

function App() {
  return <ThemeProvider><AuthProvider><AppRouter /></AuthProvider></ThemeProvider>
}

export default App
