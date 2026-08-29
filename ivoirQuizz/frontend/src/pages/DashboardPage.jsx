import { Link } from 'react-router-dom'
import { dashboard } from '../api/endpoints'
import { useResource } from '../hooks/useResource'
import { Badge, Card, EmptyState, ErrorState, Loading, PageHeader } from '../ui/components'
import { formatNumber, successRateTone } from '../lib/constants'

function Stat({ label, value, hint, tone = '' }) {
  return (
    <div className={`stat ${tone}`}>
      <div className="stat-label">{label}</div>
      <div className="stat-value">{value}</div>
      {hint && <div className="stat-hint">{hint}</div>}
    </div>
  )
}

function BalanceTable({ rows, emptyLabel }) {
  if (!rows?.length) {
    return <EmptyState icon="📈" title={emptyLabel}>Il faut des parties jouées pour mesurer quoi que ce soit.</EmptyState>
  }

  return (
    <div className="table-wrap">
      <table className="data">
        <thead>
          <tr>
            <th>Question</th>
            <th>Réussite</th>
            <th className="num">Réponses</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {rows.map((question) => {
            const rate = question.stats?.success_rate

            return (
              <tr key={question.id}>
                <td>
                  <div className="truncate">{question.question_text}</div>
                  <div className="faint small">{question.level_title || '—'}</div>
                </td>
                <td>
                  <Badge tone={successRateTone(rate)}>{rate === null || rate === undefined ? '—' : `${rate} %`}</Badge>
                </td>
                <td className="num">{formatNumber(question.stats?.times_answered)}</td>
                <td className="actions">
                  <Link className="btn sm" to={`/questions/${question.id}`}>
                    Ouvrir
                  </Link>
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}

export function DashboardPage() {
  const summary = useResource((signal) => dashboard.summary(signal), [])
  const balance = useResource((signal) => dashboard.questionBalance({ min_answers: 20, limit: 8 }, signal), [])
  const funnel = useResource((signal) => dashboard.levelFunnel({ limit: 8 }, signal), [])

  const data = summary.data
  const incomplete = data?.content?.incomplete_levels ?? 0

  return (
    <>
      <PageHeader
        title="Tableau de bord"
        subtitle="L’état du contenu, des joueurs et de la modération."
      >
        <Link className="btn primary" to="/questions/nouvelle">
          ✏️ Saisir une question
        </Link>
      </PageHeader>

      <div className="page-body stack">
        <ErrorState error={summary.error} onRetry={summary.reload} />

        {summary.loading && !data && <Loading />}

        {data && (
          <>
            {incomplete > 0 && (
              <div className="alert error">
                <strong>{incomplete} niveau{incomplete > 1 ? 'x' : ''} ne peu{incomplete > 1 ? 'vent' : 't'} pas démarrer.</strong>{' '}
                Ces niveaux tirent plus de questions qu’ils n’en possèdent : les joueurs sont bloqués dessus.{' '}
                <Link to="/niveaux?incomplets=1">Voir lesquels →</Link>
              </div>
            )}

            <section>
              <h2 style={{ marginBottom: 12 }}>Contenu</h2>
              <div className="grid cols-4">
                <Stat label="Questions actives" value={formatNumber(data.content.active_questions)} hint={`${formatNumber(data.content.questions)} au total`} />
                <Stat label="Niveaux" value={formatNumber(data.content.levels)} />
                <Stat label="Chapitres" value={formatNumber(data.content.chapters)} hint={`${formatNumber(data.content.regions)} régions`} />
                <Stat
                  label="Niveaux incomplets"
                  value={formatNumber(incomplete)}
                  hint={incomplete > 0 ? 'À réparer en priorité' : 'Tout est jouable'}
                  tone={incomplete > 0 ? 'alert' : 'ok'}
                />
              </div>
            </section>

            <section>
              <h2 style={{ marginBottom: 12 }}>Joueurs</h2>
              <div className="grid cols-4">
                <Stat label="Total" value={formatNumber(data.players.total)} />
                <Stat label="Nouveaux (7 j)" value={formatNumber(data.players.new_this_week)} />
                <Stat label="Actifs aujourd’hui" value={formatNumber(data.players.active_today)} />
                <Stat label="Actifs (7 j)" value={formatNumber(data.players.active_this_week)} />
              </div>
            </section>

            <section>
              <h2 style={{ marginBottom: 12 }}>Parties (7 derniers jours)</h2>
              <div className="grid cols-4">
                <Stat label="Aujourd’hui" value={formatNumber(data.gameplay.sessions_today)} />
                <Stat
                  label="Taux de réussite"
                  value={data.gameplay.success_rate === null ? '—' : `${data.gameplay.success_rate} %`}
                  hint={`${formatNumber(data.gameplay.completed_this_week)} réussies / ${formatNumber(data.gameplay.failed_this_week)} ratées`}
                />
                <Stat label="Abandons" value={formatNumber(data.gameplay.abandoned_this_week)} hint="Parties quittées en cours" />
                <Stat
                  label="Signalements"
                  value={formatNumber(data.moderation.pending_reports)}
                  hint={`${formatNumber(data.moderation.reports_this_week)} cette semaine`}
                  tone={data.moderation.pending_reports > 0 ? 'alert' : 'ok'}
                />
              </div>
            </section>

            <div className="grid cols-2">
              <Card
                title="Questions les plus ratées"
                hint="Souvent mal formulées, pas difficiles"
                bodyClass="tight"
              >
                {balance.loading ? <Loading /> : <BalanceTable rows={balance.data?.hardest} emptyLabel="Pas encore de données" />}
              </Card>

              <Card title="Questions les plus faciles" hint="Au-dessus de 95 %, elles n’apprennent rien" bodyClass="tight">
                {balance.loading ? <Loading /> : <BalanceTable rows={balance.data?.easiest} emptyLabel="Pas encore de données" />}
              </Card>
            </div>

            <Card title="Où les joueurs s’arrêtent" hint="Trié par abandons" bodyClass="tight">
              {funnel.loading ? (
                <Loading />
              ) : !funnel.data?.length ? (
                <EmptyState icon="🚧" title="Pas encore assez de parties">
                  Un niveau apparaît ici à partir de 5 tentatives.
                </EmptyState>
              ) : (
                <div className="table-wrap">
                  <table className="data">
                    <thead>
                      <tr>
                        <th>Niveau</th>
                        <th className="num">Tentatives</th>
                        <th className="num">Réussies</th>
                        <th className="num">Abandons</th>
                        <th className="num">Précision moy.</th>
                      </tr>
                    </thead>
                    <tbody>
                      {funnel.data.map((row) => (
                        <tr key={row.id}>
                          <td>{row.title}</td>
                          <td className="num">{formatNumber(row.attempts)}</td>
                          <td className="num">{formatNumber(row.completed)}</td>
                          <td className="num">
                            <Badge tone={row.abandoned > row.completed ? 'danger' : ''}>{row.abandoned}</Badge>
                          </td>
                          <td className="num">{row.avg_accuracy ?? '—'} %</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </Card>
          </>
        )}
      </div>
    </>
  )
}
