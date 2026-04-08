import Swal from 'sweetalert2'

type AlertSeverity = 'success' | 'error' | 'warn' | 'warning' | 'info'

type AlertPayload = {
  severity: AlertSeverity
  summary: string
  detail?: string
}

const severityToIcon = (severity: AlertSeverity): 'success' | 'error' | 'warning' | 'info' => {
  if (severity === 'success') return 'success'
  if (severity === 'error') return 'error'
  if (severity === 'info') return 'info'
  return 'warning'
}

export function showAlert(payload: AlertPayload) {
  const icon = severityToIcon(payload.severity)
  return Swal.fire({
    icon,
    title: payload.summary,
    text: payload.detail || '',
    confirmButtonText: 'OK',
    heightAuto: false,
  })
}

export async function confirmAlert(payload: { title: string; text: string; confirmText?: string; cancelText?: string }) {
  const result = await Swal.fire({
    icon: 'warning',
    title: payload.title,
    text: payload.text,
    showCancelButton: true,
    confirmButtonText: payload.confirmText || 'Confirm',
    cancelButtonText: payload.cancelText || 'Cancel',
    heightAuto: false,
  })

  return result.isConfirmed
}
