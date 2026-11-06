import http from 'k6/http'
import { check, sleep } from 'k6'

const baseUrl = String(__ENV.BASE_URL || '').replace(/\/+$/, '')
const testEnvironment = String(__ENV.TEST_ENV || '').toLowerCase()
const host = baseUrl.match(/^https?:\/\/([^/:]+)/i)?.[1]?.toLowerCase() || ''
const isLocalHost = ['localhost', '127.0.0.1', '::1'].includes(host)
const isStagingHost = host.startsWith('staging.') || host.includes('.staging.')

if (!baseUrl) {
  throw new Error('Set BASE_URL to the local or staging application URL before running this test.')
}

if (!isLocalHost && (testEnvironment !== 'staging' || !isStagingHost)) {
  throw new Error('This script only permits localhost or an explicitly marked staging subdomain. Production load testing is blocked.')
}

export const options = {
  discardResponseBodies: true,
  scenarios: {
    read_only_catalog_ramp: {
      executor: 'ramping-vus',
      startVUs: 1,
      stages: [
        { duration: '15s', target: 1 },
        { duration: '15s', target: 2 },
        { duration: '15s', target: 5 },
        { duration: '10s', target: 5 },
        { duration: '10s', target: 0 },
      ],
      gracefulRampDown: '5s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<5000'],
  },
}

const paths = [
  '/api/ecommerce/categories/active-stock',
  '/api/ecommerce/products/active-stock?page=1&per_page=20',
  '/api/public/home-content',
]

export default function () {
  for (const path of paths) {
    const response = http.get(`${baseUrl}${path}`, {
      tags: { endpoint: path.split('?')[0] },
      timeout: '12s',
    })

    check(response, {
      [`${path.split('?')[0]} returns HTTP 200`]: (res) => res.status === 200,
    })
  }

  sleep(0.5)
}
