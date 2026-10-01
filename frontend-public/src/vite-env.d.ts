interface ImportMetaEnv {
  readonly VITE_API_URL?: string
  readonly VITE_ADMIN_URL?: string
  readonly VITE_PUBLIC_CONTACT_EMAIL?: string
  readonly VITE_PUBLIC_CONTACT_PHONE?: string
  readonly VITE_PUBLIC_LOCATION_QUERY?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.css'
declare module '*.png'
declare module '*.jpg'
