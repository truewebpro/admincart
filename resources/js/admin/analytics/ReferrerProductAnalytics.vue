<template>
  <v-container>
    <v-row class="mb-2" align="center">
      <v-col cols="12" md="6"><h2 class="text-h5 font-weight-bold">Referrers &amp; Products</h2></v-col>
      <v-col cols="12" md="6" class="d-flex justify-end">
        <v-text-field v-model="from" type="date" label="From" density="compact" variant="outlined" hide-details style="max-width:160px" class="mr-2" @update:model-value="fetchAll" />
        <v-text-field v-model="to" type="date" label="To" density="compact" variant="outlined" hide-details style="max-width:160px" @update:model-value="fetchAll" />
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="6">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-3">Sessions by referrer</div>
          <dual-bar-comparison-list :items="referrerItems" value-format="number" />
        </v-card>
      </v-col>

<!--      <v-col cols="12" md="4">-->
<!--        <v-card elevation="1" rounded="lg" class="pa-4">-->
<!--          <div class="text-subtitle-2 text-decoration-underline mb-3">Total sales by POS location</div>-->
<!--          <div class="text-body-2 text-medium-emphasis d-flex align-center justify-center" style="height: 260px;">-->
<!--            No data for this date range-->
<!--          </div>-->
<!--        </v-card>-->
<!--      </v-col>-->

      <v-col cols="12" md="6">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-3">Products by sell-through rate</div>
          <dual-bar-comparison-list :items="sellThroughItems" value-format="percent" />
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import DualBarComparisonList from './DualBarComparisonList.vue';

export default {
  name: 'ReferrerProductAnalytics',
  components: { DualBarComparisonList },

  data() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 29);

    return {
      from: thirtyDaysAgo.toISOString().slice(0, 10),
      to: today.toISOString().slice(0, 10),
      referrers: [],
      sellThrough: [],
    };
  },

  computed: {
    referrerItems() {
      return this.referrers.map((r) => ({
        label: `${r.medium} · ${r.source} · ${r.city}`,
        current: r.sessions,
        previous: r.previous_sessions,
        change: r.change,
      }));
    },

    sellThroughItems() {
      return this.sellThrough.map((p) => ({
        label: p.title,
        current: p.sell_through_rate,
        previous: p.previous_rate,
        change: p.change,
      }));
    },
  },

  mounted() {
    this.fetchAll();
  },

  methods: {
    fetchAll() {
      this.fetchReferrers();
      this.fetchSellThrough();
    },

    async fetchReferrers() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sessions-by-referrer', { params: { from: this.from, to: this.to, limit: 10 } });
        this.referrers = data.referrers || [];
      } catch (e) { console.error(e); }
    },

    async fetchSellThrough() {
      try {
        const { data } = await axios.get('/sadmin/analytics/products-sell-through', { params: { from: this.from, to: this.to, limit: 10 } });
        this.sellThrough = data.products || [];
      } catch (e) { console.error(e); }
    },
  },
};
</script>
