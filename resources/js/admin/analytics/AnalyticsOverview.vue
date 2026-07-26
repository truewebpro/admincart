<template>
    <v-container class="pa-1">
        <v-row class="mb-2" align="center">
            <v-col cols="12" md="6">
                <h2 class="text-h4 font-weight-bold">Analytics</h2>
            </v-col>
            <v-col cols="12" md="6" class="d-flex ga-2 justify-start justify-lg-end">
                <v-date-input
                    v-model="from"
                    label="From"
                    density="compact"
                    variant="underlined"
                    hide-details
                    max-width="220"
                    prepend-icon=""
                    prepend-inner-icon="mdi-calendar"
                    input-format="dd-mm-yyyy"
                    @update:model-value="fetchAll"
                />
                <v-date-input
                    v-model="to"
                    label="To"
                    density="compact"
                    variant="underlined"
                    hide-details
                    max-width="220"
                    prepend-icon=""
                    prepend-inner-icon="mdi-calendar"
                    input-format="dd-mm-yyyy"
                    @update:model-value="fetchAll"
                />
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" sm="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Revenue</div>
                        <div class="text-h5 font-weight-bold mt-1">
                            {{ formatCurrency(overview?.current?.revenue) }}
                        </div>
                        <v-chip
                            v-if="overview"
                            :color="changeColor(overview.change.revenue)"
                            size="small"
                            class="mt-2"
                            variant="tonal"
                        >
                            {{ formatChange(overview.change.revenue) }} vs previous period
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Orders</div>
                        <div class="text-h5 font-weight-bold mt-1">
                            {{ overview?.current?.order_count ?? '—' }}
                        </div>
                        <v-chip
                            v-if="overview"
                            :color="changeColor(overview.change.orders)"
                            size="small"
                            class="mt-2"
                            variant="tonal"
                        >
                            {{ formatChange(overview.change.orders) }} vs previous period
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">Avg. Order Value</div>
                        <div class="text-h5 font-weight-bold mt-1">
                            {{ formatCurrency(overview?.current?.avg_order_value) }}
                        </div>
                        <v-chip
                            v-if="overview"
                            :color="changeColor(overview.change.aov)"
                            size="small"
                            class="mt-2"
                            variant="tonal"
                        >
                            {{ formatChange(overview.change.aov) }} vs previous period
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2">
            <v-col cols="12">
                <v-card elevation="2">
                    <v-card-title class="text-subtitle-1">Revenue Trend</v-card-title>
                    <v-card-text>
                        <v-progress-linear v-show="loadingTrend" indeterminate class="mb-3" />
                        <apexchart
                            v-show="!loadingTrend"
                            type="area"
                            height="300"
                            :options="chartOptions"
                            :series="chartSeries"
                        />
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2">
            <v-col cols="12">
                <v-card elevation="2">
                    <v-card-title class="text-subtitle-1 d-flex align-center justify-space-between">
                        Top Products
                        <v-btn-toggle color="success" v-model="topProductsSort" density="compact" mandatory @update:model-value="fetchTopProducts">
                            <v-btn value="revenue" size="small">By Revenue</v-btn>
                            <v-btn value="quantity" size="small">By Quantity</v-btn>
                        </v-btn-toggle>
                    </v-card-title>
                    <v-data-table
                        :headers="topProductsHeaders"
                        :items="topProducts"
                        :loading="loadingTopProducts"
                        :items-per-page="10"
                        density="default"
                    >
                        <template #item.total_revenue="{ item }">
                            {{ formatCurrency(item.total_revenue) }}
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import {VDateInput} from "vuetify/labs/VDateInput";
export default {
    name: "AnalyticsOverview",
    components: {VDateInput},
    data() {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 29);

        return {
            from: thirtyDaysAgo.toISOString().slice(0, 10),
            to: today.toISOString().slice(0, 10),

            overview: null,
            loadingOverview: false,

            trend: [],
            loadingTrend: false,

            topProducts: [],
            topProductsSort: 'revenue',
            loadingTopProducts: false,

            topProductsHeaders: [
                { title: 'Product', key: 'title' },
                { title: 'Qty Sold', key: 'total_quantity', align: 'start' },
                { title: 'Orders', key: 'order_count', align: 'start' },
                { title: 'Revenue', key: 'total_revenue', align: 'start' },
            ],
        };
    },
    computed:{
        chartSeries() {
            return [
                {
                    name: 'Revenue',
                    data: this.trend.map((d) => d.revenue),
                },
            ];
        },
        chartOptions() {
            return {
                chart: { toolbar: { show: false }, zoom: { enabled: false } },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: this.trend.map((d) => d.date),
                    labels: { rotate: -45 },
                },
                yaxis: {
                    labels: {
                        formatter: (val) => this.formatCurrency(val),
                    },
                },
                tooltip: {
                    y: {
                        formatter: (val) => this.formatCurrency(val),
                    },
                },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 },
                },
            };
        },
    },
    mounted() {
        this.fetchAll();
    },
    methods:{
        fetchAll() {
            this.fetchOverview();
            this.fetchTrend();
            this.fetchTopProducts();
        },
        async fetchOverview() {
            this.loadingOverview = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/overview', {
                    params: { from: this.from, to: this.to },
                });
                this.overview = data;
            } catch (e) {
                console.error('Failed to load overview', e);
            } finally {
                this.loadingOverview = false;
            }
        },
        async fetchTrend() {
            this.loadingTrend = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/sales-trend', {
                    params: { from: this.from, to: this.to },
                });
                this.trend = data.series || [];
            } catch (e) {
                console.error('Failed to load sales trend', e);
            } finally {
                this.loadingTrend = false;
            }
        },
        async fetchTopProducts() {
            this.loadingTopProducts = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/top-products', {
                    params: { from: this.from, to: this.to, sort_by: this.topProductsSort, limit: 10 },
                });
                this.topProducts = data.products || [];
            } catch (e) {
                console.error('Failed to load top products', e);
            } finally {
                this.loadingTopProducts = false;
            }
        },
        formatCurrency(value) {
            if (value === null || value === undefined) return '—';
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value);
        },

        formatChange(value) {
            if (value === null || value === undefined) return '—';
            const sign = value > 0 ? '+' : '';
            return `${sign}${value}%`;
        },
        changeColor(value) {
            if (value > 0) return 'success';
            if (value < 0) return 'error';
            return 'grey';
        },
    },
};
</script>
