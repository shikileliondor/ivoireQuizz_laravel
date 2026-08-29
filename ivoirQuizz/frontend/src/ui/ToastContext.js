import { createContext, useContext } from 'react'

export const ToastContext = createContext(null)

export function useToast() {
  const context = useContext(ToastContext)

  if (!context) {
    throw new Error('useToast doit être utilisé à l’intérieur de <ToastProvider>.')
  }

  return context
}
