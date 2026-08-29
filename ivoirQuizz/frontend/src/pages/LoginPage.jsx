import { useState } from 'react'
import { useAuth } from '../auth/AuthContext'
import { Field } from '../ui/components'

export function LoginPage() {
  const { signIn, status } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)

  async function handleSubmit(event) {
    event.preventDefault()
    setBusy(true)
    setError(null)

    try {
      await signIn(email, password)
    } catch (caught) {
      setError(caught)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="login-screen">
      <div className="login-card">
        <div className="login-logo">
          <div className="mark" aria-hidden="true">
            🇨🇮
          </div>
          <h1>IvoireQuiz</h1>
          <p>Espace d’administration</p>
        </div>

        <div className="card">
          <div className="card-body">
            <form onSubmit={handleSubmit} className="stack" style={{ gap: 14 }}>
              {status === 'forbidden' && (
                <div className="alert warning">
                  Ta session a expiré ou ce compte n’est plus administrateur.
                </div>
              )}

              {error && (
                <div className="alert error">
                  {error.fieldError?.('email') || error.message}
                </div>
              )}

              <Field label="Adresse e-mail">
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  autoComplete="username"
                  required
                  autoFocus
                />
              </Field>

              <Field label="Mot de passe">
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoComplete="current-password"
                  required
                />
              </Field>

              <button type="submit" className="btn primary lg block" disabled={busy}>
                {busy ? <span className="spinner" /> : 'Se connecter'}
              </button>

              <p className="faint small" style={{ textAlign: 'center' }}>
                Réservé aux comptes administrateur.
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>
  )
}
