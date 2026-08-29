import { useState } from 'react'
import { regions as regionsApi } from '../api/endpoints'
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
} from '../ui/components'
import { ReorderButtons } from '../ui/ReorderButtons'
import { useToast } from '../ui/ToastContext'

function RegionForm({ region, onClose, onSaved }) {
  const toast = useToast()
  const [form, setForm] = useState({
    name: region?.name || '',
    description: region?.description || '',
    intro_title: region?.intro_title || '',
    intro_text: region?.intro_text || '',
    image: region?.image || '',
    map_image: region?.map_image || '',
    is_active: region?.is_active ?? true,
  })
  const [errors, setErrors] = useState({})
  const [busy, setBusy] = useState(false)

  const patch = (changes) => setForm((current) => ({ ...current, ...changes }))
  const errorFor = (field) => (Array.isArray(errors[field]) ? errors[field][0] : errors[field])

  async function submit() {
    setBusy(true)
    setErrors({})

    try {
      if (region) {
        await regionsApi.update(region.id, form)
      } else {
        await regionsApi.create(form)
      }
      toast.success(region ? 'Région mise à jour.' : 'Région créée.')
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
      title={region ? `Modifier ${region.name}` : 'Nouvelle région'}
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
        <Field label="Nom" error={errorFor('name')} hint="L’identifiant technique est généré automatiquement">
          <input type="text" value={form.name} onChange={(e) => patch({ name: e.target.value })} autoFocus />
        </Field>

        <Field label="Description" error={errorFor('description')}>
          <textarea value={form.description} onChange={(e) => patch({ description: e.target.value })} />
        </Field>

        <Field label="Titre d’introduction" error={errorFor('intro_title')} hint="Affiché la première fois que le joueur entre dans la région">
          <input type="text" value={form.intro_title} onChange={(e) => patch({ intro_title: e.target.value })} />
        </Field>

        <Field label="Texte d’introduction" error={errorFor('intro_text')}>
          <textarea value={form.intro_text} onChange={(e) => patch({ intro_text: e.target.value })} />
        </Field>

        <div className="filters">
          <Field label="Image" className="grow" error={errorFor('image')}>
            <input type="text" value={form.image} onChange={(e) => patch({ image: e.target.value })} />
          </Field>
          <Field label="Image de carte" className="grow" error={errorFor('map_image')}>
            <input type="text" value={form.map_image} onChange={(e) => patch({ map_image: e.target.value })} />
          </Field>
        </div>

        <label className="checkbox">
          <input type="checkbox" checked={form.is_active} onChange={(e) => patch({ is_active: e.target.checked })} />
          Région active
        </label>
      </div>
    </Modal>
  )
}

export function RegionsPage() {
  const toast = useToast()
  const [editing, setEditing] = useState(null)
  const [pendingDelete, setPendingDelete] = useState(null)
  const [busy, setBusy] = useState(false)

  const list = useResource((signal) => regionsApi.list({ per_page: 100 }, signal), [])
  const items = list.data?.items || []

  async function move(from, to) {
    const ids = items.map((item) => item.id)
    const [moved] = ids.splice(from, 1)
    ids.splice(to, 0, moved)

    setBusy(true)
    try {
      await regionsApi.reorder(ids)
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
      await regionsApi.remove(pendingDelete.id)
      toast.success('Région archivée.')
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
      <PageHeader title="Régions" subtitle="Les mondes visibles sur la carte. L’ordre détermine la progression.">
        <button type="button" className="btn primary" onClick={() => setEditing({})}>
          + Nouvelle région
        </button>
      </PageHeader>

      <div className="page-body stack">
        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="🗺️" title="Aucune région">
              Crée la première région pour commencer la carte.
            </EmptyState>
          ) : (
            <div className="table-wrap">
              <table className="data">
                <thead>
                  <tr>
                    <th style={{ width: 60 }}>Ordre</th>
                    <th>Région</th>
                    <th className="num">Chapitres</th>
                    <th className="num">Niveaux</th>
                    <th>État</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {items.map((region, index) => (
                    <tr key={region.id}>
                      <td>
                        <ReorderButtons index={index} total={items.length} onMove={move} disabled={busy} />
                      </td>
                      <td>
                        <div className="question-text">{region.name}</div>
                        <div className="faint small mono">{region.slug}</div>
                      </td>
                      <td className="num">{region.chapters_count ?? '—'}</td>
                      <td className="num">{region.levels_count ?? '—'}</td>
                      <td>
                        {region.is_active ? <Badge tone="ok">Active</Badge> : <Badge tone="danger">Inactive</Badge>}
                      </td>
                      <td className="actions">
                        <button type="button" className="btn sm" onClick={() => setEditing(region)}>
                          Modifier
                        </button>{' '}
                        <button type="button" className="btn sm ghost" onClick={() => setPendingDelete(region)}>
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
        <RegionForm
          region={editing.id ? editing : null}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            list.reload()
          }}
        />
      )}

      {pendingDelete && (
        <ConfirmDialog
          title="Archiver cette région ?"
          message={`${pendingDelete.name} disparaîtra de la carte. Rien n’est supprimé : la progression des joueurs qui y sont passés reste intacte.`}
          confirmLabel="Archiver"
          busy={busy}
          onConfirm={confirmDelete}
          onCancel={() => setPendingDelete(null)}
        />
      )}
    </>
  )
}
