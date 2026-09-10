import axios from 'axios'

// No shop segment: the server resolves the tenant from session('shop_id').
const base = '/sadmin'

export const paymentsApi = {
    gatewaySchema: () =>
        axios.get(`${base}/payment-gateways/schema`).then((r) => r.data.providers),

    listGateways: () =>
        axios.get(`${base}/payment-gateways`).then((r) => r.data.gateways),

    saveGateway: (provider, payload) =>
        axios.put(`${base}/payment-gateways/${provider}`, payload).then((r) => r.data),

    removeGateway: (provider) =>
        axios.delete(`${base}/payment-gateways/${provider}`).then((r) => r.data),

    /** Read-only credential check — cannot move money. */
    testGateway: (provider) =>
        axios.post(`${base}/payment-gateways/${provider}/test`).then((r) => r.data),

    listTransactions: (params) =>
        axios.get(`${base}/payment-transactions`, { params }).then((r) => r.data),

    getTransaction: (id) =>
        axios.get(`${base}/payment-transactions/${id}`).then((r) => r.data.transaction),

    refundSummary: (orderId) =>
        axios.get(`${base}/orders/${orderId}/refunds`).then((r) => r.data),

    submitRefund: (orderId, payload) =>
        axios.post(`${base}/orders/${orderId}/refunds`, payload).then((r) => r.data),
}

/** Stable key so a retry after a network drop can't double-refund. */
export const newIdempotencyKey = () =>
    crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`

export const money = (value, currency) =>
    new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value ?? 0)
