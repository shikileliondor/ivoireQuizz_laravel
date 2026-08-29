import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { categories as categoriesApi, questions as questionsApi, regions as regionsApi } from '../api/endpoints'
import { useDebounced, useResource } from '../hooks/useResource'
import {
  Badge,
  Card,
  ConfirmDialog,
  EmptyState,
  ErrorState,
  Field,
  Loading,
  PageHeader,
  Pagination,
  Select,
} from '../ui/components'
import { useToast } from '../ui/ToastContext'
import { DIFFICULTIES, DIFFICULTY_LABEL, QUESTION_TYPES, successRateTone } from '../lib/constants'

export function QuestionsPage() {
  const toast = useToast()
  const [params, setParams] = useSearchParams()

  const [search, setSearch] = useState(params.get('search') || '')
  const debouncedSearch = useDebounced(search)
  const [page, setPage] = useState(1)
  const [regionId, setRegionId] = useState(params.get('region_id') || '')
  const [categoryId, setCategoryId] = useState('')
  const [difficulty, setDifficulty] = useState('')
  const [type, setType] = useState('')
  const [reportedOnly, setReportedOnly] = useState(params.get('signalees') === '1')
  const [pendingDelete, setPendingDelete] = useState(null)
  const [busy, setBusy] = useState(false)

  // Arriving from a level row pins the list to that level until it is cleared.
  const levelId = params.get('level') || ''

  const regions = useResource((signal) => regionsApi.list({ per_page: 100 }, signal), [])
  const categories = useResource((signal) => categoriesApi.list({}, signal), [])

  const list = useResource(
    (signal) =>
      questionsApi.list(
        {
          page,
          search: debouncedSearch,
          level_id: levelId,
          region_id: regionId,
          category_id: categoryId,
          difficulty,
          type,
          reported_only: reportedOnly ? 1 : '',
        },
        signal,
      ),
    [page, debouncedSearch, levelId, regionId, categoryId, difficulty, type, reportedOnly],
  )

  function updateFilter(setter, value) {
    setter(value)
    setPage(1)
  }

  async function confirmDelete() {
    setBusy(true)
    try {
      await questionsApi.remove(pendingDelete.id)
      toast.success('Question archivée.')
      setPendingDelete(null)
      list.reload()
    } catch (error) {
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  const items = list.data?.items || []

  return (
    <>
      <PageHeader title="Questions" subtitle="Tout le contenu jouable, filtrable et corrigeable.">
        <Link className="btn" to="/questions/import">
          📥 Import
        </Link>
        <Link className="btn primary" to="/questions/nouvelle">
          ✏️ Nouvelle question
        </Link>
      </PageHeader>

      <div className="page-body stack">
        {levelId && (
          <div className="alert info">
            Liste filtrée sur un niveau précis.{' '}
            <button type="button" className="btn sm" onClick={() => setParams({})}>
              Retirer le filtre
            </button>
          </div>
        )}

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
                placeholder="Chercher dans les énoncés…"
              />
            </Field>

            <Field label="Région">
              <Select
                value={regionId}
                onChange={(value) => {
                  updateFilter(setRegionId, value)
                  setParams(value ? { region_id: value } : {})
                }}
                placeholder="Toutes"
                options={(regions.data?.items || []).map((r) => ({ value: String(r.id), label: r.name }))}
              />
            </Field>

            <Field label="Catégorie">
              <Select
                value={categoryId}
                onChange={(value) => updateFilter(setCategoryId, value)}
                placeholder="Toutes"
                options={(categories.data || []).map((c) => ({ value: String(c.id), label: c.name }))}
              />
            </Field>

            <Field label="Difficulté">
              <Select
                value={difficulty}
                onChange={(value) => updateFilter(setDifficulty, value)}
                placeholder="Toutes"
                options={DIFFICULTIES}
              />
            </Field>

            <Field label="Type">
              <Select value={type} onChange={(value) => updateFilter(setType, value)} placeholder="Tous" options={QUESTION_TYPES} />
            </Field>

            <Field label="&nbsp;">
              <label className="checkbox">
                <input
                  type="checkbox"
                  checked={reportedOnly}
                  onChange={(e) => updateFilter(setReportedOnly, e.target.checked)}
                />
                Signalées seulement
              </label>
            </Field>
          </div>
        </Card>

        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="🔍" title="Aucune question ne correspond">
              Ajuste les filtres, ou crée la première question de ce niveau.
            </EmptyState>
          ) : (
            <>
              <div className="table-wrap">
                <table className="data">
                  <thead>
                    <tr>
                      <th>Question</th>
                      <th>Emplacement</th>
                      <th>Difficulté</th>
                      <th>Réussite</th>
                      <th>État</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((question) => {
                      const rate = question.stats?.success_rate
                      const correct = (question.answers || []).find((a) => a.is_correct)

                      return (
                        <tr key={question.id}>
                          <td>
                            <div className="question-text truncate">{question.question_text}</div>
                            {correct && <span className="answer-pill correct">{correct.answer_text}</span>}
                            {!question.explanation && (
                              <span className="answer-pill" title="Le joueur ne verra rien après avoir répondu">
                                sans explication
                              </span>
                            )}
                          </td>
                          <td className="small muted nowrap">
                            {question.chapter_name || '—'}
                            <div className="faint">{question.level_title || '—'}</div>
                          </td>
                          <td>
                            <Badge>{DIFFICULTY_LABEL[question.difficulty] || question.difficulty}</Badge>
                          </td>
                          <td className="nowrap">
                            {rate === null || rate === undefined ? (
                              <span className="faint small">—</span>
                            ) : (
                              <Badge tone={successRateTone(rate)}>{rate} %</Badge>
                            )}
                            <div className="faint small">{question.stats?.times_answered ?? 0} rép.</div>
                          </td>
                          <td>
                            {!question.is_active && <Badge tone="danger">Archivée</Badge>}
                            {question.is_active && question.pending_reports_count > 0 && (
                              <Badge tone="warning">🚩 {question.pending_reports_count}</Badge>
                            )}
                            {question.is_active && !question.pending_reports_count && <Badge tone="ok">Active</Badge>}
                          </td>
                          <td className="actions">
                            <Link className="btn sm" to={`/questions/${question.id}`}>
                              Modifier
                            </Link>{' '}
                            <button type="button" className="btn sm ghost" onClick={() => setPendingDelete(question)}>
                              Archiver
                            </button>
                          </td>
                        </tr>
                      )
                    })}
                  </tbody>
                </table>
              </div>
              <Pagination meta={list.data?.meta} onPage={setPage} />
            </>
          )}
        </Card>
      </div>

      {pendingDelete && (
        <ConfirmDialog
          title="Archiver cette question ?"
          message={`« ${pendingDelete.question_text} » ne sera plus tirée dans les parties. Son historique reste intact et elle peut être réactivée.`}
          confirmLabel="Archiver"
          busy={busy}
          onConfirm={confirmDelete}
          onCancel={() => setPendingDelete(null)}
        />
      )}
    </>
  )
}
