const TOKEN_KEY = 'ivq_admin_token'

export const tokenStore = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (token) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

/**
 * Carries what the server actually said. Every screen can render `message`
 * directly: the API writes its 422s in French, for humans.
 */
export class ApiError extends Error {
  constructor(message, { status = 0, errors = {}, payload = null } = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
    this.payload = payload
  }

  /** First validation message for a field, if the server flagged it. */
  fieldError(field) {
    const value = this.errors?.[field]
    return Array.isArray(value) ? value[0] : value || null
  }

  get isValidation() {
    return this.status === 422
  }

  get isUnauthenticated() {
    return this.status === 401
  }

  get isForbidden() {
    return this.status === 403
  }
}

// A 401 anywhere means the token died. Rather than teach every screen to handle
// it, the shell subscribes once and logs out globally.
let onUnauthenticated = () => {}
export function setUnauthenticatedHandler(handler) {
  onUnauthenticated = handler
}

function buildUrl(path, params) {
  const url = `/api/v1${path}`
  if (!params) return url

  const search = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null || value === '') continue
    search.append(key, value)
  }

  const query = search.toString()
  return query ? `${url}?${query}` : url
}

async function request(method, path, { body, params, signal } = {}) {
  const token = tokenStore.get()

  let response
  try {
    response = await fetch(buildUrl(path, params), {
      method,
      signal,
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    })
  } catch (error) {
    if (error.name === 'AbortError') throw error
    throw new ApiError(
      "Impossible de joindre le serveur. Vérifie que l'API Laravel tourne.",
      { status: 0 },
    )
  }

  if (response.status === 204) return null

  let payload = null
  try {
    payload = await response.json()
  } catch {
    payload = null
  }

  if (!response.ok) {
    if (response.status === 401) onUnauthenticated()

    throw new ApiError(payload?.message || `Erreur ${response.status}.`, {
      status: response.status,
      errors: payload?.errors || {},
      payload,
    })
  }

  return payload
}

/**
 * Single-resource routes answer `{ success, message, data }`; the useful part
 * is `data`. Paginated routes answer Laravel's raw `{ data, links, meta }`,
 * which callers want whole — hence the two helpers.
 */
export const api = {
  /** Unwraps `data` from the standard envelope. */
  async one(method, path, options) {
    const payload = await request(method, path, options)
    return payload && 'data' in payload ? payload.data : payload
  },

  /** Keeps `{ data, meta }` so the caller can paginate. */
  async page(path, params, signal) {
    const payload = await request('GET', path, { params, signal })
    return {
      items: payload?.data ?? [],
      meta: payload?.meta ?? { current_page: 1, last_page: 1, total: 0 },
    }
  },

  get: (path, options) => api.one('GET', path, options),
  post: (path, body, options) => api.one('POST', path, { body, ...options }),
  put: (path, body, options) => api.one('PUT', path, { body, ...options }),
  patch: (path, body, options) => api.one('PATCH', path, { body, ...options }),
  del: (path, options) => api.one('DELETE', path, options),
}
