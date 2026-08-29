import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams, Link } from 'react-router-dom'
import { categories as categoriesApi, chapters as chaptersApi, levels as levelsApi, questions as questionsApi, regions as regionsApi } from '../api/endpoints'
import { useResource } from '../hooks/useResource'
import { Card, Field, Loading, PageHeader, Select, ErrorState, QuotaMeter } from '../ui/components'
import { useToast } from '../ui/ToastContext'
import { DIFFICULTIES, QUESTION_TYPES } from '../lib/constants'

const CONTEXT_KEY = 'ivq_editor_context'
const MIN_ANSWERS = 2
const MAX_ANSWERS = 6

function emptyAnswers() {
  return [
    { key: 'a', answer_text: '', is_correct: true },
    { key: 'b', answer_text: '', is_correct: false },
    { key: 'c', answer_text: '', is_correct: false },
    { key: 'd', answer_text: '', is_correct: false },
  ]
}

function blankForm() {
  return {
    question_text: '',
    type: 'text',
    difficulty: 'easy',
    explanation: '',
    image: '',
    audio: '',
    points: 10,
    xp_reward: 5,
    time_limit: 20,
    is_active: true,
    answers: emptyAnswers(),
  }
}

/** The level/category pair survives reloads: it is the same for dozens of rows. */
function loadContext() {
  try {
    return JSON.parse(localStorage.getItem(CONTEXT_KEY)) || {}
  } catch {
    return {}
  }
}

export function QuestionEditorPage() {
  const { id } = useParams()
  const isEditing = Boolean(id)
  const navigate = useNavigate()
  const toast = useToast()

  const saved = loadContext()
  const [regionId, setRegionId] = useState(saved.regionId || '')
  const [chapterId, setChapterId] = useState(saved.chapterId || '')
  const [levelId, setLevelId] = useState(saved.levelId || '')
  const [categoryId, setCategoryId] = useState(saved.categoryId || '')

  const [form, setForm] = useState(blankForm)
  const [errors, setErrors] = useState({})
  const [busy, setBusy] = useState(false)
  const [recent, setRecent] = useState([])

  const questionRef = useRef(null)

  const regions = useResource((signal) => regionsApi.list({ per_page: 100 }, signal), [])
  const chapters = useResource(
    (signal) => chaptersApi.list({ region_id: regionId, per_page: 100 }, signal),
    [regionId],
    { enabled: Boolean(regionId) },
  )
  const levels = useResource(
    (signal) => levelsApi.list({ chapter_id: chapterId, per_page: 100 }, signal),
    [chapterId],
    { enabled: Boolean(chapterId) },
  )
  const categories = useResource((signal) => categoriesApi.list({}, signal), [])
  const existing = useResource((signal) => questionsApi.show(id, signal), [id], { enabled: isEditing })

  useEffect(() => {
    if (isEditing) return
    localStorage.setItem(CONTEXT_KEY, JSON.stringify({ regionId, chapterId, levelId, categoryId }))
  }, [isEditing, regionId, chapterId, levelId, categoryId])

  // Loading an existing question also restores its place in the tree, so the
  // breadcrumbs above the form stay meaningful.
  useEffect(() => {
    const question = existing.data
    if (!question) return

    setForm({
      question_text: question.question_text || '',
      type: question.type || 'text',
      difficulty: question.difficulty || 'easy',
      explanation: question.explanation || '',
      image: question.image || '',
      audio: question.audio || '',
      points: question.points ?? 10,
      xp_reward: question.xp_reward ?? 5,
      time_limit: question.time_limit ?? 20,
      is_active: question.is_active ?? true,
      answers: (question.answers || []).map((answer) => ({
        key: `saved-${answer.id}`,
        id: answer.id,
        answer_text: answer.answer_text,
        is_correct: answer.is_correct,
      })),
    })
    setLevelId(String(question.level_id))
    setCategoryId(question.category_id ? String(question.category_id) : '')
  }, [existing.data])

  const levelOptions = useMemo(
    () =>
      (levels.data?.items || []).map((level) => ({
        value: String(level.id),
        label: `${level.title}${level.node_type === 'boss' ? ' (boss)' : ''}`,
      })),
    [levels.data],
  )

  const selectedLevel = useMemo(
    () => (levels.data?.items || []).find((level) => String(level.id) === String(levelId)) || null,
    [levels.data, levelId],
  )

  function patch(changes) {
    setForm((current) => ({ ...current, ...changes }))
  }

  function patchAnswer(index, changes) {
    setForm((current) => ({
      ...current,
      answers: current.answers.map((answer, i) => (i === index ? { ...answer, ...changes } : answer)),
    }))
  }

  function markCorrect(index) {
    setForm((current) => ({
      ...current,
      answers: current.answers.map((answer, i) => ({ ...answer, is_correct: i === index })),
    }))
  }

  function addAnswer() {
    setForm((current) =>
      current.answers.length >= MAX_ANSWERS
        ? current
        : { ...current, answers: [...current.answers, { key: `new-${Date.now()}`, answer_text: '', is_correct: false }] },
    )
  }

  function removeAnswer(index) {
    setForm((current) => {
      if (current.answers.length <= MIN_ANSWERS) return current

      const answers = current.answers.filter((_, i) => i !== index)
      // Dropping the correct answer would leave the question with none, which
      // the API rejects — promote the first remaining one instead.
      if (!answers.some((answer) => answer.is_correct)) answers[0].is_correct = true

      return { ...current, answers }
    })
  }

  const buildPayload = useCallback(() => {
    const answers = form.answers
      .map((answer, index) => ({
        ...(answer.id ? { id: answer.id } : {}),
        answer_text: answer.answer_text.trim(),
        is_correct: Boolean(answer.is_correct),
        order: index,
      }))
      .filter((answer) => answer.answer_text !== '')

    return {
      level_id: Number(levelId),
      category_id: categoryId ? Number(categoryId) : null,
      question_text: form.question_text.trim(),
      type: form.type,
      difficulty: form.difficulty,
      explanation: form.explanation.trim() || null,
      image: form.type === 'image' ? form.image.trim() || null : null,
      audio: form.type === 'audio' ? form.audio.trim() || null : null,
      points: Number(form.points),
      xp_reward: Number(form.xp_reward),
      time_limit: Number(form.time_limit),
      is_active: form.is_active,
      answers,
    }
  }, [form, levelId, categoryId])

  async function save({ andNext }) {
    if (!levelId) {
      setErrors({ level_id: 'Choisis un niveau.' })
      return
    }

    setBusy(true)
    setErrors({})

    try {
      const payload = buildPayload()

      if (isEditing) {
        await questionsApi.update(id, payload)
        toast.success('Question mise à jour.')
        navigate('/questions')
        return
      }

      const created = await questionsApi.create(payload)
      toast.success('Question enregistrée.')
      setRecent((current) => [created, ...current].slice(0, 12))

      if (andNext) {
        // Keep level, category and difficulty; clear only what changes per row.
        setForm((current) => ({ ...blankForm(), difficulty: current.difficulty, type: current.type }))
        questionRef.current?.focus()
      } else {
        navigate('/questions')
      }
    } catch (caught) {
      setErrors(caught.errors || {})
      toast.error(caught.message)
    } finally {
      setBusy(false)
    }
  }

  // Ctrl/Cmd+Enter saves and moves on — the whole point is not touching the mouse.
  useEffect(() => {
    function onKey(event) {
      if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault()
        if (!busy) save({ andNext: !isEditing })
      }
    }

    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  })

  const firstError = (field) => {
    const value = errors[field]
    return Array.isArray(value) ? value[0] : value
  }

  if (isEditing && existing.loading) {
    return (
      <>
        <PageHeader title="Modifier la question" />
        <div className="page-body">
          <Loading />
        </div>
      </>
    )
  }

  return (
    <>
      <PageHeader
        title={isEditing ? 'Modifier la question' : 'Saisir une question'}
        subtitle={
          isEditing
            ? 'Les identifiants des réponses sont conservés pour ne pas casser l’historique des joueurs.'
            : 'Le formulaire reste ouvert après enregistrement. Raccourci : Ctrl + Entrée.'
        }
      >
        <Link className="btn" to="/questions">
          Voir la liste
        </Link>
      </PageHeader>

      <div className="page-body">
        <ErrorState error={existing.error} />

        <div className="editor-layout">
          <div className="stack">
            <Card title="Emplacement">
              <div className="filters">
                <Field label="Région" className="grow">
                  <Select
                    value={regionId}
                    onChange={(value) => {
                      setRegionId(value)
                      setChapterId('')
                      setLevelId('')
                    }}
                    placeholder="Choisir…"
                    options={(regions.data?.items || []).map((r) => ({ value: String(r.id), label: r.name }))}
                  />
                </Field>

                <Field label="Chapitre" className="grow">
                  <Select
                    value={chapterId}
                    onChange={(value) => {
                      setChapterId(value)
                      setLevelId('')
                    }}
                    placeholder={regionId ? 'Choisir…' : 'Choisis une région'}
                    disabled={!regionId}
                    options={(chapters.data?.items || []).map((c) => ({ value: String(c.id), label: c.name }))}
                  />
                </Field>

                <Field label="Niveau" className="grow" error={firstError('level_id')}>
                  <Select
                    value={levelId}
                    onChange={setLevelId}
                    placeholder={chapterId ? 'Choisir…' : 'Choisis un chapitre'}
                    disabled={!chapterId}
                    options={levelOptions}
                  />
                </Field>

                <Field label="Catégorie" className="grow" hint="Optionnelle">
                  <Select
                    value={categoryId}
                    onChange={setCategoryId}
                    placeholder="Aucune"
                    options={(categories.data || []).map((c) => ({ value: String(c.id), label: c.name }))}
                  />
                </Field>
              </div>

              {selectedLevel && (
                <div className="row" style={{ marginTop: 12 }}>
                  <span className="small muted">Questions de ce niveau :</span>
                  <QuotaMeter available={selectedLevel.available_questions} required={selectedLevel.questions_count} />
                  {selectedLevel.is_playable === false && (
                    <span className="badge danger">Injouable tant qu’il en manque</span>
                  )}
                </div>
              )}
            </Card>

            <Card title="La question">
              <div className="stack" style={{ gap: 14 }}>
                <Field label="Énoncé" error={firstError('question_text')}>
                  <textarea
                    ref={questionRef}
                    value={form.question_text}
                    onChange={(e) => patch({ question_text: e.target.value })}
                    placeholder="Quelle est la capitale politique de la Côte d’Ivoire ?"
                    aria-invalid={Boolean(firstError('question_text'))}
                    autoFocus
                  />
                </Field>

                <div className="filters">
                  <Field label="Type">
                    <Select value={form.type} onChange={(value) => patch({ type: value })} options={QUESTION_TYPES} />
                  </Field>
                  <Field label="Difficulté">
                    <Select value={form.difficulty} onChange={(value) => patch({ difficulty: value })} options={DIFFICULTIES} />
                  </Field>
                </div>

                {form.type === 'image' && (
                  <Field label="Chemin de l’image" error={firstError('image')} hint="Ex. questions/basilique.jpg">
                    <input type="text" value={form.image} onChange={(e) => patch({ image: e.target.value })} />
                  </Field>
                )}

                {form.type === 'audio' && (
                  <Field label="Chemin du fichier audio" error={firstError('audio')} hint="Ex. questions/coupe-decale.mp3">
                    <input type="text" value={form.audio} onChange={(e) => patch({ audio: e.target.value })} />
                  </Field>
                )}
              </div>
            </Card>

            <Card
              title="Réponses"
              hint="Coche la bonne réponse · 2 à 6 propositions"
              actions={
                <button type="button" className="btn sm" onClick={addAnswer} disabled={form.answers.length >= MAX_ANSWERS}>
                  + Ajouter
                </button>
              }
            >
              {firstError('answers') && <div className="alert error" style={{ marginBottom: 10 }}>{firstError('answers')}</div>}

              {form.answers.map((answer, index) => (
                <div key={answer.key} className={`answer-row ${answer.is_correct ? 'correct' : ''}`}>
                  <input
                    type="radio"
                    name="correct-answer"
                    checked={Boolean(answer.is_correct)}
                    onChange={() => markCorrect(index)}
                    aria-label={`Réponse ${index + 1} correcte`}
                  />
                  <input
                    type="text"
                    value={answer.answer_text}
                    onChange={(e) => patchAnswer(index, { answer_text: e.target.value })}
                    placeholder={`Proposition ${index + 1}`}
                  />
                  <button
                    type="button"
                    className="btn ghost sm remove"
                    onClick={() => removeAnswer(index)}
                    disabled={form.answers.length <= MIN_ANSWERS}
                    aria-label="Retirer cette réponse"
                  >
                    ✕
                  </button>
                </div>
              ))}
            </Card>

            <Card title="Explication" hint="Ce que le joueur lit après avoir répondu">
              <Field
                error={firstError('explanation')}
                hint="C’est le moment le plus partagé du jeu. Un fait, une date, une anecdote."
              >
                <textarea
                  value={form.explanation}
                  onChange={(e) => patch({ explanation: e.target.value })}
                  placeholder="Yamoussoukro est capitale politique depuis 1983, ville natale de Félix Houphouët-Boigny."
                />
              </Field>
            </Card>

            <Card title="Réglages avancés">
              <div className="filters">
                <Field label="Points" error={firstError('points')}>
                  <input type="number" min="1" max="100" value={form.points} onChange={(e) => patch({ points: e.target.value })} />
                </Field>
                <Field label="XP" error={firstError('xp_reward')}>
                  <input type="number" min="0" max="100" value={form.xp_reward} onChange={(e) => patch({ xp_reward: e.target.value })} />
                </Field>
                <Field label="Temps limite (s)" hint="Appliqué en boss uniquement" error={firstError('time_limit')}>
                  <input type="number" min="5" max="120" value={form.time_limit} onChange={(e) => patch({ time_limit: e.target.value })} />
                </Field>
                <Field label="État">
                  <label className="checkbox">
                    <input type="checkbox" checked={form.is_active} onChange={(e) => patch({ is_active: e.target.checked })} />
                    Active
                  </label>
                </Field>
              </div>
            </Card>

            <div className="row end">
              {!isEditing && (
                <button type="button" className="btn" onClick={() => save({ andNext: false })} disabled={busy}>
                  Enregistrer et fermer
                </button>
              )}
              <button type="button" className="btn primary lg" onClick={() => save({ andNext: !isEditing })} disabled={busy}>
                {busy ? <span className="spinner" /> : isEditing ? 'Enregistrer' : 'Enregistrer et suivante'}
              </button>
            </div>
          </div>

          {!isEditing && (
            <Card title="Ajoutées dans cette session" hint={`${recent.length}`} bodyClass="tight">
              {recent.length === 0 ? (
                <div className="empty" style={{ padding: 28 }}>
                  <div className="empty-icon">✏️</div>
                  <p className="small">Les questions enregistrées s’empilent ici.</p>
                </div>
              ) : (
                recent.map((question) => (
                  <div className="recent-item" key={question.id}>
                    <div className="truncate" style={{ maxWidth: 250 }}>
                      {question.question_text}
                    </div>
                    <div className="recent-meta">
                      {question.level_title || '—'} ·{' '}
                      <Link to={`/questions/${question.id}`}>modifier</Link>
                    </div>
                  </div>
                ))
              )}
            </Card>
          )}
        </div>
      </div>
    </>
  )
}
