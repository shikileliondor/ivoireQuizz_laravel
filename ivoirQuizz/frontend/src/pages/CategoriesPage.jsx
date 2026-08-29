import { useState } from 'react'
import { categories as categoriesApi } from '../api/endpoints'
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
import { useToast } from '../ui/ToastContext'

function CategoryForm({ category, onClose, onSaved }) {
  const toast = useToast()
  const [form, setForm] = useState({
    name: category?.name || '',
    icon: category?.icon || '',
    color: category?.color || '#e07b39',
    is_active: category?.is_active ?? true,
  })
  const [errors, setErrors] = useState({})
  const [busy, setBusy] = useState(false)

  const patch = (changes) => setForm((current) => ({ ...current, ...changes }))
  const errorFor = (field) => (Array.isArray(errors[field]) ? errors[field][0] : errors[field])

  async function submit() {
    setBusy(true)
    setErrors({})

    try {
      if (category) {
        await categoriesApi.update(category.id, form)
      } else {
        await categoriesApi.create(form)
      }
      toast.success(category ? 'Catégorie mise à jour.' : 'Catégorie créée.')
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
      title={category ? `Modifier ${category.name}` : 'Nouvelle catégorie'}
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
        <Field label="Nom" error={errorFor('name')} hint="Histoire, Culture, Géographie, Sport…">
          <input type="text" value={form.name} onChange={(e) => patch({ name: e.target.value })} autoFocus />
        </Field>

        <div className="filters">
          <Field label="Icône" className="grow" error={errorFor('icon')} hint="Un emoji suffit">
            <input type="text" value={form.icon} onChange={(e) => patch({ icon: e.target.value })} placeholder="🏛️" />
          </Field>
          <Field label="Couleur" error={errorFor('color')}>
            <input type="text" value={form.color} onChange={(e) => patch({ color: e.target.value })} placeholder="#e07b39" />
          </Field>
        </div>

        <label className="checkbox">
          <input type="checkbox" checked={form.is_active} onChange={(e) => patch({ is_active: e.target.checked })} />
          Catégorie active
        </label>
      </div>
    </Modal>
  )
}

export function CategoriesPage() {
  const toast = useToast()
  const [editing, setEditing] = useState(null)
  const [pendingDelete, setPendingDelete] = useState(null)
  const [busy, setBusy] = useState(false)

  const list = useResource((signal) => categoriesApi.list({}, signal), [])
  const items = list.data || []

  async function confirmDelete() {
    setBusy(true)
    try {
      await categoriesApi.remove(pendingDelete.id)
      toast.success('Catégorie traitée.')
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
        title="Catégories"
        subtitle="Elles équilibrent le contenu. Le joueur ne les voit jamais comme un menu."
      >
        <button type="button" className="btn primary" onClick={() => setEditing({})}>
          + Nouvelle catégorie
        </button>
      </PageHeader>

      <div className="page-body stack">
        <ErrorState error={list.error} onRetry={list.reload} />

        <Card bodyClass="tight">
          {list.loading && !list.data ? (
            <Loading />
          ) : items.length === 0 ? (
            <EmptyState icon="🏷️" title="Aucune catégorie">
              Les catégories servent à répartir les thèmes dans un niveau.
            </EmptyState>
          ) : (
            <div className="table-wrap">
              <table className="data">
                <thead>
                  <tr>
                    <th>Catégorie</th>
                    <th className="num">Questions</th>
                    <th>État</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {items.map((category) => (
                    <tr key={category.id}>
                      <td>
                        <span style={{ marginRight: 8 }}>{category.icon}</span>
                        <span className="question-text">{category.name}</span>
                        {category.color && (
                          <span
                            aria-hidden="true"
                            style={{
                              display: 'inline-block',
                              width: 10,
                              height: 10,
                              borderRadius: 3,
                              background: category.color,
                              marginLeft: 8,
                              verticalAlign: 'middle',
                            }}
                          />
                        )}
                        <div className="faint small mono">{category.slug}</div>
                      </td>
                      <td className="num">{category.questions_count ?? 0}</td>
                      <td>
                        {category.is_active ? <Badge tone="ok">Active</Badge> : <Badge tone="danger">Inactive</Badge>}
                      </td>
                      <td className="actions">
                        <button type="button" className="btn sm" onClick={() => setEditing(category)}>
                          Modifier
                        </button>{' '}
                        <button type="button" className="btn sm ghost" onClick={() => setPendingDelete(category)}>
                          Supprimer
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
        <CategoryForm
          category={editing.id ? editing : null}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            list.reload()
          }}
        />
      )}

      {pendingDelete && (
        <ConfirmDialog
          title="Supprimer cette catégorie ?"
          message={
            pendingDelete.questions_count > 0
              ? `${pendingDelete.name} est utilisée par ${pendingDelete.questions_count} question(s). Elle sera désactivée plutôt que supprimée, pour ne pas déclasser ces questions.`
              : `${pendingDelete.name} n’est utilisée nulle part et sera supprimée définitivement.`
          }
          confirmLabel={pendingDelete.questions_count > 0 ? 'Désactiver' : 'Supprimer'}
          busy={busy}
          onConfirm={confirmDelete}
          onCancel={() => setPendingDelete(null)}
        />
      )}
    </>
  )
}
