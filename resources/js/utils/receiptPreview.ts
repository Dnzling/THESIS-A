export async function openReceiptPreview(loadReceipt: () => Promise<Blob>): Promise<void> {
  const preview = window.open('about:blank', '_blank')
  if (!preview) throw new Error('Allow popups to open and print the receipt.')

  try {
    const pdf = await loadReceipt()
    if (!pdf.type.toLowerCase().includes('application/pdf')) {
      throw new Error('The server did not return a PDF receipt. Please sign in again and retry.')
    }

    const url = URL.createObjectURL(pdf)
    preview.opener = null
    preview.location.replace(url)
    window.setTimeout(() => URL.revokeObjectURL(url), 60 * 60 * 1000)
  } catch (error) {
    preview.close()
    throw error
  }
}
