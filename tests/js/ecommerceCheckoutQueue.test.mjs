import test from 'node:test'
import assert from 'node:assert/strict'
import { encodeCheckoutQueue, parseCheckoutQueue, remainingCheckoutQueue } from '../../resources/js/utils/ecommerceCheckoutQueue.js'

test('encodes two selected stores in order and advances after the first payment', () => {
  const groups = [
    { storeId: 10, itemIds: [3, 4] },
    { storeId: 20, itemIds: [8] },
  ]
  const parsed = parseCheckoutQueue(encodeCheckoutQueue(groups))

  assert.deepEqual(parsed, groups)
  assert.deepEqual(remainingCheckoutQueue(parsed, 10), [groups[1]])
  assert.deepEqual(remainingCheckoutQueue([groups[1]], 20), [])
})

test('does not advance for a paid order from another store', () => {
  const groups = parseCheckoutQueue('10:3;20:8')
  assert.equal(remainingCheckoutQueue(groups, 20), null)
})

test('rejects malformed or repeated store and item IDs', () => {
  for (const raw of ['10:3;10:4', '10:3;20:3', '10:3.3', '0:3', '10:0', '10:3;20:', '10:3;20:4;']) {
    assert.deepEqual(parseCheckoutQueue(raw), [])
  }
})
