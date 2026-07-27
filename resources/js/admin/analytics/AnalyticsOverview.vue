<template>
    <v-container class="pa-1">
        <v-row class="mb-1" align="center">
            <v-col cols="12" md="6">
                <h2 class="text-h4 font-weight-bold">Analytics Dashboard</h2>
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
        <v-row align="stretch">
            <v-col cols="12" md="8">
                <v-card class="position-relative overflow-visible elevation-2 rounded-lg h-100">
                    <v-card-text>
                        <div class="d-flex">
                            <div class="w-75 d-flex flex-column justify-space-between">
                                <div class="text-h5 text-md-h4">Welcome <span class="font-weight-bold">{{suser.name}} {{$store.getters.currentShopName}} 🎉</span></div>
                                <p class="text-body-1 mt-3">
                                    Have done 😎 more sales? Check your new raising badge in your profile.
                                </p>
                                <p class="text-body-1 mt-1 font-weight-medium">
                                    {{dayjs(from).format('D MMM, YYYY')}} to {{dayjs(to).format('D MMM, YYYY')}}
                                </p>
                            </div>
                            <div class="position-absolute bottom-0 right-0">
                                <v-img width="225"
                                       src="https://demos.themeselection.com/materio-vuetify-vuejs-admin-template/demo-1/assets/illustration-john-2-DCqPs8R_.png"/>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-item
                        title="Products & Users"
                        subtitle="Total Growth 😎 this store "
                        append-icon="mdi-credit-card-wireless-outline"></v-card-item>
                    <v-card-text>
                        <v-row>
                            <v-col cols="12" md="6">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar icon="mdi-account-group" class="bg-success rounded"></v-avatar>
                                    <div class="d-flex flex-column">
                                        <div class="text-body-1 text-medium-emphasis">Customers</div>
                                        <h5 class="text-h5 font-weight-medium">{{custNew}}</h5>
                                    </div>
                                </div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar icon="mdi-cart-arrow-down" class="bg-info rounded"></v-avatar>
                                    <div class="d-flex flex-column">
                                        <div class="text-body-1 text-medium-emphasis">Products</div>
                                        <h5 class="text-h5 font-weight-medium">{{productsCount}}</h5>
                                    </div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" sm="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-item
                        title="Revenue"
                        append-icon="mdi-chart-bar">
                    </v-card-item>
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">
                            {{dayjs(from).format('D MMM, YYYY')}} to {{dayjs(to).format('D MMM, YYYY')}}
                        </div>
                        <div class="text-h5 font-weight-bold mt-1">
                            {{ formatCurrency(overview?.current?.revenue) }}
                        </div>
                        <v-chip
                            v-if="overview"
                            :color="changeColor(overview.change.revenue)"
                            size="small"
                            class="mt-2"
                            variant="elevated"
                        >
                            {{ formatChange(overview.change.revenue) }} vs previous period
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-item
                        title="Orders"
                        append-icon="mdi-truck-fast-outline">
                    </v-card-item>
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">
                            {{dayjs(from).format('D MMM, YYYY')}} to {{dayjs(to).format('D MMM, YYYY')}}
                        </div>
                        <div class="text-h5 font-weight-bold mt-1">
                            {{ overview?.current?.order_count ?? '—' }}
                        </div>
                        <v-chip
                            v-if="overview"
                            :color="changeColor(overview.change.orders)"
                            size="small"
                            class="mt-2"
                            variant="elevated"
                        >
                            {{ formatChange(overview.change.orders) }} vs previous period
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" sm="4">
                <v-card :loading="loadingOverview" elevation="2">
                    <v-card-item
                        title="Avg. Order Value"
                        append-icon="mdi-sale-outline">
                    </v-card-item>
                    <v-card-text>
                        <div class="text-caption text-medium-emphasis">
                            {{dayjs(from).format('D MMM, YYYY')}} to {{dayjs(to).format('D MMM, YYYY')}}
                        </div>
                        <div class="text-h5 font-weight-bold mt-1">
                            {{ formatCurrency(overview?.current?.avg_order_value) }}
                        </div>
                        <v-chip
                            v-if="overview"
                            :color="changeColor(overview.change.aov)"
                            size="small"
                            class="mt-2"
                            variant="elevated"
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
                    <v-card-item
                        title="Revenue trend"
                        subtitle="Trend day basis as select"
                        append-icon="mdi-finance">
                    </v-card-item>
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
                    <v-card-item title="Top Products">
                        <template #append>
                            <v-btn-toggle color="success" v-model="topProductsSort" density="compact" mandatory @update:model-value="fetchTopProducts">
                                <v-btn value="revenue" size="small">By Revenue</v-btn>
                                <v-btn value="quantity" size="small">By Quantity</v-btn>
                            </v-btn-toggle>
                        </template>
                    </v-card-item>
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
import dayjs from "dayjs";
export default {
    name: "AnalyticsOverview",
    components: {VDateInput},
    data() {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 29);

        return {
            cdn:this.$store.state.cdn,
            suser:{},
            productsCount:0,
            custNew:0,
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
    created() {
        this.getAllUsers();
    },
    methods:{
        dayjs,
        fetchAll() {
            this.fetchOverview();
            this.fetchTrend();
            this.fetchTopProducts();
        },
        getAllUsers(){
            axios.get(`/sadmin/dashboard`)
                .then((resp)=>{
                    this.users = resp.data.users;
                    this.suser = resp.data.users;
                    this.ashops = resp.data.shops;
                    if(this.ashops.length > 0){
                        this.selectedShop = this.ashops[0];
                    }
                    this.rorders = resp.data.orders;
                    this.pendingRev = resp.data.pendingCount;
                    this.paidRev = resp.data.paidCount;
                    this.shippedOrd = resp.data.shippedOrd;
                    this.pendingOrd = resp.data.pendingOrd;
                    this.ordersCount = resp.data.ordersCount;
                    this.productsCount = resp.data.productsCount;
                    this.variantsCount = resp.data.variantsCount;
                    this.shopUsers = resp.data.shopUsers;
                    this.tpros = resp.data.tpros;
                    this.custNew = resp.data.customersNew;
                })
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
            return 'info';
        },
    },
};
</script>
