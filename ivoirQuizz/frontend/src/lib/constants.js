export const DIFFICULTIES = [
  { value: 'easy', label: 'Facile' },
  { value: 'medium', label: 'Moyen' },
  { value: 'hard', label: 'Difficile' },
  { value: 'expert', label: 'Expert' },
]

export const QUESTION_TYPES = [
  { value: 'text', label: 'Texte' },
  { value: 'image', label: 'Image' },
  { value: 'audio', label: 'Audio' },
]

export const NODE_TYPES = [
  { value: 'level', label: 'Niveau' },
  { value: 'review', label: 'Révision' },
  { value: 'boss', label: 'Boss' },
  { value: 'chest', label: 'Coffre (non jouable)' },
]

export const REPORT_STATUSES = [
  { value: 'pending', label: 'En attente' },
  { value: 'reviewed', label: 'Examiné' },
  { value: 'fixed', label: 'Corrigé' },
  { value: 'rejected', label: 'Rejeté' },
]

export const DIFFICULTY_LABEL = Object.fromEntries(DIFFICULTIES.map((d) => [d.value, d.label]))
export const NODE_TYPE_LABEL = Object.fromEntries(NODE_TYPES.map((n) => [n.value, n.label]))
export const REPORT_STATUS_LABEL = Object.fromEntries(REPORT_STATUSES.map((s) => [s.value, s.label]))

export const REPORT_STATUS_TONE = {
  pending: 'warning',
  reviewed: 'info',
  fixed: 'ok',
  rejected: '',
}

export function formatDate(iso) {
  if (!iso) return '—'

  return new Date(iso).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

export function formatNumber(value) {
  return typeof value === 'number' ? value.toLocaleString('fr-FR') : '—'
}

/** Success rate colouring: very low usually means a broken answer key. */
export function successRateTone(rate) {
  if (rate === null || rate === undefined) return ''
  if (rate < 25) return 'danger'
  if (rate < 45) return 'warning'
  if (rate > 95) return 'info'
  return 'ok'
}
