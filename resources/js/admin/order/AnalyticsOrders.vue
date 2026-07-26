<template>
    <v-container class="pa-1">
        <v-row class="mb-2" align="center">
            <v-col cols="12" md="4">
                <h2 class="text-h5 font-weight-bold">Orders Last 30 days</h2>
            </v-col>
            <v-col cols="12" md="3">
                <v-text-field
                    v-model="search"
                    label="Search order #"
                    density="compact"
                    variant="outlined"
                    hide-details
                    clearable
                    @update:model-value="debouncedFetch"
                />
            </v-col>
            <v-col cols="12" md="3">
                <v-select
                    v-model="statusFilter"
                    :items="statusOptions"
                    label="Status"
                    density="compact"
                    variant="outlined"
                    hide-details
                    clearable
                    @update:model-value="fetchOrders"
                />
            </v-col>
            <v-col cols="12" md="2" class="d-flex justify-end">
                <v-btn variant="tonal" @click="fetchOrders" color="primary">Refresh</v-btn>
            </v-col>
        </v-row>

        <!-- New vs. Returning customer summary -->
        <v-row class="mb-2">
            <v-col cols="12" sm="6">
                <v-card elevation="2" :loading="loadingSplit">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">New Customers</div>
                        <div class="text-h6 font-weight-bold mt-1">
                            {{ customerSplit.new.customer_count }} customers
                        </div>
                        <div class="text-body-2 text-medium-emphasis">
                            {{ customerSplit.new.order_count }} orders · {{ formatCurrency(customerSplit.new.revenue) }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6">
                <v-card elevation="2" :loading="loadingSplit">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Returning Customers</div>
                        <div class="text-h6 font-weight-bold mt-1">
                            {{ customerSplit.returning.customer_count }} customers
                        </div>
                        <div class="text-body-2 text-medium-emphasis">
                            {{ customerSplit.returning.order_count }} orders · {{ formatCurrency(customerSplit.returning.revenue) }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>


        <v-card elevation="2">
            <!-- Status breakdown chips: quick-glance counts, click to filter -->
            <v-row class="mt-1 ms-2" dense>
                <v-col cols="12">
                    <v-chip
                        v-for="row in statusBreakdown"
                        :key="row.order_status"
                        class="mr-2 mb-2"
                        :variant="statusFilter === row.order_status ? 'flat' : 'tonal'"
                        :color="statusColor(row.order_status)"
                        @click="toggleStatusFilter(row.order_status)"
                    >
                        {{ row.order_status }}: {{ row.order_count }} ({{ formatCurrency(row.revenue) }})
                    </v-chip>
                </v-col>
            </v-row>
            <v-data-table-server
                v-model:items-per-page="perPage"
                v-model:page="page"
                :headers="headers"
                :items="orders"
                :items-length="total"
                :loading="loading"
                hover
                @update:options="fetchOrders"
            >
                <template #item.customer="{ item }">
                    {{ item.customer?.fname }} {{ item.customer?.lname }}
                    <div class="text-caption text-medium-emphasis">{{ item.customer?.email }}</div>
                </template>

                <template #item.order_status="{ item }">
                    <v-chip :color="statusColor(item.order_status)" size="small" variant="tonal">
                        {{ item.order_status }}
                    </v-chip>
                </template>

                <template #item.payment_status="{ item }">
                    <v-chip :color="item.payment_status === 'paid' ? 'success' : 'warning'" size="small" variant="tonal">
                        {{ item.payment_status }}
                    </v-chip>
                </template>

                <template #item.order_total="{ item }">
                    {{ formatCurrency(item.order_total) }}
                </template>

                <template #item.placed_at="{ item }">
                    {{ formatDate(item.placed_at) }}
                </template>

                <template #item.actions="{ item }">
                    <v-btn size="small" variant="outlined" color="info" density="comfortable" :to="`/orders/${item.order_id}`">
                        View
                    </v-btn>
                </template>
            </v-data-table-server>
        </v-card>
    </v-container>
</template>

<script>
export default {
    name: 'AnalyticsOrders',

    data() {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 29);

        return {
            from: thirtyDaysAgo.toISOString().slice(0, 10),
            to: today.toISOString().slice(0, 10),

            orders: [],
            total: 0,
            page: 1,
            perPage: 25,
            loading: false,

            search: '',
            statusFilter: null,
            statusBreakdown: [],

            customerSplit: {
                new: { customer_count: 0, order_count: 0, revenue: 0 },
                returning: { customer_count: 0, order_count: 0, revenue: 0 },
            },
            loadingSplit: false,

            debounceTimer: null,

            statusOptions: ['pending', 'processing', 'fulfilled', 'cancelled', 'refunded'], // adjust to your real order_status values

            headers: [
                { title: 'Order #', key: 'order_number' },
                { title: 'Customer', key: 'customer', sortable: false },
                { title: 'Status', key: 'order_status' },
                { title: 'Payment', key: 'payment_status' },
                { title: 'Total', key: 'order_total', align: 'start' },
                { title: 'Placed', key: 'placed_at' },
                { title: '', key: 'actions', sortable: false, align: 'start' },
            ],
        };
    },

    mounted() {
        this.fetchBreakdown();
        this.fetchCustomerSplit();
    },

    methods: {
        debouncedFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.page = 1;
                this.fetchOrders();
            }, 350);
        },

        toggleStatusFilter(status) {
            this.statusFilter = this.statusFilter === status ? null : status;
            this.page = 1;
            this.fetchOrders();
        },

        async fetchOrders() {
            this.loading = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/orders', {
                    params: {
                        from: this.from,
                        to: this.to,
                        search: this.search || undefined,
                        order_status: this.statusFilter || undefined,
                        page: this.page,
                        per_page: this.perPage,
                    },
                });
                this.orders = data.orders || [];
                this.total = data.total || 0;
            } catch (e) {
                console.error('Failed to load orders', e);
            } finally {
                this.loading = false;
            }
        },

        async fetchBreakdown() {
            try {
                const { data } = await axios.get('/sadmin/analytics/breakdown', {
                    params: { from: this.from, to: this.to, by: 'order_status' },
                });
                this.statusBreakdown = data.breakdown || [];
            } catch (e) {
                console.error('Failed to load status breakdown', e);
            }
        },

        async fetchCustomerSplit() {
            this.loadingSplit = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/customer-split', {
                    params: { from: this.from, to: this.to },
                });
                this.customerSplit = { new: data.new, returning: data.returning };
            } catch (e) {
                console.error('Failed to load customer split', e);
            } finally {
                this.loadingSplit = false;
            }
        },


        statusColor(status) {
            const map = {
                pending: 'warning',
                processing: 'info',
                fulfilled: 'success',
                completed: 'success',
                cancelled: 'error',
                refunded: 'grey',
            };
            return map[status] || 'grey';
        },

        formatCurrency(value) {
            if (value === null || value === undefined) return '—';
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value);
        },

        formatDate(value) {
            if (!value) return '—';
            return new Date(value).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        },
    },
};
</script>
