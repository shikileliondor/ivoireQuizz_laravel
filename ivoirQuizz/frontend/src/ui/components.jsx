import { useEffect } from 'react'

export function PageHeader({ title, subtitle, children }) {
  return (
    <header className="page-header">
      <div className="page-titles">
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {children && <div className="page-actions">{children}</div>}
    </header>
  )
}

export function Card({ title, hint, actions, children, bodyClass = '' }) {
  return (
    <section className="card">
      {(title || actions) && (
        <div className="card-header">
          {title && <h2>{title}</h2>}
          {hint && <span className="card-hint">{hint}</span>}
          {actions}
        </div>
      )}
      <div className={`card-body ${bodyClass}`}>{children}</div>
    </section>
  )
}

export function Field({ label, hint, error, children, className = '' }) {
  return (
    <div className={`field ${className}`}>
      {label && <label>{label}</label>}
      {children}
      {error && <span className="error">{error}</span>}
      {!error && hint && <span className="hint">{hint}</span>}
    </div>
  )
}

export function Select({ value, onChange, options, placeholder, ...rest }) {
  return (
    <select value={value ?? ''} onChange={(e) => onChange(e.target.value)} {...rest}>
      {placeholder && <option value="">{placeholder}</option>}
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  )
}

export function Badge({ tone = '', children }) {
  return <span className={`badge ${tone}`}>{children}</span>
}

export function Loading({ label = 'Chargement…' }) {
  return (
    <div className="loading-block">
      <span className="spinner" />
      <span>{label}</span>
    </div>
  )
}

export function EmptyState({ icon = '📭', title, children }) {
  return (
    <div className="empty">
      <div className="empty-icon">{icon}</div>
      <h3>{title}</h3>
      {children && <p>{children}</p>}
    </div>
  )
}

export function ErrorState({ error, onRetry }) {
  if (!error) return null

  return (
    <div className="alert error">
      <div>{error.message}</div>
      {onRetry && (
        <button type="button" className="btn sm" style={{ marginTop: 8 }} onClick={onRetry}>
          Réessayer
        </button>
      )}
    </div>
  )
}

export function Pagination({ meta, onPage }) {
  if (!meta || meta.last_page <= 1) return null

  const page = meta.current_page

  return (
    <div className="pagination">
      <button type="button" className="btn sm" disabled={page <= 1} onClick={() => onPage(page - 1)}>
        ← Précédent
      </button>
      <span>
        Page {page} sur {meta.last_page} · {meta.total} élément{meta.total > 1 ? 's' : ''}
      </span>
      <button
        type="button"
        className="btn sm"
        disabled={page >= meta.last_page}
        onClick={() => onPage(page + 1)}
      >
        Suivant →
      </button>
    </div>
  )
}

export function Modal({ title, onClose, children, footer, wide = false }) {
  useEffect(() => {
    const onKey = (event) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div
      className="modal-backdrop"
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) onClose()
      }}
    >
      <div className={`modal ${wide ? 'wide' : ''}`} role="dialog" aria-modal="true" aria-label={title}>
        <div className="modal-header">
          <h2>{title}</h2>
          <button type="button" className="btn ghost sm" onClick={onClose} aria-label="Fermer">
            ✕
          </button>
        </div>
        <div className="modal-body">{children}</div>
        {footer && <div className="modal-footer">{footer}</div>}
      </div>
    </div>
  )
}

export function ConfirmDialog({ title, message, confirmLabel = 'Confirmer', tone = 'danger', busy, onConfirm, onCancel }) {
  return (
    <Modal
      title={title}
      onClose={onCancel}
      footer={
        <>
          <button type="button" className="btn" onClick={onCancel} disabled={busy}>
            Annuler
          </button>
          <button type="button" className={`btn ${tone}`} onClick={onConfirm} disabled={busy}>
            {busy ? <span className="spinner" /> : confirmLabel}
          </button>
        </>
      }
    >
      <p>{message}</p>
    </Modal>
  )
}

/** Health meter for a level: how much of its question quota is actually filled. */
export function QuotaMeter({ available, required }) {
  if (available === undefined || available === null) return null

  const ratio = required > 0 ? Math.min(1, available / required) : 1
  const tone = ratio >= 1 ? '' : ratio >= 0.5 ? 'warn' : 'bad'

  return (
    <div>
      <span className={ratio >= 1 ? 'muted small' : 'small'} style={{ fontVariantNumeric: 'tabular-nums' }}>
        {available} / {required}
      </span>
      <div className={`meter ${tone}`}>
        <span style={{ width: `${ratio * 100}%` }} />
      </div>
    </div>
  )
}
