export type ResponseDialogSeverity = 'success' | 'error' | 'info' | 'warn'

export type ResponseDialogPayload = {
  severity: ResponseDialogSeverity
  title?: string
  message: string
  details?: string
}

const target = new EventTarget()
const EVENT_NAME = 'response-dialog'

export const showResponseDialog = (payload: ResponseDialogPayload) => {
  target.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: payload }))
}

export const onResponseDialog = (handler: (payload: ResponseDialogPayload) => void) => {
  const listener = (event: Event) => {
    const detail = (event as CustomEvent).detail as ResponseDialogPayload
    handler(detail)
  }
  target.addEventListener(EVENT_NAME, listener)
  return () => target.removeEventListener(EVENT_NAME, listener)
}
