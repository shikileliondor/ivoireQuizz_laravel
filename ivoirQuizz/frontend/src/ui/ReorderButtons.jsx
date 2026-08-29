/**
 * Up/down buttons rather than drag-and-drop: the API takes the full ordered
 * list either way, and arrows work with a keyboard and on a laggy connection.
 */
export function ReorderButtons({ index, total, onMove, disabled }) {
  return (
    <>
      <button
        type="button"
        className="btn ghost sm"
        onClick={() => onMove(index, index - 1)}
        disabled={disabled || index === 0}
        aria-label="Monter"
        title="Monter"
      >
        ↑
      </button>
      <button
        type="button"
        className="btn ghost sm"
        onClick={() => onMove(index, index + 1)}
        disabled={disabled || index === total - 1}
        aria-label="Descendre"
        title="Descendre"
      >
        ↓
      </button>
    </>
  )
}
