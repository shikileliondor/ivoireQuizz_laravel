import { useCallback, useMemo, useRef, useState } from 'react'
import { ToastContext } from './ToastContext'

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])
  const nextId = useRef(1)

  const dismiss = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
  }, [])

  const push = useCallback(
    (message, tone = 'success') => {
      const id = nextId.current++
      setToasts((current) => [...current, { id, message, tone }])

      // Errors stay until dismissed: they usually carry a message the editor
      // needs to act on, and stealing it after four seconds helps nobody.
      if (tone !== 'error') {
        setTimeout(() => dismiss(id), 4000)
      }

      return id
    },
    [dismiss],
  )

  const value = useMemo(
    () => ({
      success: (message) => push(message, 'success'),
      error: (message) => push(message, 'error'),
      info: (message) => push(message, 'info'),
      dismiss,
    }),
    [push, dismiss],
  )

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="toast-stack">
        {toasts.map((toast) => (
          <div key={toast.id} className={`toast ${toast.tone}`} role="status">
            <span style={{ flex: 1 }}>{toast.message}</span>
            <button type="button" onClick={() => dismiss(toast.id)} aria-label="Fermer">
              ✕
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}
