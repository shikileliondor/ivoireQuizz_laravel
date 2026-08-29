import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import { dashboard } from '../api/endpoints'
import { useResource } from '../hooks/useResource'

const SECTIONS = [
  {
    label: 'Pilotage',
    links: [{ to: '/', icon: '📊', label: 'Tableau de bord', end: true }],
  },
  {
    label: 'Contenu',
    links: [
      { to: '/questions/nouvelle', icon: '✏️', label: 'Saisir une question' },
      { to: '/questions', icon: '📚', label: 'Toutes les questions' },
      { to: '/questions/import', icon: '📥', label: 'Import en masse' },
      { to: '/signalements', icon: '🚩', label: 'Signalements', badge: 'reports' },
    ],
  },
  {
    label: 'Structure',
    links: [
      { to: '/regions', icon: '🗺️', label: 'Régions' },
      { to: '/chapitres', icon: '🏘️', label: 'Chapitres' },
      { to: '/niveaux', icon: '🎯', label: 'Niveaux' },
      { to: '/categories', icon: '🏷️', label: 'Catégories' },
    ],
  },
  {
    label: 'Communauté',
    links: [{ to: '/joueurs', icon: '👥', label: 'Joueurs' }],
  },
]

export function Layout() {
  const { user, signOut } = useAuth()

  // Drives the "unread" badge on the moderation queue. A failure here must not
  // take the whole shell down, so the error is simply ignored.
  const { data: summary } = useResource((signal) => dashboard.summary(signal), [])
  const pendingReports = summary?.moderation?.pending_reports ?? 0

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="sidebar-brand">
          <strong>IvoireQuiz</strong>
          <span>Administration</span>
        </div>

        <nav className="sidebar-nav">
          {SECTIONS.map((section) => (
            <div className="nav-section" key={section.label}>
              <div className="nav-section-label">{section.label}</div>
              {section.links.map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  end={link.end}
                  className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}
                >
                  <span className="nav-icon" aria-hidden="true">
                    {link.icon}
                  </span>
                  <span>{link.label}</span>
                  {link.badge === 'reports' && pendingReports > 0 && (
                    <span className="nav-count">{pendingReports}</span>
                  )}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="sidebar-user">
            <strong>{user?.name || 'Administrateur'}</strong>
            {user?.email}
          </div>
          <button type="button" className="btn sm block" onClick={() => signOut()}>
            Se déconnecter
          </button>
        </div>
      </aside>

      <main className="main">
        <Outlet />
      </main>
    </div>
  )
}
