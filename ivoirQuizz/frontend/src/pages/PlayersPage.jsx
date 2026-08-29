import { useState } from 'react'
import { players as playersApi } from '../api/endpoints'
import { useDebounced, useResource } from '../hooks/useResource'
import {
  Badge,
  Card,
  EmptyState,
  ErrorState,
  Field,
  Loading,
  Modal,
  PageHeader,
  Pagination,
  Select,
} from '../ui/components'
import { useAuth } from '../auth/AuthContext'
import { useToast } from '../ui/ToastContext'
import { formatDate, formatNumber } from '../lib/constants'

function PlayerDrawer({ playerId, onClose, onSaved }) {
  const toast = useToast()
  const { user } = useAuth()
  const detail = useResource((signal) => playersApi.show(playerId, signal), [playerId])
  const [busy, setBusy] = useState(false)
  const [form, setForm] = useState(null)

  const player = detail.data?.player
  const sessions = detail.data?.recent_sessions || []
  const isSelf = user?.id === playerId

  const current = form ?? {
    lives: player?.lives ?? 5,
    coins: player?.coins ?? 0,
    gems: player?.gems ?? 0,
    role: player?.role ?? 'player',
  }

  const patch = (changes) => setForm({ ...current, ...changes })

  async function submit() {
    setBusy(true)
    try {
      await playersApi.update(playerId, {
        lives: Number(current.lives),
        coins: Number(current.coins),
        gems: Number(current.gems),
        role: current.role,
      })
      toast.success('Joueur mis à jour.')
      onSaved()
    } catch (error) {
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <Modal
      wide
      title={player ? player.name : 'Joueur'}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn" onClick={onClose} disabled={busy}>
            Fermer
          </button>
          <button type="button" className="btn primary" onClick={submit} disabled={busy || !player}>
            {busy ? <span className="spinner" /> : 'Enregistrer'}
          </button>
        </>
      }
    >
      {detail.loading ? (
        <Loading />
      ) : !player ? (
        <ErrorState error={detail.error} onRetry={detail.reload} />
      ) : (
        <div className="stack" style={{ gap: 18 }}>
          <div className="grid cols-4">
            <div className="stat">
              <div className="stat-label">XP</div>
              <div className="stat-value" style={{ fontSize: 20 }}>{formatNumber(player.xp_total)}</div>
            </div>
            <div className="stat">
              <div className="stat-label">Parties</div>
              <div className="stat-value" style={{ fontSize: 20 }}>{formatNumber(player.games_played)}</div>
              <div className="stat-hint">{formatNumber(player.games_won)} gagnées</div>
            </div>
            <div className="stat">
              <div className="stat-label">Série</div>
              <div className="stat-value" style={{ fontSize: 20 }}>{player.streak ?? 0} j</div>
            </div>
            <div className="stat">
              <div className="stat-label">Inscrit le</div>
              <div className="stat-value" style={{ fontSize: 15, marginTop: 8 }}>{formatDate(player.created_at)}</div>
            </div>
          </div>

          <div>
            <h3 style={{ marginBottom: 10 }}>Ajustements support</h3>
            <div className="filters">
              <Field label="Vies" hint="0 à 5">
                <input type="number" min="0" max="5" value={current.lives} onChange={(e) => patch({ lives: e.target.value })} />
              </Field>
              <Field label="Pièces">
                <input type="number" min="0" value={current.coins} onChange={(e) => patch({ coins: e.target.value })} />
              </Field>
              <Field label="Gemmes">
                <input type="number" min="0" value={current.gems} onChange={(e) => patch({ gems: e.target.value })} />
              </Field>
              <Field label="Rôle" hint={isSelf ? 'Tu ne peux pas retirer tes propres droits' : undefined}>
                <Select
                  value={current.role}
                  onChange={(value) => patch({ role: value })}
                  disabled={isSelf}
                  options={[
                    { value: 'player', label: 'Joueur' },
                    { value: 'admin', label: 'Administrateur' },
                  ]}
                />
              </Field>
            </div>
          </div>

          <div>
            <h3 style={{ marginBottom: 10 }}>20 dernières parties</h3>
            {sessions.length === 0 ? (
              <p className="faint small">Ce joueur n’a encore joué aucune partie.</p>
            ) : (
              <div className="table-wrap">
                <table className="data">
                  <thead>
                    <tr>
                      <th>Niveau</th>
                      <th>Mode</th>
                      <th>Statut</th>
                      <th className="num">Score</th>
                      <th className="num">Précision</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {sessions.map((session) => (
                      <tr key={session.id}>
                        <td>{session.level?.title || '—'}</td>
                        <td className="muted small">{session.mode}</td>
                        <td>
                          <Badge
                            tone={
                              session.status === 'completed' ? 'ok' : session.status === 'failed' ? 'danger' : 'warning'
                            }
                          >
                            {session.status}
                          </Badge>
                        </td>
                        <td className="num">{session.score}</td>
                        <td className="num">{session.accuracy} %</td>
                        <td className="muted small nowrap">{formatDate(session.created_at)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}
    </Modal>
  )
}

export function PlayersPage() {
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounced(search)
  const [role, setRole] = useState('')
  const [page, setPage] = useState(1)
  const [openId, setOpenId] = useState(null)

  const list = useResource(
    (signal) => playersApi.list({ search: debouncedSearch, role, page }, signal),
    [debouncedSearch, role, page],
  )

  const items = list.data?.items || []

  return (
    <>
      <PageHeader title="Joueurs" subtitle="Outil de support : débloquer un compte, rendre ce qu’un bug a coûté." />

      <div className="page-body stack">
        <Card>
          <div className="filters">
            <Field label="Recherche" className="grow">
              <input
                type="search"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
                  setPage(1)
                }}
                placeholder="Nom, e-mail ou code ami…"
              />
            </Field>
            <Field label="Rôle">
              <Select
                value={role}
                onChange={(value) => {
                  setRole(value)
                  setPage(1)
                }}
                placeholder="Tous"
                options={[
                  { value: 'player', label: 'Joueurs' },
                  { value: 'admin', label: 'Administrateurs' },
                ]}
              />
            </Field>
          </div>
        </Card>

        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="👥" title="Aucun joueur trouvé">
              Ajuste la recherche.
            </EmptyState>
          ) : (
            <>
              <div className="table-wrap">
                <table className="data">
                  <thead>
                    <tr>
                      <th>Joueur</th>
                      <th>Code ami</th>
                      <th className="num">XP</th>
                      <th className="num">Parties</th>
                      <th>Vies</th>
                      <th>Rôle</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((player) => (
                      <tr key={player.id}>
                        <td>
                          <div className="question-text">{player.name}</div>
                          <div className="faint small">{player.email}</div>
                        </td>
                        <td className="mono">{player.friend_code}</td>
                        <td className="num">{formatNumber(player.xp_total)}</td>
                        <td className="num">{formatNumber(player.games_played)}</td>
                        <td>{player.lives ?? '—'}</td>
                        <td>
                          {player.role === 'admin' ? <Badge tone="brand">Admin</Badge> : <Badge>Joueur</Badge>}
                        </td>
                        <td className="actions">
                          <button type="button" className="btn sm" onClick={() => setOpenId(player.id)}>
                            Ouvrir
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <Pagination meta={list.data?.meta} onPage={setPage} />
            </>
          )}
        </Card>
      </div>

      {openId && (
        <PlayerDrawer
          playerId={openId}
          onClose={() => setOpenId(null)}
          onSaved={() => {
            setOpenId(null)
            list.reload()
          }}
        />
      )}
    </>
  )
}
