import { useState } from 'react'
import { chapters as chaptersApi, regions as regionsApi } from '../api/endpoints'
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
  Select,
} from '../ui/components'
import { ReorderButtons } from '../ui/ReorderButtons'
import { useToast } from '../ui/ToastContext'

function ChapterForm({ chapter, regionOptions, defaultRegionId, onClose, onSaved }) {
  const toast = useToast()
  const [form, setForm] = useState({
    region_id: chapter?.region_id ? String(chapter.region_id) : defaultRegionId || '',
    name: chapter?.name || '',
    description: chapter?.description || '',
    image: chapter?.image || '',
    is_active: chapter?.is_active ?? true,
  })
  const [errors, setErrors] = useState({})
  const [busy, setBusy] = useState(false)

  const patch = (changes) => setForm((current) => ({ ...current, ...changes }))
  const errorFor = (field) => (Array.isArray(errors[field]) ? errors[field][0] : errors[field])

  async function submit() {
    setBusy(true)
    setErrors({})

    try {
      const payload = { ...form, region_id: Number(form.region_id) }

      if (chapter) {
        await chaptersApi.update(chapter.id, payload)
      } else {
        await chaptersApi.create(payload)
      }
      toast.success(chapter ? 'Chapitre mis à jour.' : 'Chapitre créé.')
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
      title={chapter ? `Modifier ${chapter.name}` : 'Nouveau chapitre'}
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
        <Field label="Région" error={errorFor('region_id')}>
          <Select
            value={form.region_id}
            onChange={(value) => patch({ region_id: value })}
            placeholder="Choisir…"
            options={regionOptions}
          />
        </Field>

        <Field label="Nom" error={errorFor('name')} hint="Une commune ou une zone : Plateau, Cocody, Yopougon…">
          <input type="text" value={form.name} onChange={(e) => patch({ name: e.target.value })} autoFocus />
        </Field>

        <Field label="Description" error={errorFor('description')}>
          <textarea value={form.description} onChange={(e) => patch({ description: e.target.value })} />
        </Field>

        <Field label="Image" error={errorFor('image')}>
          <input type="text" value={form.image} onChange={(e) => patch({ image: e.target.value })} />
        </Field>

        <label className="checkbox">
          <input type="checkbox" checked={form.is_active} onChange={(e) => patch({ is_active: e.target.checked })} />
          Chapitre actif
        </label>
      </div>
    </Modal>
  )
}

export function ChaptersPage() {
  const toast = useToast()
  const [regionId, setRegionId] = useState('')
  const [editing, setEditing] = useState(null)
  const [pendingDelete, setPendingDelete] = useState(null)
  const [busy, setBusy] = useState(false)

  const regions = useResource((signal) => regionsApi.list({ per_page: 100 }, signal), [])
  const list = useResource(
    (signal) => chaptersApi.list({ region_id: regionId, per_page: 100 }, signal),
    [regionId],
  )

  const items = list.data?.items || []
  const regionOptions = (regions.data?.items || []).map((r) => ({ value: String(r.id), label: r.name }))

  async function move(from, to) {
    if (!regionId) {
      toast.info('Filtre sur une région avant de réordonner : l’ordre est propre à chaque région.')
      return
    }

    const ids = items.map((item) => item.id)
    const [moved] = ids.splice(from, 1)
    ids.splice(to, 0, moved)

    setBusy(true)
    try {
      await chaptersApi.reorder(ids)
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
      await chaptersApi.remove(pendingDelete.id)
      toast.success('Chapitre archivé.')
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
      <PageHeader
        title="Chapitres"
        subtitle="Les zones internes d’une région. Le joueur ne les choisit jamais : il les traverse."
      >
        <button type="button" className="btn primary" onClick={() => setEditing({})}>
          + Nouveau chapitre
        </button>
      </PageHeader>

      <div className="page-body stack">
        <Card>
          <div className="filters">
            <Field label="Région" className="grow">
              <Select value={regionId} onChange={setRegionId} placeholder="Toutes les régions" options={regionOptions} />
            </Field>
          </div>
          {!regionId && (
            <p className="faint small" style={{ marginTop: 8 }}>
              Choisis une région pour pouvoir réordonner les chapitres.
            </p>
          )}
        </Card>

        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="🏘️" title="Aucun chapitre">
              Crée un chapitre pour y accrocher des niveaux.
            </EmptyState>
          ) : (
            <div className="table-wrap">
              <table className="data">
                <thead>
                  <tr>
                    <th style={{ width: 60 }}>Ordre</th>
                    <th>Chapitre</th>
                    <th>Région</th>
                    <th className="num">Niveaux</th>
                    <th>État</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {items.map((chapter, index) => (
                    <tr key={chapter.id}>
                      <td>
                        <ReorderButtons index={index} total={items.length} onMove={move} disabled={busy || !regionId} />
                      </td>
                      <td>
                        <div className="question-text">{chapter.name}</div>
                        <div className="faint small mono">{chapter.slug}</div>
                      </td>
                      <td className="muted small">{chapter.region_name || '—'}</td>
                      <td className="num">{chapter.levels_count ?? '—'}</td>
                      <td>
                        {chapter.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="danger">Inactif</Badge>}
                      </td>
                      <td className="actions">
                        <button type="button" className="btn sm" onClick={() => setEditing(chapter)}>
                          Modifier
                        </button>{' '}
                        <button type="button" className="btn sm ghost" onClick={() => setPendingDelete(chapter)}>
                          Archiver
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Card>
      </div>

      {editing && (
        <ChapterForm
          chapter={editing.id ? editing : null}
          regionOptions={regionOptions}
          defaultRegionId={regionId}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            list.reload()
          }}
        />
      )}

      {pendingDelete && (
        <ConfirmDialog
          title="Archiver ce chapitre ?"
          message={`${pendingDelete.name} et ses niveaux disparaîtront du parcours. La progression des joueurs reste conservée.`}
          confirmLabel="Archiver"
          busy={busy}
          onConfirm={confirmDelete}
          onCancel={() => setPendingDelete(null)}
        />
      )}
    </>
  )
}
