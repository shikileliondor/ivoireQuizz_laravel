import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { chapters as chaptersApi, levels as levelsApi, questions as questionsApi, regions as regionsApi } from '../api/endpoints'
import { useResource } from '../hooks/useResource'
import { Badge, Card, EmptyState, Field, PageHeader, Select } from '../ui/components'
import { useToast } from '../ui/ToastContext'
import { DIFFICULTIES } from '../lib/constants'

const MAX_ROWS = 100
const VALID_DIFFICULTIES = new Set(DIFFICULTIES.map((d) => d.value))

const SAMPLE = `Quelle est la capitale politique ?\tYamoussoukro\tAbidjan\tBouaké\tSan Pedro\tCapitale depuis 1983.\teasy
Quel fleuve traverse Bouaké ?\tLe Bandama\tLe Sassandra\tLa Comoé\tLe Cavally\tLe Bandama est le plus long fleuve du pays.\tmedium`

/**
 * Accepts what a spreadsheet actually puts on the clipboard. Tabs win because
 * Excel and Google Sheets both paste tab-separated; semicolons are the common
 * French CSV fallback.
 */
function detectSeparator(text) {
  if (text.includes('\t')) return '\t'
  if (text.includes(';')) return ';'
  return ','
}

function parseRows(raw) {
  const separator = detectSeparator(raw)

  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line, index) => {
      const cells = line.split(separator).map((cell) => cell.trim())
      const [questionText, correct, ...rest] = cells

      // Trailing columns are optional: difficulty if it is one of the known
      // values, and the cell before it as the explanation.
      let difficulty = 'easy'
      let explanation = ''
      const wrong = [...rest]

      if (wrong.length && VALID_DIFFICULTIES.has(wrong[wrong.length - 1]?.toLowerCase())) {
        difficulty = wrong.pop().toLowerCase()
      }
      if (wrong.length > 1) {
        explanation = wrong.pop()
      }

      const distractors = wrong.filter(Boolean)
      const problems = []

      if (!questionText) problems.push('énoncé manquant')
      if (!correct) problems.push('bonne réponse manquante')
      if (distractors.length === 0) problems.push('au moins une mauvaise réponse est requise')
      if (distractors.length > 5) problems.push('6 propositions maximum')

      const texts = [correct, ...distractors].map((t) => t.toLowerCase())
      if (new Set(texts).size !== texts.length) problems.push('propositions en double')

      return {
        line: index + 1,
        questionText,
        correct,
        distractors,
        explanation,
        difficulty,
        problems,
      }
    })
}

export function QuestionImportPage() {
  const toast = useToast()
  const navigate = useNavigate()

  const [regionId, setRegionId] = useState('')
  const [chapterId, setChapterId] = useState('')
  const [levelId, setLevelId] = useState('')
  const [raw, setRaw] = useState('')
  const [busy, setBusy] = useState(false)
  const [serverError, setServerError] = useState(null)

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

  const rows = useMemo(() => (raw.trim() ? parseRows(raw) : []), [raw])
  const invalid = rows.filter((row) => row.problems.length > 0)
  const canSubmit = Boolean(levelId) && rows.length > 0 && invalid.length === 0 && rows.length <= MAX_ROWS

  async function submit() {
    setBusy(true)
    setServerError(null)

    try {
      const payload = rows.map((row) => ({
        question_text: row.questionText,
        difficulty: row.difficulty,
        ...(row.explanation ? { explanation: row.explanation } : {}),
        answers: [
          { answer_text: row.correct, is_correct: true },
          ...row.distractors.map((text) => ({ answer_text: text, is_correct: false })),
        ],
      }))

      const result = await questionsApi.import(Number(levelId), payload)
      toast.success(`${result.created} question(s) importée(s).`)
      navigate('/questions')
    } catch (error) {
      // The API imports all-or-nothing and names the offending row, so the
      // message is worth showing verbatim rather than summarising.
      setServerError(error.message)
      toast.error(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <>
      <PageHeader
        title="Import en masse"
        subtitle="Colle des lignes depuis Excel ou Google Sheets. Tout ou rien : si une ligne est invalide, aucune n’est créée."
      />

      <div className="page-body stack">
        <Card title="Niveau de destination">
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
            <Field label="Niveau" className="grow">
              <Select
                value={levelId}
                onChange={setLevelId}
                placeholder={chapterId ? 'Choisir…' : 'Choisis un chapitre'}
                disabled={!chapterId}
                options={(levels.data?.items || []).map((l) => ({ value: String(l.id), label: l.title }))}
              />
            </Field>
          </div>
        </Card>

        <Card title="Les lignes" hint="Une question par ligne, colonnes séparées par des tabulations">
          <div className="alert info" style={{ marginBottom: 12 }}>
            <strong>Ordre des colonnes :</strong> énoncé · <em>bonne réponse</em> · mauvaises réponses… · explication ·
            difficulté.
            <br />
            L’explication et la difficulté sont facultatives. La difficulté doit valoir{' '}
            <span className="mono">easy</span>, <span className="mono">medium</span>, <span className="mono">hard</span> ou{' '}
            <span className="mono">expert</span>.
          </div>

          <Field>
            <textarea
              value={raw}
              onChange={(e) => setRaw(e.target.value)}
              rows={10}
              className="mono"
              placeholder="Colle ici les lignes copiées depuis ton tableur…"
            />
          </Field>

          <div className="row" style={{ marginTop: 10 }}>
            <button type="button" className="btn sm" onClick={() => setRaw(SAMPLE)}>
              Insérer un exemple
            </button>
            <button type="button" className="btn sm ghost" onClick={() => setRaw('')} disabled={!raw}>
              Vider
            </button>
            <div className="spacer" />
            <span className="small muted">
              {rows.length} ligne{rows.length > 1 ? 's' : ''}
              {invalid.length > 0 && <span style={{ color: 'var(--danger)' }}> · {invalid.length} invalide(s)</span>}
            </span>
          </div>
        </Card>

        {serverError && <div className="alert error">{serverError}</div>}

        {rows.length > MAX_ROWS && (
          <div className="alert warning">
            {rows.length} lignes : le maximum est de {MAX_ROWS} par import. Découpe en plusieurs lots.
          </div>
        )}

        <Card title="Aperçu" hint="Ce qui sera créé" bodyClass="tight">
          {rows.length === 0 ? (
            <EmptyState icon="📋" title="Rien à prévisualiser">
              Colle des lignes ci-dessus pour les vérifier avant l’envoi.
            </EmptyState>
          ) : (
            <div className="table-wrap">
              <table className="data">
                <thead>
                  <tr>
                    <th style={{ width: 40 }}>#</th>
                    <th>Énoncé</th>
                    <th>Propositions</th>
                    <th>Difficulté</th>
                    <th>État</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.line}>
                      <td className="faint small">{row.line}</td>
                      <td>
                        <div className="truncate">{row.questionText || <em className="faint">vide</em>}</div>
                        {row.explanation && <div className="faint small truncate">{row.explanation}</div>}
                      </td>
                      <td>
                        {row.correct && <span className="answer-pill correct">{row.correct}</span>}
                        {row.distractors.map((text, i) => (
                          <span className="answer-pill" key={i}>
                            {text}
                          </span>
                        ))}
                      </td>
                      <td>
                        <Badge>{row.difficulty}</Badge>
                      </td>
                      <td>
                        {row.problems.length === 0 ? (
                          <Badge tone="ok">OK</Badge>
                        ) : (
                          <Badge tone="danger">{row.problems.join(', ')}</Badge>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Card>

        <div className="row end">
          <button type="button" className="btn primary lg" onClick={submit} disabled={!canSubmit || busy}>
            {busy ? <span className="spinner" /> : `Importer ${rows.length} question${rows.length > 1 ? 's' : ''}`}
          </button>
        </div>
      </div>
    </>
  )
}
