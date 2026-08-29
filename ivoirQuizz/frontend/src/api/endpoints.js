import { api } from './client'

export const auth = {
  login: (email, password) => api.post('/auth/login', { email, password }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/me'),
}

export const dashboard = {
  summary: (signal) => api.get('/admin/dashboard', { signal }),
  questionBalance: (params, signal) => api.get('/admin/stats/question-balance', { params, signal }),
  levelFunnel: (params, signal) => api.get('/admin/stats/level-funnel', { params, signal }),
}

/** Regions, chapters and levels share one CRUD shape, so they share one factory. */
function crud(resource) {
  return {
    list: (params, signal) => api.page(`/admin/${resource}`, params, signal),
    show: (id, signal) => api.get(`/admin/${resource}/${id}`, { signal }),
    create: (body) => api.post(`/admin/${resource}`, body),
    update: (id, body) => api.put(`/admin/${resource}/${id}`, body),
    remove: (id) => api.del(`/admin/${resource}/${id}`),
    reorder: (ids) => api.post(`/admin/${resource}/reorder`, { ids }),
  }
}

export const regions = crud('regions')
export const chapters = crud('chapters')
export const levels = crud('levels')

export const questions = {
  ...crud('questions'),
  import: (levelId, rows) => api.post('/admin/questions/import', { level_id: levelId, questions: rows }),
}

export const categories = {
  list: (params, signal) => api.get('/admin/categories', { params, signal }),
  create: (body) => api.post('/admin/categories', body),
  update: (id, body) => api.put(`/admin/categories/${id}`, body),
  remove: (id) => api.del(`/admin/categories/${id}`),
}

export const reports = {
  list: (params, signal) => api.page('/admin/reports', params, signal),
  show: (id, signal) => api.get(`/admin/reports/${id}`, { signal }),
  resolve: (id, body) => api.post(`/admin/reports/${id}/resolve`, body),
}

export const players = {
  list: (params, signal) => api.page('/admin/players', params, signal),
  show: (id, signal) => api.get(`/admin/players/${id}`, { signal }),
  update: (id, body) => api.patch(`/admin/players/${id}`, body),
}
