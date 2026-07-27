<template>
    <v-container class="pa-1">
        <v-row class="mb-2" align="center">
            <v-col cols="12" md="6">
                <h2 class="text-h5 font-weight-bold">Traffic</h2>
            </v-col>
            <v-col cols="12" md="6" class="d-flex justify-end">
                <v-text-field
                    v-model="from"
                    type="date"
                    label="From"
                    density="compact"
                    variant="outlined"
                    hide-details
                    style="max-width: 160px"
                    class="mr-2"
                    @update:model-value="fetchAll"
                />
                <v-text-field
                    v-model="to"
                    type="date"
                    label="To"
                    density="compact"
                    variant="outlined"
                    hide-details
                    style="max-width: 160px"
                    @update:model-value="fetchAll"
                />
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="4">
                <live-now-widget />
            </v-col>

            <v-col cols="12" md="8">
                <v-card elevation="2">
                    <v-card-item title="Traffic Trend" append-icon="mdi-chart-bell-curve"></v-card-item>
                    <v-card-text>
                        <v-progress-linear v-show="loadingTrend" indeterminate class="mb-4" />
                        <apexchart
                            v-show="!loadingTrend"
                            type="line"
                            height="280"
                            :options="chartOptions"
                            :series="chartSeries"
                        />
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2">
            <v-col cols="12" md="4">
                <TrafficBreakdownDonut :to="to" :from="from"/>
            </v-col>
            <v-col cols="12" md="8">
                <v-card elevation="2">
                    <v-card-item title="Top Pages" append-icon="mdi-web"></v-card-item>
                    <v-data-table
                        :headers="pageHeaders"
                        :items="topPages"
                        :loading="loadingPages"
                        :items-per-page="15"
                        density="comfortable"
                    >
                        <template #item.views="{ item }">
                            {{ item.views.toLocaleString() }}
                        </template>
                        <template #item.unique_visitors="{ item }">
                            {{ item.unique_visitors.toLocaleString() }}
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>

import LiveNowWidget from "@/admin/analytics/LiveNowWidget.vue";
import TrafficBreakdownDonut from "@/admin/analytics/TrafficBreakdownDonut.vue";

export default {
    name: 'AnalyticsTraffic',
    components: {TrafficBreakdownDonut, LiveNowWidget },
    data() {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 29);

        return {
            from: thirtyDaysAgo.toISOString().slice(0, 10),
            to: today.toISOString().slice(0, 10),

            trend: [],
            loadingTrend: false,

            topPages: [],
            loadingPages: false,

            pageHeaders: [
                { title: 'Path', key: 'path' },
                { title: 'Views', key: 'views', align: 'end' },
                { title: 'Unique Visitors', key: 'unique_visitors', align: 'end' },
            ],
        };
    },

    computed: {
        chartSeries() {
            return [
                { name: 'Pageviews', data: this.trend.map((d) => d.pageviews) },
                { name: 'Unique Visitors', data: this.trend.map((d) => d.unique_visitors) },
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
                legend: { position: 'top' },
            };
        },
    },

    mounted() {
        this.fetchAll();
    },

    methods: {
        fetchAll() {
            this.fetchTrend();
            this.fetchTopPages();
        },

        async fetchTrend() {
            this.loadingTrend = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/pageview-trend', {
                    params: { from: this.from, to: this.to },
                });
                this.trend = data.series || [];
            } catch (e) {
                console.error('Failed to load pageview trend', e);
            } finally {
                this.loadingTrend = false;
            }
        },

        async fetchTopPages() {
            this.loadingPages = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/top-pages', {
                    params: { from: this.from, to: this.to, limit: 15 },
                });
                this.topPages = data.pages || [];
            } catch (e) {
                console.error('Failed to load top pages', e);
            } finally {
                this.loadingPages = false;
            }
        },
    },
};
</script>
