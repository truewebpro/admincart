<template>
  <v-container>
      <v-row>
          <v-col cols="12" md="5" style="max-height: 90vh; overflow-y: auto;">
              <div class="d-flex align-center mb-3">
                  <v-icon icon="mdi-earth" class="mr-2" />
                  <span class="text-h6 font-weight-bold">Live View</span>
                  <v-icon icon="mdi-circle" color="primary" size="8" class="ml-2 mr-1" />
                  <span class="text-caption text-medium-emphasis">Just now</span>
              </div>

              <v-row dense>
                  <v-col cols="6"><v-card elevation="1" rounded="lg" class="pa-3"><div class="text-caption text-decoration-underline">Visitors right now</div><div class="text-h6 font-weight-bold mt-1">{{ liveCount }}</div></v-card></v-col>
                  <v-col cols="6"><v-card elevation="1" rounded="lg" class="pa-3"><div class="text-caption text-decoration-underline">Total sales</div><div class="text-body-1 font-weight-bold mt-1">{{ formatCurrency(dailyStats.total_sales?.value) }} <span :class="changeClass(dailyStats.total_sales?.change)">{{ changeArrow(dailyStats.total_sales?.change) }} {{ Math.abs(dailyStats.total_sales?.change || 0) }}%</span></div></v-card></v-col>
                  <v-col cols="6"><v-card elevation="1" rounded="lg" class="pa-3"><div class="text-caption text-decoration-underline">Sessions</div><div class="text-body-1 font-weight-bold mt-1">{{ dailyStats.sessions?.value }} <span :class="changeClass(dailyStats.sessions?.change)">{{ changeArrow(dailyStats.sessions?.change) }} {{ Math.abs(dailyStats.sessions?.change || 0) }}%</span></div></v-card></v-col>
                  <v-col cols="6"><v-card elevation="1" rounded="lg" class="pa-3"><div class="text-caption text-decoration-underline">Orders</div><div class="text-body-1 font-weight-bold mt-1">{{ dailyStats.orders?.value }} <span :class="changeClass(dailyStats.orders?.change)">{{ changeArrow(dailyStats.orders?.change) }} {{ Math.abs(dailyStats.orders?.change || 0) }}%</span></div></v-card></v-col>
              </v-row>

              <v-card elevation="1" rounded="lg" class="pa-3 mt-3">
                  <div class="text-caption text-decoration-underline mb-2">Customer behavior</div>
                  <v-row dense>
                      <v-col cols="4"><div class="text-caption text-medium-emphasis">Active carts</div><div class="text-body-1 font-weight-bold">{{ behavior.active_carts }}</div></v-col>
                      <v-col cols="4"><div class="text-caption text-medium-emphasis">Checking out</div><div class="text-body-1 font-weight-bold">{{ behavior.checking_out }}</div></v-col>
                      <v-col cols="4"><div class="text-caption text-medium-emphasis">Purchased</div><div class="text-body-1 font-weight-bold">{{ behavior.purchased }}</div></v-col>
                  </v-row>
                  <div class="d-flex align-end ga-1 mt-3" style="height: 60px;">
                      <div style="flex:1; background:#3B5BFA; border-radius:3px 3px 0 0;" :style="{ height: behaviorBarHeight(behavior.active_carts) + '%' }" />
                      <div style="flex:1; background:#3B5BFA; opacity:0.5; border-radius:3px 3px 0 0;" :style="{ height: behaviorBarHeight(behavior.checking_out) + '%' }" />
                  </div>
              </v-card>

              <v-card elevation="1" rounded="lg" class="pa-3 mt-3">
                  <div class="text-caption text-decoration-underline mb-2">Sessions by location</div>
                  <div v-for="loc in locations" :key="loc.city" class="mb-3">
                      <div class="text-caption mb-1">{{ loc.country }} · {{ loc.region }} · {{ loc.city }}</div>
                      <div class="d-flex align-center ga-2">
                          <v-progress-linear :model-value="(loc.sessions / maxLocationSessions) * 100" height="6" rounded color="primary" />
                          <span class="text-caption" style="min-width: 24px;">{{ loc.sessions }}</span>
                      </div>
                  </div>
              </v-card>

              <v-card elevation="1" rounded="lg" class="pa-3 mt-3">
                  <div class="text-caption text-decoration-underline mb-2">New vs returning customers</div>
                  <v-row dense>
                      <v-col cols="6">
                          <div class="text-caption"><v-icon icon="mdi-circle" color="#1E88E5" size="8" class="mr-1" />New</div>
                          <div class="text-h6 font-weight-bold">{{ customerSplit.new?.customer_count ?? 0 }}</div>
                      </v-col>
                      <v-col cols="6">
                          <div class="text-caption"><v-icon icon="mdi-circle" color="#7C4DFF" size="8" class="mr-1" />Returning</div>
                          <div class="text-h6 font-weight-bold">{{ customerSplit.returning?.customer_count ?? 0 }}</div>
                      </v-col>
                  </v-row>
              </v-card>
          </v-col>

          <v-col cols="12" md="7">
              <live-globe :visitors="liveVisitors" style="height: 90vh;" />
          </v-col>
      </v-row>
  </v-container>
</template>

<script>
import LiveGlobe from './LiveGlobe.vue';

const POLL_INTERVAL_MS = 15000;

export default {
  name: 'LiveViewPage',
  components: { LiveGlobe },

  data() {
    return {
      liveCount: 0,
      liveVisitors: [],
      dailyStats: {},
      behavior: { active_carts: 0, checking_out: 0, purchased: 0 },
      locations: [],
      customerSplit: {},
      pollTimer: null,
    };
  },

  computed: {
    maxLocationSessions() {
      return Math.max(...this.locations.map((l) => l.sessions), 1);
    },
  },

  mounted() {
    this.fetchAll();
    this.pollTimer = setInterval(this.fetchLive, POLL_INTERVAL_MS);
  },

  beforeUnmount() {
    clearInterval(this.pollTimer);
  },

  methods: {
    fetchAll() {
      this.fetchLive();
      this.fetchDailyStats();
      this.fetchBehavior();
      this.fetchLocations();
      this.fetchCustomerSplit();
    },

    async fetchLive() {
      try {
        const { data } = await axios.get('/sadmin/analytics/live-now');
        this.liveCount = data.live_count;
      } catch (e) { console.error(e); }
      try {
        const { data } = await axios.get('/sadmin/analytics/live-locations');
        this.liveVisitors = data.visitors || [];
      } catch (e) { console.error(e); }
    },

    async fetchDailyStats() {
      try {
        const { data } = await axios.get('/sadmin/analytics/daily-stats');
        this.dailyStats = data;
      } catch (e) { console.error(e); }
    },

    async fetchBehavior() {
      try {
        const { data } = await axios.get('/sadmin/analytics/customer-behavior');
        this.behavior = data;
      } catch (e) { console.error(e); }
    },

    async fetchLocations() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sessions-by-location');
        this.locations = data.locations || [];
      } catch (e) { console.error(e); }
    },

    async fetchCustomerSplit() {
      try {
        const { data } = await axios.get('/sadmin/analytics/customer-split');
        this.customerSplit = data;
      } catch (e) { console.error(e); }
    },

    behaviorBarHeight(value) {
      const max = Math.max(this.behavior.active_carts, this.behavior.checking_out, 1);
      return (value / max) * 100;
    },

    changeClass(v) {
      if (v === undefined || v === null) return '';
      return v >= 0 ? 'text-success' : 'text-error';
    },
    changeArrow(v) {
      if (v === undefined || v === null) return '';
      return v >= 0 ? '↗' : '↘';
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value || 0);
    },
  },
};
</script>
