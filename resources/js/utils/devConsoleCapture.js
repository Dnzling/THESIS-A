const SENSITIVE_KEY = /token|password|secret|authorization|api.?key/i

const redact = (value, seen = new WeakSet()) => {
  if (typeof value === 'string') {
    return value
      .replace(/Bearer\s+[^\s"']+/gi, 'Bearer [REDACTED]')
      .replace(/\b\d+\|[A-Za-z0-9_-]{20,}\b/g, '[REDACTED_TOKEN]')
  }

  if (value instanceof Error) return `${value.name}: ${value.message}\n${value.stack || ''}`
  if (!value || typeof value !== 'object') return value
  if (seen.has(value)) return '[Circular]'
  seen.add(value)

  if (Array.isArray(value)) return value.map(item => redact(item, seen))

  return Object.fromEntries(Object.entries(value).map(([key, item]) => [
    key,
    SENSITIVE_KEY.test(key) ? '[REDACTED]' : redact(item, seen),
  ]))
}

const format = value => {
  const safe = redact(value)
  if (typeof safe === 'string') return safe
  try {
    return JSON.stringify(safe)
  } catch {
    return String(safe)
  }
}

export function captureDevConsole() {
  if (!import.meta.env.DEV || typeof window === 'undefined' || window.__furnisyncConsoleCaptured) return
  if (window.localStorage.getItem('furnisync:debug-console-capture') !== 'true') return

  window.__furnisyncConsoleCaptured = true

  const viteOrigin = new URL(import.meta.url).origin
  for (const level of ['log', 'info', 'warn', 'error', 'debug']) {
    const original = console[level].bind(console)
    console[level] = (...args) => {
      original(...args)
      const message = args.map(format).join(' ')
      const payload = JSON.stringify({ level, message })
      try {
        navigator.sendBeacon(
          `${viteOrigin}/__fs_console_capture`,
          new Blob([payload], { type: 'text/plain;charset=UTF-8' }),
        )
      } catch {
        // Dev logging must never interfere with the application.
      }
    }
  }
}
