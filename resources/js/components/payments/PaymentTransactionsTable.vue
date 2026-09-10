<script>
import { paymentsApi, money } from '@/services/payments.js'

export default {
    name: 'PaymentTransactionsTable',
    props: {
        // Pass an order id to scope the log to a single order.
        orderId: { type: [Number, String], default: null },
    },
    data() {
        return {
            loading: false,
            rows: [],
            total: 0,
            stats: {},
            page: 1,
            perPage: 25,
            filters: {
                provider: null,
                type: null,
                status: null,
                search: '',
            },
            detail: null,
            detailLoading: false,
            detailOpen: false,
            error: '',
            providerOptions: [
                { value: null, title: 'All providers' },
                { value: 'viva_wallet', title: 'Viva Wallet' },
                { value: 'worldpay', title: 'Worldpay' },
                { value: 'cybersource', title: 'Cybersource' },
                { value: 'paypal', title: 'PayPal' },
            ],
            typeOptions: [
                { value: null, title: 'All types' },
                { value: 'charge', title: 'Charge' },
                { value: 'refund', title: 'Refund' },
                { value: 'void', title: 'Cancellation' },
            ],
            statusOptions: [
                { value: null, title: 'All statuses' },
                { value: 'succeeded', title: 'Succeeded' },
                { value: 'pending', title: 'Pending' },
                { value: 'failed', title: 'Failed' },
            ],
            headers: [
                { title: 'When', key: 'created_at', sortable: false },
                { title: 'Order', key: 'order_id', sortable: false },
                { title: 'Provider', key: 'provider', sortable: false },
                { title: 'Type', key: 'type', sortable: false },
                { title: 'Amount', key: 'amount', align: 'end', sortable: false },
                { title: 'Status', key: 'status', sortable: false },
                { title: 'Reference', key: 'gateway_transaction_id', sortable: false },
                { title: '', key: 'actions', align: 'end', sortable: false },
            ],
        }
    },
    computed: {
        shopId() {
            return this.$store.state.shop_id
        },
        statProviders() {
            return Object.keys(this.stats ?? {})
        },
    },
    watch: {
        shopId() {
            this.page = 1
            this.load()
        },
    },
    mounted() {
        this.load()
    },
    methods: {
        money,

        statusColour(status) {
            return { succeeded: 'success', pending: 'warning', failed: 'error' }[status] ?? 'grey'
        },

        typeColour(type) {
            return { charge: 'primary', refund: 'info', void: 'purple' }[type] ?? 'grey'
        },

        providerLabel(provider) {
            return (
                {
                    viva_wallet: 'Viva Wallet',
                    worldpay: 'Worldpay',
                    cybersource: 'Cybersource',
                    paypal: 'PayPal',
                }[provider] ?? provider
            )
        },

        formatDate(value) {
            return value ? new Date(value).toLocaleString() : ''
        },

        pretty(value) {
            if (value === null || value === undefined) return '—'
            return JSON.stringify(value, null, 2)
        },

        async load() {
            this.loading = true
            this.error = ''
            try {
                const data = await paymentsApi.listTransactions({
                    ...this.filters,
                    order_id: this.orderId ?? undefined,
                    page: this.page,
                    per_page: this.perPage,
                })
                this.rows = data.rows
                this.total = data.total
                this.stats = data.stats ?? {}
            } catch (e) {
                this.error =
                    e.response?.status === 409
                        ? 'No shop is selected. Pick a shop to see its payment activity.'
                        : 'Could not load the payment log.'
            } finally {
                this.loading = false
            }
        },

        applyFilters() {
            this.page = 1
            this.load()
        },

        resetFilters() {
            this.filters = { provider: null, type: null, status: null, search: '' }
            this.applyFilters()
        },

        onPageChange(page) {
            this.page = page
            this.load()
        },

        async openDetail(row) {
            this.detailOpen = true
            this.detailLoading = true
            this.detail = null
            try {
                this.detail = await paymentsApi.getTransaction(row.id)
            } catch (e) {
                this.error = 'Could not load that transaction.'
                this.detailOpen = false
            } finally {
                this.detailLoading = false
            }
        },
    },
}
</script>

<template>
    <v-card>
        <v-card-title>Payment activity</v-card-title>
        <v-card-subtitle>
            Every charge, refund and cancellation this shop has sent, including the ones the
            provider rejected.
        </v-card-subtitle>

        <v-card-text>
            <v-alert v-if="error" type="error" variant="tonal" class="mb-4" :text="error" />

            <!-- Last 30 days per provider, so a failing gateway stands out immediately -->
            <v-row v-if="statProviders.length" dense class="mb-4">
                <v-col v-for="provider in statProviders" :key="provider" cols="12" sm="4">
                    <v-sheet border rounded class="pa-3">
                        <div class="text-subtitle-2 mb-1">{{ providerLabel(provider) }}</div>
                        <div class="d-flex ga-2">
                            <v-chip size="x-small" color="success" variant="tonal">
                                {{ stats[provider].succeeded }} ok
                            </v-chip>
                            <v-chip size="x-small" color="warning" variant="tonal">
                                {{ stats[provider].pending }} pending
                            </v-chip>
                            <v-chip size="x-small" color="error" variant="tonal">
                                {{ stats[provider].failed }} failed
                            </v-chip>
                        </div>
                        <div class="text-caption text-medium-emphasis mt-1">Last 30 days</div>
                    </v-sheet>
                </v-col>
            </v-row>

            <v-row dense class="mb-2">
                <v-col cols="12" sm="3">
                    <v-select
                        v-model="filters.provider"
                        :items="providerOptions"
                        label="Provider"
                        density="comfortable"
                        hide-details
                        @update:model-value="applyFilters"
                    />
                </v-col>
                <v-col cols="6" sm="2">
                    <v-select
                        v-model="filters.type"
                        :items="typeOptions"
                        label="Type"
                        density="comfortable"
                        hide-details
                        @update:model-value="applyFilters"
                    />
                </v-col>
                <v-col cols="6" sm="2">
                    <v-select
                        v-model="filters.status"
                        :items="statusOptions"
                        label="Status"
                        density="comfortable"
                        hide-details
                        @update:model-value="applyFilters"
                    />
                </v-col>
                <v-col cols="12" sm="4">
                    <v-text-field
                        v-model="filters.search"
                        label="Provider reference or idempotency key"
                        density="comfortable"
                        clearable
                        hide-details
                        @keyup.enter="applyFilters"
                        @click:clear="applyFilters"
                    />
                </v-col>
                <v-col cols="12" sm="1" class="d-flex align-center">
                    <v-btn variant="text" size="small" @click="resetFilters">Clear</v-btn>
                </v-col>
            </v-row>

            <v-data-table-server
                :headers="headers"
                :items="rows"
                :items-length="total"
                :loading="loading"
                :page="page"
                :items-per-page="perPage"
                density="comfortable"
                @update:page="onPageChange"
            >
                <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                </template>

                <template #item.order_id="{ item }">
                    <span class="text-medium-emphasis">#{{ item.order_id ?? '—' }}</span>
                </template>

                <template #item.provider="{ item }">
                    {{ providerLabel(item.provider) }}
                </template>

                <template #item.type="{ item }">
                    <v-chip :color="typeColour(item.type)" size="x-small" variant="tonal">
                        {{ item.type }}
                    </v-chip>
                </template>

                <template #item.amount="{ item }">
                    {{ money(item.amount, item.currency) }}
                </template>

                <template #item.status="{ item }">
                    <v-tooltip :text="item.error_message" :disabled="!item.error_message">
                        <template #activator="{ props }">
                            <v-chip v-bind="props" :color="statusColour(item.status)" size="x-small" variant="flat">
                                {{ item.status }}
                            </v-chip>
                        </template>
                    </v-tooltip>
                </template>

                <template #item.gateway_transaction_id="{ item }">
                    <span class="text-caption">{{ item.gateway_transaction_id ?? '—' }}</span>
                </template>

                <template #item.actions="{ item }">
                    <v-btn variant="text" size="small" @click="openDetail(item)">Details</v-btn>
                </template>

                <template #no-data>
                    <div class="pa-6 text-center text-medium-emphasis">
                        Nothing here yet. Charges and refunds appear as soon as they are sent.
                    </div>
                </template>
            </v-data-table-server>
        </v-card-text>

        <v-dialog v-model="detailOpen" max-width="760" scrollable>
            <v-card>
                <v-card-title>Transaction #{{ detail?.id }}</v-card-title>
                <v-divider />
                <v-card-text>
                    <v-skeleton-loader v-if="detailLoading" type="article" />

                    <template v-else-if="detail">
                        <v-row dense class="mb-3">
                            <v-col cols="6" sm="3">
                                <div class="text-caption text-medium-emphasis">Status</div>
                                <v-chip :color="statusColour(detail.status)" size="x-small" variant="flat">
                                    {{ detail.status }}
                                </v-chip>
                            </v-col>
                            <v-col cols="6" sm="3">
                                <div class="text-caption text-medium-emphasis">Amount</div>
                                <div>{{ money(detail.amount, detail.currency) }}</div>
                            </v-col>
                            <v-col cols="6" sm="3">
                                <div class="text-caption text-medium-emphasis">Provider</div>
                                <div>{{ providerLabel(detail.provider) }}</div>
                            </v-col>
                            <v-col cols="6" sm="3">
                                <div class="text-caption text-medium-emphasis">Sent</div>
                                <div class="text-caption">{{ formatDate(detail.created_at) }}</div>
                            </v-col>
                        </v-row>

                        <v-alert
                            v-if="detail.error_message"
                            type="error"
                            variant="tonal"
                            class="mb-3"
                            :text="`${detail.error_code}: ${detail.error_message}`"
                        />

                        <div class="text-subtitle-2 mb-1">What we sent</div>
                        <pre class="log-json mb-4">{{ pretty(detail.request_payload) }}</pre>

                        <div class="text-subtitle-2 mb-1">What the provider returned</div>
                        <pre class="log-json mb-4">{{ pretty(detail.response_payload) }}</pre>

                        <div class="text-subtitle-2 mb-1">Stored handles</div>
                        <pre class="log-json">{{ pretty(detail.meta) }}</pre>

                        <div class="text-caption text-medium-emphasis mt-3">
                            Idempotency key {{ detail.idempotency_key ?? '—' }}
                        </div>
                    </template>
                </v-card-text>
                <v-divider />
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="detailOpen = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-card>
</template>

<style scoped>
.log-json {
    background: rgba(var(--v-theme-on-surface), 0.04);
    border-radius: 4px;
    padding: 12px;
    font-size: 12px;
    line-height: 1.5;
    max-height: 240px;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
