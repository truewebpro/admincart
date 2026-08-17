<template>
    <v-row>
        <v-col cols="12" md="9">
            <v-card elevation="1" rounded="lg" class="pa-4 section-card">
                <!-- Stat pill row -->
                <v-row class="d-flex align-center flex-wrap mb-6">
                    <v-col cols="6" md="3"
                           v-for="stat in statPills"
                           :key="stat.key"
                           class="stat-pill"
                           :class="{ 'stat-pill--active': activeStat === stat.key }"
                           @click="activeStat = stat.key"
                    >
                        <div class="text-caption text-medium-emphasis">Today {{ stat.label }}</div>
                        <div class="d-flex align-center ga-2 mt-1">
                            <span class="text-h6 font-weight-bold">{{ stat.formattedValue }}</span>
                            <v-chip
                                v-if="stat.change !== null"
                                :color="stat.change >= 0 ? 'success' : 'error'"
                                size="x-small"
                                variant="flat"
                                class="font-weight-bold"
                            >
                                <v-icon
                                    :icon="stat.change >= 0 ? 'mdi-trending-up' : 'mdi-trending-down'"
                                    size="12"
                                    class="mr-1"
                                />
                                {{ stat.change >= 0 ? '+' : '' }}{{ stat.change }}%
                            </v-chip>
                        </div>
                    </v-col>
                </v-row>

                <!-- Chart -->
                <div class="text-body-2 text-medium-emphasis mb-1">
                    {{ activeStatLabel }} over time
                </div>
                <div class="d-flex align-center ga-2 mb-2">
                    <span class="text-h4 font-weight-bold">{{ activePillValue }}</span>
                    <v-chip
                        v-if="activePillChange !== null"
                        :color="activePillChange >= 0 ? 'success' : 'error'"
                        size="small"
                        variant="text"
                        class="font-weight-bold pa-0"
                    >
                        {{ activePillChange >= 0 ? '↗' : '↘' }} {{ Math.abs(activePillChange) }}%
                    </v-chip>
                </div>

                <v-progress-linear v-show="loadingChart" indeterminate class="mb-4" />
                <apexchart
                    v-show="!loadingChart"
                    type="line"
                    height="280"
                    :options="chartOptions"
                    :series="chartSeries"
                />
            </v-card>
        </v-col>

        <!-- Live visitors panel -->
        <v-col cols="12" md="3">
            <LiveNowWidget/>
<!--            <v-card elevation="1" rounded="lg" class="pa-4 h-100">-->
<!--                <div class="d-flex align-center justify-space-between mb-4">-->
<!--                    <span class="text-body-2 font-weight-medium">Live visitors</span>-->
<!--                    <div class="d-flex align-center ga-2">-->
<!--                        <span class="text-h6 font-weight-bold">{{ liveCount }}</span>-->
<!--                        <v-icon icon="mdi-circle" :color="liveCount > 0 ? 'success' : 'grey'" size="10" />-->
<!--                    </div>-->
<!--                </div>-->

<!--                <v-list density="compact" lines="two" class="pa-0">-->
<!--                    <v-list-item-->
<!--                        v-for="visitor in recentVisitors"-->
<!--                        :key="visitor.session_id"-->
<!--                        class="px-0"-->
<!--                    >-->
<!--                        <template #prepend>-->
<!--                            <v-icon icon="mdi-circle" color="success" size="8" class="mr-2" />-->
<!--                        </template>-->
<!--                        <v-list-item-title class="text-body-2">-->
<!--                            {{ visitor.current_path || 'Unknown page' }}-->
<!--                        </v-list-item-title>-->
<!--                        <v-list-item-subtitle class="text-caption">-->
<!--                            Page view · {{ formatTime(visitor.last_seen_at) }}-->
<!--                        </v-list-item-subtitle>-->
<!--                    </v-list-item>-->

<!--                    <v-list-item v-if="!recentVisitors.length" class="px-0">-->
<!--                        <v-list-item-title class="text-body-2 text-medium-emphasis">-->
<!--                            No live visitors right now-->
<!--                        </v-list-item-title>-->
<!--                    </v-list-item>-->
<!--                </v-list>-->

<!--                <div class="text-caption text-medium-emphasis mt-2">-->
<!--                    City-level location requires IP geolocation — not wired up yet.-->
<!--                    This list shows page + time only for now.-->
<!--                </div>-->
<!--            </v-card>-->
        </v-col>
    </v-row>
</template>

<script>

import LiveNowWidget from "@/admin/analytics/LiveNowWidget.vue";

const LIVE_POLL_INTERVAL_MS = 15000;

export default {
  name: 'ShopifyStyleOverview',
    components: {LiveNowWidget},

  data() {
    return {
      activeStat: 'sessions',

      stats: {
        sessions: { value: 0, change: null },
        total_sales: { value: 0, change: null },
        orders: { value: 0, change: null },
        conversion_rate: { value: 0, change: null },
      },

      chartToday: [],
      chartYesterday: [],
      todayLabel: '',
      yesterdayLabel: '',
      loadingChart: false,

      liveCount: 0,
      recentVisitors: [],
      livePollTimer: null,
    };
  },

  computed: {
    statPills() {
      return [
        {
          key: 'sessions',
          label: 'Sessions',
          value: this.stats.sessions.value,
          change: this.stats.sessions.change,
          formattedValue: this.stats.sessions.value.toLocaleString(),
        },
        {
          key: 'total_sales',
          label: 'Total sales',
          value: this.stats.total_sales.value,
          change: this.stats.total_sales.change,
          formattedValue: this.formatCurrency(this.stats.total_sales.value),
        },
        {
          key: 'orders',
          label: 'Orders',
          value: this.stats.orders.value,
          change: this.stats.orders.change,
          formattedValue: this.stats.orders.value.toLocaleString(),
        },
        {
          key: 'conversion_rate',
          label: 'Conversion rate',
          value: this.stats.conversion_rate.value,
          change: this.stats.conversion_rate.change,
          formattedValue: `${this.stats.conversion_rate.value}%`,
        },
      ];
    },

    activeStatLabel() {
      return this.statPills.find((s) => s.key === this.activeStat)?.label || '';
    },

    activePillValue() {
      // The big number under the chart only really makes sense for
      // "Sessions" (matching the reference image), since that's the only
      // stat with an hourly series backing it right now.
      return this.stats.sessions.value.toLocaleString();
    },

    activePillChange() {
      return this.stats.sessions.change;
    },

    chartSeries() {
      return [
        { name: this.todayLabel, data: this.chartToday },
        { name: this.yesterdayLabel, data: this.chartYesterday },
      ];
    },

    chartOptions() {
      return {
        chart: { toolbar: { show: false }, zoom: { enabled: false } },
        dataLabels: { enabled: false },
        stroke: {
          curve: 'smooth',
          width: [3, 2],
          dashArray: [0, 6], // solid line for today, dashed for yesterday — matches the reference
        },
        colors: ['#1E88E5', '#90CAF9'],
        xaxis: {
          categories: ['00', '', '', '03', '', '', '06', '', '', '09', '', '', '12', '', '', '15', '', '', '18', '', '', '21', '', ''],
          tickAmount: 8,
        },
        legend: { position: 'bottom' },
        tooltip: { shared: true },
      };
    },
  },

  mounted() {
    this.fetchStats();
    this.fetchSessionsChart();
    this.fetchLiveVisitors();
    this.livePollTimer = setInterval(this.fetchLiveVisitors, LIVE_POLL_INTERVAL_MS);
  },

  beforeUnmount() {
    clearInterval(this.livePollTimer);
  },

  methods: {
    async fetchStats() {
      try {
        const { data } = await axios.get('/sadmin/analytics/daily-stats');
        this.stats = {
          sessions: data.sessions,
          total_sales: data.total_sales,
          orders: data.orders,
          conversion_rate: data.conversion_rate,
        };
      } catch (e) {
        console.error('Failed to load daily stats', e);
      }
    },

    async fetchSessionsChart() {
      this.loadingChart = true;
      try {
        const { data } = await axios.get('/sadmin/analytics/sessions-over-time');
        this.chartToday = data.today;
        this.chartYesterday = data.yesterday;
        this.todayLabel = data.today_label;
        this.yesterdayLabel = data.yesterday_label;
      } catch (e) {
        console.error('Failed to load sessions chart', e);
      } finally {
        this.loadingChart = false;
      }
    },

    async fetchLiveVisitors() {
      try {
        const { data } = await axios.get('/sadmin/analytics/live-now');
        this.liveCount = data.live_count;
        // current_pages is grouped by path; for a "recent activity" feel
        // matching the reference, just show up to 5 entries.
        this.recentVisitors = (data.current_pages || []).slice(0, 5).map((p) => ({
          session_id: p.current_path,
          current_path: p.current_path,
          last_seen_at: new Date().toISOString(),
        }));
      } catch (e) {
        console.error('Failed to load live visitors', e);
      }
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value || 0);
    },

    formatTime(value) {
      return new Date(value).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    },
  },
};
</script>

<style scoped>
.stat-pill {
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background-color 0.15s ease;
}
.stat-pill--active {
  background-color: rgba(25, 118, 210, 0.08);
}
</style>
