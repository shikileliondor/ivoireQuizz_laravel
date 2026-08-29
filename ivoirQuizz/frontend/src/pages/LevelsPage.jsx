import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { chapters as chaptersApi, levels as levelsApi, regions as regionsApi } from '../api/endpoints'
import { useResource } from '../hooks/useResource'
import {
  Badge,
  Card,
  ConfirmDialog,
  EmptyState,
  ErrorState,
  Field,
  Loading,
  Modal,
  PageHeader,
  Pagination,
  QuotaMeter,
  Select,
} from '../ui/components'
import { ReorderButtons } from '../ui/ReorderButtons'
import { useToast } from '../ui/ToastContext'
import { DIFFICULTIES, DIFFICULTY_LABEL, NODE_TYPES, NODE_TYPE_LABEL } from '../lib/constants'

function LevelForm({ level, chapterOptions, defaultChapterId, onClose, onSaved }) {
  const toast = useToast()
  const [form, setForm] = useState({
    chapter_id: level?.chapter_id ? String(level.chapter_id) : defaultChapterId || '',
    title: level?.title || '',
    description: level?.description || '',
    difficulty: level?.difficulty || 'easy',
    node_type: level?.node_type || 'level',
    questions_count: level?.questions_count ?? 10,
    passing_score: level?.passing_score ?? 70,
    xp_reward: level?.xp_reward ?? 50,
    coins_reward: level?.coins_reward ?? 0,
    gems_reward: level?.gems_reward ?? 0,
    is_active: level?.is_active ?? true,
  })
  const [errors, setErrors] = useState({})
  const [busy, setBusy] = useState(false)

  const patch = (changes) => setForm((current) => ({ ...current, ...changes }))
  const errorFor = (field) => (Array.isArray(errors[field]) ? errors[field][0] : errors[field])

  async function submit() {
    setBusy(true)
    setErrors({})

    try {
      const payload = {
        ...form,
        chapter_id: Number(form.chapter_id),
        questions_count: Number(form.questions_count),
        passing_score: Number(form.passing_score),
        xp_reward: Number(form.xp_reward),
        coins_reward: Number(form.coins_reward),
        gems_reward: Number(form.gems_reward),
        // The API rejects a level whose node_type and is_boss disagree, so the
        // flag is derived here instead of being a second thing to get right.
        is_boss: form.node_type === 'boss',
      }

      if (level) {
        await levelsApi.update(level.id, payload)
      } else {
        await levelsApi.create(payload)
      }
      toast.success(level ? 'Niveau mis à jour.' : 'Niveau créé.')
      onSaved()
    } catch (error) {
      setErrors(error.errors || {})
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <Modal
      wide
      title={level ? `Modifier ${level.title}` : 'Nouveau niveau'}
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
      <div className="stack" style={{ gap: 14 }}>
        <div className="filters">
          <Field label="Chapitre" className="grow" error={errorFor('chapter_id')}>
            <Select
              value={form.chapter_id}
              onChange={(value) => patch({ chapter_id: value })}
              placeholder="Choisir…"
              options={chapterOptions}
            />
          </Field>
          <Field label="Type de nœud" className="grow" error={errorFor('node_type')}>
            <Select value={form.node_type} onChange={(value) => patch({ node_type: value })} options={NODE_TYPES} />
          </Field>
        </div>

        {form.node_type === 'chest' && (
          <div className="alert info">
            Un coffre est un jalon visuel : il n’est pas jouable et n’a pas besoin de questions.
          </div>
        )}
        {form.node_type === 'boss' && (
          <div className="alert warning">
            Un boss se joue au chrono et coûte une vie en cas d’échec. C’est le seul nœud qui bloque la région.
          </div>
        )}

        <Field label="Titre" error={errorFor('title')}>
          <input type="text" value={form.title} onChange={(e) => patch({ title: e.target.value })} autoFocus />
        </Field>

        <Field label="Description" error={errorFor('description')}>
          <textarea value={form.description} onChange={(e) => patch({ description: e.target.value })} />
        </Field>

        <div className="filters">
          <Field label="Difficulté">
            <Select value={form.difficulty} onChange={(value) => patch({ difficulty: value })} options={DIFFICULTIES} />
          </Field>
          <Field label="Questions tirées" error={errorFor('questions_count')} hint="1 à 50">
            <input
              type="number"
              min="1"
              max="50"
              value={form.questions_count}
              onChange={(e) => patch({ questions_count: e.target.value })}
            />
          </Field>
          <Field label="Score de réussite (%)" error={errorFor('passing_score')}>
            <input
              type="number"
              min="0"
              max="100"
              value={form.passing_score}
              onChange={(e) => patch({ passing_score: e.target.value })}
            />
          </Field>
        </div>

        <div className="filters">
          <Field label="XP">
            <input type="number" min="0" value={form.xp_reward} onChange={(e) => patch({ xp_reward: e.target.value })} />
          </Field>
          <Field label="Pièces">
            <input type="number" min="0" value={form.coins_reward} onChange={(e) => patch({ coins_reward: e.target.value })} />
          </Field>
          <Field label="Gemmes">
            <input type="number" min="0" value={form.gems_reward} onChange={(e) => patch({ gems_reward: e.target.value })} />
          </Field>
          <Field label="État">
            <label className="checkbox">
              <input type="checkbox" checked={form.is_active} onChange={(e) => patch({ is_active: e.target.checked })} />
              Actif
            </label>
          </Field>
        </div>
      </div>
    </Modal>
  )
}

export function LevelsPage() {
  const toast = useToast()
  const [params] = useSearchParams()

  const [regionId, setRegionId] = useState('')
  const [chapterId, setChapterId] = useState('')
  const [incompleteOnly, setIncompleteOnly] = useState(params.get('incomplets') === '1')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [pendingDelete, setPendingDelete] = useState(null)
  const [busy, setBusy] = useState(false)

  const regions = useResource((signal) => regionsApi.list({ per_page: 100 }, signal), [])
  const chapters = useResource(
    (signal) => chaptersApi.list({ region_id: regionId, per_page: 100 }, signal),
    [regionId],
  )
  const list = useResource(
    (signal) =>
      levelsApi.list(
        { region_id: regionId, chapter_id: chapterId, incomplete_only: incompleteOnly ? 1 : '', page, per_page: 50 },
        signal,
      ),
    [regionId, chapterId, incompleteOnly, page],
  )

  const items = list.data?.items || []
  const chapterOptions = (chapters.data?.items || []).map((c) => ({ value: String(c.id), label: c.name }))

  async function move(from, to) {
    if (!chapterId) {
      toast.info('Filtre sur un chapitre avant de réordonner : l’ordre est propre à chaque chapitre.')
      return
    }

    const ids = items.map((item) => item.id)
    const [moved] = ids.splice(from, 1)
    ids.splice(to, 0, moved)

    setBusy(true)
    try {
      await levelsApi.reorder(ids)
      list.reload()
    } catch (error) {
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  async function confirmDelete() {
    setBusy(true)
    try {
      await levelsApi.remove(pendingDelete.id)
      toast.success('Niveau archivé.')
      setPendingDelete(null)
      list.reload()
    } catch (error) {
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <>
      <PageHeader title="Niveaux" subtitle="Les étapes jouables du parcours, et leur état de santé.">
        <button type="button" className="btn primary" onClick={() => setEditing({})}>
          + Nouveau niveau
        </button>
      </PageHeader>

      <div className="page-body stack">
        <Card>
          <div className="filters">
            <Field label="Région" className="grow">
              <Select
                value={regionId}
                onChange={(value) => {
                  setRegionId(value)
                  setChapterId('')
                  setPage(1)
                }}
                placeholder="Toutes"
                options={(regions.data?.items || []).map((r) => ({ value: String(r.id), label: r.name }))}
              />
            </Field>
            <Field label="Chapitre" className="grow">
              <Select
                value={chapterId}
                onChange={(value) => {
                  setChapterId(value)
                  setPage(1)
                }}
                placeholder="Tous"
                options={chapterOptions}
              />
            </Field>
            <Field label="&nbsp;">
              <label className="checkbox">
                <input
                  type="checkbox"
                  checked={incompleteOnly}
                  onChange={(e) => {
                    setIncompleteOnly(e.target.checked)
                    setPage(1)
                  }}
                />
                Injouables seulement
              </label>
            </Field>
          </div>
        </Card>

        {incompleteOnly && (
          <div className="alert warning">
            Ces niveaux tirent plus de questions qu’ils n’en possèdent. Tant que le compte n’y est pas, un joueur qui
            les atteint est bloqué.
          </div>
        )}

        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="🎯" title={incompleteOnly ? 'Tous les niveaux sont jouables' : 'Aucun niveau'}>
              {incompleteOnly
                ? 'Chaque niveau possède assez de questions pour démarrer.'
                : 'Crée un niveau pour commencer à y ajouter des questions.'}
            </EmptyState>
          ) : (
            <>
              <div className="table-wrap">
                <table className="data">
                  <thead>
                    <tr>
                      <th style={{ width: 60 }}>Ordre</th>
                      <th>Niveau</th>
                      <th>Emplacement</th>
                      <th>Type</th>
                      <th>Questions</th>
                      <th>État</th>
                      <th />
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((level, index) => (
                      <tr key={level.id}>
                        <td>
                          <ReorderButtons index={index} total={items.length} onMove={move} disabled={busy || !chapterId} />
                        </td>
                        <td>
                          <div className="question-text">{level.title}</div>
                          <div className="faint small">
                            {DIFFICULTY_LABEL[level.difficulty] || level.difficulty} · réussite à {level.passing_score} %
                          </div>
                        </td>
                        <td className="muted small nowrap">
                          {level.region_name || '—'}
                          <div className="faint">{level.chapter_name || '—'}</div>
                        </td>
                        <td>
                          <Badge tone={level.node_type === 'boss' ? 'warning' : level.node_type === 'chest' ? 'info' : ''}>
                            {NODE_TYPE_LABEL[level.node_type] || level.node_type}
                          </Badge>
                        </td>
                        <td>
                          {level.node_type === 'chest' ? (
                            <span className="faint small">—</span>
                          ) : (
                            <QuotaMeter available={level.available_questions} required={level.questions_count} />
                          )}
                        </td>
                        <td>
                          {!level.is_active ? (
                            <Badge tone="danger">Archivé</Badge>
                          ) : level.is_playable === false && level.node_type !== 'chest' ? (
                            <Badge tone="danger">Injouable</Badge>
                          ) : (
                            <Badge tone="ok">OK</Badge>
                          )}
                        </td>
                        <td className="actions">
                          <Link className="btn sm" to={`/questions?level=${level.id}`}>
                            Questions
                          </Link>{' '}
                          <button type="button" className="btn sm" onClick={() => setEditing(level)}>
                            Modifier
                          </button>{' '}
                          <button type="button" className="btn sm ghost" onClick={() => setPendingDelete(level)}>
                            Archiver
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

      {editing && (
        <LevelForm
          level={editing.id ? editing : null}
          chapterOptions={chapterOptions}
          defaultChapterId={chapterId}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            list.reload()
          }}
        />
      )}

      {pendingDelete && (
        <ConfirmDialog
          title="Archiver ce niveau ?"
          message={`${pendingDelete.title} sortira du parcours. Ses questions et l’historique des joueurs sont conservés.`}
          confirmLabel="Archiver"
          busy={busy}
          onConfirm={confirmDelete}
          onCancel={() => setPendingDelete(null)}
        />
      )}
    </>
  )
}
