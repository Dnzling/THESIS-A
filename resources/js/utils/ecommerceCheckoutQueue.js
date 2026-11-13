export function parseCheckoutQueue(raw) {
  if (typeof raw !== 'string' || !raw.trim()) return []

  const seenStores = new Set()
  const seenItems = new Set()
  const groups = []

  for (const segment of raw.split(';')) {
    const match = /^(\d+):(\d+(?:\.\d+)*)$/.exec(segment)
    if (!match) return []

    const storeId = Number(match[1])
    const itemIds = match[2].split('.').map(Number)
    if (!Number.isSafeInteger(storeId) || storeId < 1 || seenStores.has(storeId)
      || itemIds.some((id) => !Number.isSafeInteger(id) || id < 1 || seenItems.has(id))
      || new Set(itemIds).size !== itemIds.length) return []

    seenStores.add(storeId)
    itemIds.forEach((id) => seenItems.add(id))
    groups.push({ storeId, itemIds })
  }

  return groups
}

export function encodeCheckoutQueue(groups) {
  return groups.map(({ storeId, itemIds }) => `${storeId}:${itemIds.join('.')}`).join(';')
}

export function remainingCheckoutQueue(groups, paidStoreId) {
  if (!groups.length || groups[0].storeId !== paidStoreId) return null
  return groups.slice(1)
}
