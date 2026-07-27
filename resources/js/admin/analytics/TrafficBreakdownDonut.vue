<template>
    <v-card elevation="2">
        <v-card-title class="text-subtitle-1 d-flex align-center justify-space-between">
            Visitors by {{ by === 'device_type' ? 'Device' : 'Browser' }}
            <v-btn-toggle v-model="by" density="compact" mandatory @update:model-value="fetchBreakdown">
                <v-btn value="device_type" size="small">Device</v-btn>
                <v-btn value="browser" size="small">Browser</v-btn>
            </v-btn-toggle>
        </v-card-title>

        <v-card-text>
            <v-progress-linear v-show="loading" indeterminate class="mb-4" />
            <apexchart
                v-show="!loading"
                type="donut"
                height="280"
                :options="chartOptions"
                :series="chartSeries"
            />
        </v-card-text>
    </v-card>
</template>

<script>
export default {
    name: 'TrafficBreakdownDonut',

    props: {
        from: { type: String, required: true },
        to: { type: String, required: true },
    },

    data() {
        return {
            by: 'device_type',
            breakdown: [],
            loading: false,
        };
    },

    computed: {
        chartSeries() {
            return this.breakdown.map((row) => row.unique_visitors);
        },

        chartOptions() {
            return {
                chart: { toolbar: { show: false } },
                labels: this.breakdown.map((row) => row.label),
                legend: { position: 'bottom' },
                dataLabels: {
                    formatter: (val) => `${val.toFixed(1)}%`,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Visitors',
                                    formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                                },
                            },
                        },
                    },
                },
            };
        },
    },

    watch: {
        from() { this.fetchBreakdown(); },
        to() { this.fetchBreakdown(); },
    },

    mounted() {
        this.fetchBreakdown();
    },

    methods: {
        async fetchBreakdown() {
            this.loading = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/traffic-breakdown', {
                    params: { from: this.from, to: this.to, by: this.by },
                });
                this.breakdown = data.breakdown || [];
            } catch (e) {
                console.error('Failed to load traffic breakdown', e);
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
