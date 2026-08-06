<template>
  <v-container>
    <v-row class="mb-2" align="center">
      <v-col cols="12" md="6"><h2 class="text-h5 font-weight-bold">Sessions</h2></v-col>
      <v-col cols="12" md="6" class="d-flex justify-end">
        <v-text-field v-model="from" type="date" label="From" density="compact" variant="outlined" hide-details style="max-width:160px" class="mr-2" @update:model-value="fetchAll" />
        <v-text-field v-model="to" type="date" label="To" density="compact" variant="outlined" hide-details style="max-width:160px" @update:model-value="fetchAll" />
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="4">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Sessions over time</div>
          <div class="d-flex align-center ga-2 mb-2">
            <span class="text-h5 font-weight-bold">{{ sessions.current_total?.toLocaleString() }}</span>
          </div>
          <v-progress-linear v-show="loadingSessions" indeterminate class="mb-4" />
          <apexchart v-show="!loadingSessions" type="line" height="220" :options="sessionsChartOptions" :series="sessionsChartSeries" />
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Conversion rate over time</div>
          <div class="d-flex align-center ga-2 mb-2">
            <span class="text-h5 font-weight-bold">{{ latestConversionRate }}%</span>
          </div>
          <v-progress-linear v-show="loadingConversion" indeterminate class="mb-4" />
          <apexchart v-show="!loadingConversion" type="line" height="220" :options="conversionChartOptions" :series="conversionChartSeries" />
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Conversion rate breakdown</div>
          <div class="d-flex align-center ga-2 mb-3">
            <span class="text-h5 font-weight-bold">{{ funnelCompletedRate }}%</span>
          </div>
          <v-row dense>
            <v-col v-for="stage in funnelStages" :key="stage.key" cols="3">
              <div class="text-caption text-medium-emphasis">{{ stage.label }}</div>
              <div class="text-body-1 font-weight-bold">{{ stage.percent }}%</div>
              <div class="text-caption text-medium-emphasis">
                {{ stage.value.toLocaleString() }}
                <span :class="stage.change >= 0 ? 'text-success' : 'text-error'">
                  {{ stage.change >= 0 ? '↗' : '↘' }} {{ Math.abs(stage.change) }}%
                </span>
              </div>
            </v-col>
          </v-row>
          <div class="d-flex align-end ga-1 mt-4" style="height: 120px;">
            <div v-for="stage in funnelStages" :key="stage.key + '-bar'" style="flex:1; height:100%; display:flex; align-items:flex-end;">
              <div class="funnel-bar" :style="{ height: stage.barHeight + '%' }" />
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-2">
      <v-col cols="12" md="4">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Sessions by device type</div>
          <div class="d-flex align-center ga-4 mt-2">
            <apexchart type="donut" width="170" height="170" :options="deviceDonutOptions" :series="deviceSeries" />
            <div style="flex:1;">
              <div v-for="d in deviceLegend" :key="d.label" class="d-flex justify-space-between align-center mb-2">
                <span><v-icon icon="mdi-circle" :color="d.color" size="9" class="mr-1" />{{ d.label }}</span>
                <span class="font-weight-medium">{{ d.value }}</span>
              </div>
            </div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="5">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Sessions by location</div>
          <dual-bar-comparison-list :items="locationItems" value-format="number" />
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Total sales by social referrer</div>
          <div v-if="!socialReferrers.length" class="text-body-2 text-medium-emphasis text-center py-8">
            No data for this date range
          </div>
          <div v-for="r in socialReferrers" :key="r.name" class="d-flex justify-space-between">
            <span>{{ r.name }}</span>
            <span class="font-weight-medium">{{ formatCurrency(r.value) }}</span>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import DualBarComparisonList from './DualBarComparisonList.vue';

export default {
  name: 'SessionsAnalytics',
  components: { DualBarComparisonList },

  data() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 29);

    return {
      from: thirtyDaysAgo.toISOString().slice(0, 10),
      to: today.toISOString().slice(0, 10),

      sessions: { current: [], previous: [], current_total: 0 },
      loadingSessions: false,

      conversion: { current: [], previous: [] },
      loadingConversion: false,

      funnel: {},
      deviceBreakdown: [],
      locations: [],
      socialReferrers: [],
    };
  },

  computed: {
    sessionsChartSeries() {
      return [{ name: 'Current', data: this.sessions.current }, { name: 'Previous', data: this.sessions.previous }];
    },
    sessionsChartOptions() {
      return this.dualLineOptions((v) => v.toLocaleString());
    },

    conversionChartSeries() {
      return [{ name: 'Current', data: this.conversion.current }, { name: 'Previous', data: this.conversion.previous }];
    },
    conversionChartOptions() {
      return this.dualLineOptions((v) => `${v}%`);
    },

    latestConversionRate() {
      const arr = this.conversion.current;
      return arr.length ? arr[arr.length - 1] : 0;
    },

    funnelStages() {
      const labels = { sessions: 'Sessions', added_to_cart: 'Added to cart', reached_checkout: 'Reached checkout', completed: 'Completed' };
      const maxValue = this.funnel.sessions?.value || 1;

      return Object.keys(labels).map((key) => {
        const stage = this.funnel[key] || { value: 0, percent_of_sessions: 0, change: 0 };
        return {
          key,
          label: labels[key],
          value: stage.value,
          percent: stage.percent_of_sessions,
          change: stage.change,
          barHeight: (stage.value / maxValue) * 100,
        };
      });
    },

    funnelCompletedRate() {
      return this.funnel.completed?.percent_of_sessions ?? 0;
    },

    deviceSeries() {
      return this.deviceBreakdown.map((d) => d.unique_visitors);
    },

    deviceLegend() {
      const colors = ['#1E88E5', '#5C33F6', '#8B7CF7', '#E5507B'];
      return this.deviceBreakdown.map((d, i) => ({ label: d.label, value: d.unique_visitors, color: colors[i % colors.length] }));
    },

    deviceDonutOptions() {
      const colors = ['#1E88E5', '#5C33F6', '#8B7CF7', '#E5507B'];
      return {
        chart: { type: 'donut' },
        labels: this.deviceBreakdown.map((d) => d.label),
        colors,
        stroke: { width: 2, colors: ['#ffffff'] },
        dataLabels: { enabled: false },
        legend: { show: false },
        plotOptions: {
          pie: { donut: { size: '78%', labels: {
            show: true,
            value: { show: true, fontSize: '20px', fontWeight: 700, formatter: (v) => v.toLocaleString() },
            total: { show: true, showAlways: true, label: 'Total', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString() },
          } } },
        },
      };
    },

    locationItems() {
      return this.locations.map((l) => ({
        label: `${l.country} · ${l.region} · ${l.city}`,
        current: l.sessions,
        previous: l.previous_sessions,
        change: l.change,
      }));
    },
  },

  mounted() {
    this.fetchAll();
  },

  methods: {
    dualLineOptions(yFormatter) {
      return {
        chart: { toolbar: { show: false }, zoom: { enabled: false } },
        stroke: { curve: 'smooth', width: [2, 1.5], dashArray: [0, 5] },
        colors: ['#1E88E5', '#a9d1f5'],
        dataLabels: { enabled: false },
        xaxis: { labels: { show: false } },
        yaxis: { labels: { formatter: yFormatter } },
        legend: { show: false },
        tooltip: { shared: true, y: { formatter: yFormatter } },
      };
    },

    fetchAll() {
      this.fetchSessions();
      this.fetchConversion();
      this.fetchFunnel();
      this.fetchDeviceBreakdown();
      this.fetchLocations();
      this.fetchSocialReferrers();
    },

    async fetchSessions() {
      this.loadingSessions = true;
      try {
        const { data } = await axios.get('/sadmin/analytics/sessions-over-time-range', { params: { from: this.from, to: this.to } });
        this.sessions = data;
      } catch (e) { console.error(e); } finally { this.loadingSessions = false; }
    },

    async fetchConversion() {
      this.loadingConversion = true;
      try {
        const { data } = await axios.get('/sadmin/analytics/conversion-rate-over-time', { params: { from: this.from, to: this.to } });
        this.conversion = data;
      } catch (e) { console.error(e); } finally { this.loadingConversion = false; }
    },

    async fetchFunnel() {
      try {
        const { data } = await axios.get('/sadmin/analytics/conversion-breakdown', { params: { from: this.from, to: this.to } });
        this.funnel = data.stages;
      } catch (e) { console.error(e); }
    },

    async fetchDeviceBreakdown() {
      try {
        const { data } = await axios.get('/sadmin/analytics/traffic-breakdown', { params: { from: this.from, to: this.to, by: 'device_type' } });
        this.deviceBreakdown = data.breakdown || [];
      } catch (e) { console.error(e); }
    },

    async fetchLocations() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sessions-by-location-comparison', { params: { from: this.from, to: this.to, limit: 8 } });
        this.locations = data.locations || [];
      } catch (e) { console.error(e); }
    },

    async fetchSocialReferrers() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sales-by-social-referrer', { params: { from: this.from, to: this.to } });
        this.socialReferrers = data.referrers || [];
      } catch (e) { console.error(e); }
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value || 0);
    },
  },
};
</script>

<style scoped>
.funnel-bar { width: 100%; background: #3B5BFA; border-radius: 3px 3px 0 0; }
</style>
