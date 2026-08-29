import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthProvider'
import { useAuth } from './auth/AuthContext'
import { ToastProvider } from './ui/ToastProvider'
import { Layout } from './ui/Layout'
import { Loading } from './ui/components'
import { LoginPage } from './pages/LoginPage'
import { DashboardPage } from './pages/DashboardPage'
import { QuestionEditorPage } from './pages/QuestionEditorPage'
import { QuestionsPage } from './pages/QuestionsPage'
import { QuestionImportPage } from './pages/QuestionImportPage'
import { ReportsPage } from './pages/ReportsPage'
import { RegionsPage } from './pages/RegionsPage'
import { ChaptersPage } from './pages/ChaptersPage'
import { LevelsPage } from './pages/LevelsPage'
import { CategoriesPage } from './pages/CategoriesPage'
import { PlayersPage } from './pages/PlayersPage'

function Gate() {
  const { status } = useAuth()

  if (status === 'checking') {
    return (
      <div className="login-screen">
        <Loading label="Vérification de la session…" />
      </div>
    )
  }

  if (status !== 'signed-in') {
    return <LoginPage />
  }

  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/questions" element={<QuestionsPage />} />
        <Route path="/questions/nouvelle" element={<QuestionEditorPage />} />
        <Route path="/questions/:id" element={<QuestionEditorPage />} />
        <Route path="/questions/import" element={<QuestionImportPage />} />
        <Route path="/signalements" element={<ReportsPage />} />
        <Route path="/regions" element={<RegionsPage />} />
        <Route path="/chapitres" element={<ChaptersPage />} />
        <Route path="/niveaux" element={<LevelsPage />} />
        <Route path="/categories" element={<CategoriesPage />} />
        <Route path="/joueurs" element={<PlayersPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  )
}

export default function App() {
  return (
    <BrowserRouter>
      <ToastProvider>
        <AuthProvider>
          <Gate />
        </AuthProvider>
      </ToastProvider>
    </BrowserRouter>
  )
}
