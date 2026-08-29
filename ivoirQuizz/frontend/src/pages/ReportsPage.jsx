import { useState } from 'react'
import { Link } from 'react-router-dom'
import { reports as reportsApi } from '../api/endpoints'
import { useResource } from '../hooks/useResource'
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
import { useToast } from '../ui/ToastContext'
import { REPORT_STATUSES, REPORT_STATUS_LABEL, REPORT_STATUS_TONE, formatDate } from '../lib/constants'

function ResolveDialog({ report, onClose, onDone }) {
  const toast = useToast()
  const [status, setStatus] = useState('fixed')
  const [deactivate, setDeactivate] = useState(false)
  const [busy, setBusy] = useState(false)

  async function submit() {
    setBusy(true)
    try {
      await reportsApi.resolve(report.id, { status, deactivate_question: deactivate })
      toast.success('Signalement traité.')
      onDone()
    } catch (error) {
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  const question = report.question

  return (
    <Modal
      wide
      title="Traiter le signalement"
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn" onClick={onClose} disabled={busy}>
            Annuler
          </button>
          <button type="button" className="btn primary" onClick={submit} disabled={busy}>
            {busy ? <span className="spinner" /> : 'Enregistrer'}
          </button>
        </>
      }
    >
      <div className="stack" style={{ gap: 16 }}>
        <div>
          <div className="stat-label">Motif signalé</div>
          <p>
            <strong>{report.reason}</strong>
          </p>
          {report.message && <p className="muted small">« {report.message} »</p>}
          <p className="faint small">
            Par {report.reporter?.name || 'un joueur'} · {formatDate(report.created_at)}
          </p>
        </div>

        {question && (
          <div className="card" style={{ boxShadow: 'none' }}>
            <div className="card-body">
              <div className="stat-label">La question</div>
              <p className="question-text" style={{ margin: '6px 0 10px' }}>
                {question.question_text}
              </p>
              <div>
                {(question.answers || []).map((answer) => (
                  <span key={answer.id} className={`answer-pill ${answer.is_correct ? 'correct' : ''}`}>
                    {answer.answer_text}
                    {answer.is_correct ? ' ✓' : ''}
                  </span>
                ))}
              </div>
              {question.explanation && <p className="muted small" style={{ marginTop: 10 }}>{question.explanation}</p>}
              {question.stats?.success_rate !== undefined && question.stats?.success_rate !== null && (
                <p className="faint small" style={{ marginTop: 8 }}>
                  Taux de réussite : {question.stats.success_rate} % sur {question.stats.times_answered} réponses
                </p>
              )}
              <Link className="btn sm" style={{ marginTop: 12 }} to={`/questions/${question.id}`}>
                Ouvrir dans l’éditeur
              </Link>
            </div>
          </div>
        )}

        <Field label="Décision">
          <Select
            value={status}
            onChange={setStatus}
            options={REPORT_STATUSES.filter((s) => s.value !== 'pending')}
          />
        </Field>

        <label className="checkbox">
          <input type="checkbox" checked={deactivate} onChange={(e) => setDeactivate(e.target.checked)} />
          Retirer la question de la rotation
        </label>
        <p className="faint small" style={{ marginTop: -8 }}>
          À cocher si la bonne réponse est fausse : tant que la question tourne, elle coûte des parties à tous les
          joueurs.
        </p>
      </div>
    </Modal>
  )
}

export function ReportsPage() {
  const [status, setStatus] = useState('pending')
  const [page, setPage] = useState(1)
  const [active, setActive] = useState(null)

  const list = useResource((signal) => reportsApi.list({ status, page }, signal), [status, page])
  const items = list.data?.items || []

  return (
    <>
      <PageHeader
        title="Signalements"
        subtitle="Ce que les joueurs remontent. Les signalements en attente passent toujours en premier."
      />

      <div className="page-body stack">
        <Card>
          <div className="filters">
            <Field label="Statut">
              <Select
                value={status}
                onChange={(value) => {
                  setStatus(value)
                  setPage(1)
                }}
                placeholder="Tous"
                options={REPORT_STATUSES}
              />
            </Field>
          </div>
        </Card>

        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="✅" title="Rien à traiter">
              {status === 'pending'
                ? 'Aucun signalement en attente. Le contenu tient la route.'
                : 'Aucun signalement avec ce statut.'}
            </EmptyState>
          ) : (
            <>
              <div className="table-wrap">
                <table className="data">
                  <thead>
                    <tr>
                      <th>Question</th>
                      <th>Motif</th>
                      <th>Statut</th>
                      <th>Date</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((report) => (
                      <tr key={report.id}>
                        <td>
                          <div className="truncate">{report.question?.question_text || '—'}</div>
                          <div className="faint small">{report.question?.level_title || '—'}</div>
                        </td>
                        <td>
                          <div>{report.reason}</div>
                          {report.message && <div className="faint small truncate">{report.message}</div>}
                        </td>
                        <td>
                          <Badge tone={REPORT_STATUS_TONE[report.status]}>
                            {REPORT_STATUS_LABEL[report.status] || report.status}
                          </Badge>
                        </td>
                        <td className="small muted nowrap">{formatDate(report.created_at)}</td>
                        <td className="actions">
                          <button type="button" className="btn sm primary" onClick={() => setActive(report)}>
                            Traiter
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

      {active && (
        <ResolveDialog
          report={active}
          onClose={() => setActive(null)}
          onDone={() => {
            setActive(null)
            list.reload()
          }}
        />
      )}
    </>
  )
}
