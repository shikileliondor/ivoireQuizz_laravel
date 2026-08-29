import { useCallback, useEffect, useRef, useState } from 'react'

/**
 * Fetches once and on demand, aborting the in-flight request when the inputs
 * change so a slow first response can never overwrite a newer one.
 *
 * `deps` is the dependency list for the fetcher, exactly like useEffect.
 */
export function useResource(fetcher, deps = [], { enabled = true } = {}) {
  const [data, setData] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(enabled)
  const [reloadToken, setReloadToken] = useState(0)

  const fetcherRef = useRef(fetcher)
  fetcherRef.current = fetcher

  useEffect(() => {
    if (!enabled) {
      setLoading(false)
      return undefined
    }

    const controller = new AbortController()
    let cancelled = false

    setLoading(true)
    setError(null)

    fetcherRef
      .current(controller.signal)
      .then((result) => {
        if (!cancelled) setData(result)
      })
      .catch((caught) => {
        if (cancelled || caught.name === 'AbortError') return
        setError(caught)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
      controller.abort()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [...deps, enabled, reloadToken])

  const reload = useCallback(() => setReloadToken((token) => token + 1), [])

  return { data, error, loading, reload, setData }
}

/** Debounces a value so typing in a search box does not fire a request per key. */
export function useDebounced(value, delay = 350) {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(timer)
  }, [value, delay])

  return debounced
}
