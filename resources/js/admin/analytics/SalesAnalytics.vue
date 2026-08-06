<template>
  <v-container>
    <v-row class="mb-2" align="center">
      <v-col cols="12" md="6"><h2 class="text-h5 font-weight-bold">Sales</h2></v-col>
      <v-col cols="12" md="6" class="d-flex justify-end">
        <v-text-field v-model="from" type="date" label="From" density="compact" variant="outlined" hide-details style="max-width:160px" class="mr-2" @update:model-value="fetchAll" />
        <v-text-field v-model="to" type="date" label="To" density="compact" variant="outlined" hide-details style="max-width:160px" @update:model-value="fetchAll" />
      </v-col>
    </v-row>

    <!-- Stat cards -->
    <v-row>
      <v-col v-for="card in statCards" :key="card.key" cols="12" sm="3">
        <v-card elevation="1" rounded="lg" class="pa-4" :loading="loadingCards">
          <div class="text-caption text-medium-emphasis text-decoration-underline">{{ card.label }}</div>
          <div class="d-flex align-center justify-space-between mt-1">
            <div>
              <span class="text-h6 font-weight-bold">{{ card.formattedValue }}</span>
              <v-chip :color="card.change >= 0 ? 'success' : 'error'" size="x-small" variant="flat" class="ml-1">
                {{ card.change >= 0 ? '+' : '' }}{{ card.change }}%
              </v-chip>
            </div>
            <apexchart type="line" width="90" height="30" :options="sparkOptions" :series="[{ data: card.sparkline }]" />
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Total sales over time + breakdown -->
    <v-row class="mt-2">
      <v-col cols="12" md="8">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Total sales over time</div>
          <div class="d-flex align-center ga-2 mb-2">
            <span class="text-h5 font-weight-bold">{{ formatCurrency(totalSales.current_total) }}</span>
          </div>
          <v-progress-linear v-show="loadingSalesChart" indeterminate class="mb-4" />
          <apexchart v-show="!loadingSalesChart" type="line" height="280" :options="salesChartOptions" :series="salesChartSeries" />
          <div class="d-flex ga-4 mt-1">
            <span class="text-caption"><v-icon icon="mdi-circle" color="#1E88E5" size="8" class="mr-1" />{{ totalSales.current_label }}</span>
            <span class="text-caption"><v-icon icon="mdi-circle" color="#a9d1f5" size="8" class="mr-1" />{{ totalSales.previous_label }}</span>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-3">Total sales breakdown</div>
          <div v-for="(row, key) in breakdown" :key="key" class="breakdown-row">
            <span :class="key === 'total_sales' ? 'font-weight-bold' : 'text-primary'">{{ breakdownLabels[key] }}</span>
            <span class="d-flex align-center ga-2">
              <span class="font-weight-medium">{{ formatCurrency(row.value) }}</span>
              <span v-if="row.change !== null" :class="row.change >= 0 ? 'text-success' : 'text-error'" class="text-caption font-weight-bold">
                {{ row.change >= 0 ? '↗' : '↘' }} {{ Math.abs(row.change) }}%
              </span>
              <span v-else class="text-medium-emphasis">—</span>
            </span>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Channel donut, AOV, sales by product -->
    <v-row class="mt-2">
      <v-col cols="12" md="3">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Total sales by sales channel</div>
          <div class="d-flex flex-column align-center ga-3 mt-3">
            <apexchart type="donut" width="180" height="180" :options="channelDonutOptions" :series="channelSeries" />
            <div class="text-caption text-center">
              <div><v-icon icon="mdi-circle" color="#1E88E5" size="9" class="mr-1" />Online Store</div>
            </div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Average order value over time</div>
          <apexchart type="line" height="200" :options="aovChartOptions" :series="aovChartSeries" />
        </v-card>
      </v-col>

      <v-col cols="12" md="5">
        <v-card elevation="1" rounded="lg" class="pa-4">
          <div class="text-subtitle-2 text-decoration-underline mb-2">Total sales by product</div>
          <dual-bar-comparison-list :items="productItems" value-format="currency" />
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import DualBarComparisonList from './DualBarComparisonList.vue';

export default {
  name: 'SalesAnalytics',
  components: { DualBarComparisonList },

  data() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 29);

    return {
      from: thirtyDaysAgo.toISOString().slice(0, 10),
      to: today.toISOString().slice(0, 10),

      cards: null,
      loadingCards: false,

      totalSales: { current: [], previous: [], current_label: '', previous_label: '', current_total: 0 },
      loadingSalesChart: false,

      breakdown: {},
      breakdownLabels: {
        gross_sales: 'Gross sales', discounts: 'Discounts', sales_reversals: 'Sales reversals',
        net_sales: 'Net sales', shipping_charges: 'Shipping charges', return_fees: 'Return fees',
        taxes: 'Taxes', total_sales: 'Total sales',
      },

      channelSeries: [0],
      aov: { current: [], previous: [] },
      products: [],
    };
  },

  computed: {
    statCards() {
      if (!this.cards) return [];
      return [
        { key: 'gross_sales', label: 'Gross sales', formattedValue: this.formatCurrency(this.cards.gross_sales.value), change: this.cards.gross_sales.change, sparkline: this.cards.gross_sales.sparkline },
        { key: 'returning_customer_rate', label: 'Returning customer rate', formattedValue: `${this.cards.returning_customer_rate.value}%`, change: this.cards.returning_customer_rate.change, sparkline: this.cards.returning_customer_rate.sparkline },
        { key: 'orders_fulfilled', label: 'Orders fulfilled', formattedValue: this.cards.orders_fulfilled.value, change: this.cards.orders_fulfilled.change, sparkline: this.cards.orders_fulfilled.sparkline },
        { key: 'orders', label: 'Orders', formattedValue: this.cards.orders.value, change: this.cards.orders.change, sparkline: this.cards.orders.sparkline },
      ];
    },

    sparkOptions() {
      return {
        chart: { sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 1.5 },
        colors: ['#1E88E5'],
        tooltip: { enabled: false },
      };
    },

    salesChartSeries() {
      return [
        { name: this.totalSales.current_label, data: this.totalSales.current },
        { name: this.totalSales.previous_label, data: this.totalSales.previous },
      ];
    },

    salesChartOptions() {
      return {
        chart: { toolbar: { show: false }, zoom: { enabled: false } },
        stroke: { curve: 'smooth', width: [2, 1.5], dashArray: [0, 5] },
        colors: ['#1E88E5', '#a9d1f5'],
        dataLabels: { enabled: false },
        xaxis: { categories: this.totalSales.current.map((_, i) => (i % 3 === 0 ? `${i + 1}` : '')) },
        yaxis: { labels: { formatter: (v) => this.formatCurrency(v) } },
        legend: { show: false },
        tooltip: { shared: true, y: { formatter: (v) => this.formatCurrency(v) } },
      };
    },

    channelDonutOptions() {
      return {
        chart: { type: 'donut' },
        labels: ['Online Store'],
        colors: ['#1E88E5'],
        stroke: { width: 3, colors: ['#ffffff'] },
        dataLabels: { enabled: false },
        legend: { show: false },
        plotOptions: {
          pie: { donut: { size: '82%', labels: {
            show: true, name: { show: false },
            value: { show: true, fontSize: '20px', fontWeight: 700, formatter: (v) => this.formatCurrency(v) },
            total: { show: true, showAlways: true, label: 'Total sales', formatter: (w) => this.formatCurrency(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
          } } },
        },
        tooltip: { y: { formatter: (v) => this.formatCurrency(v) } },
      };
    },

    aovChartSeries() {
      return [{ name: 'Current', data: this.aov.current }, { name: 'Previous', data: this.aov.previous }];
    },

    aovChartOptions() {
      return {
        chart: { toolbar: { show: false }, zoom: { enabled: false } },
        stroke: { curve: 'smooth', width: [2, 1.5], dashArray: [0, 5] },
        colors: ['#1E88E5', '#a9d1f5'],
        dataLabels: { enabled: false },
        xaxis: { labels: { show: false } },
        yaxis: { labels: { formatter: (v) => this.formatCurrency(v) } },
        legend: { show: false },
        tooltip: { shared: true, y: { formatter: (v) => this.formatCurrency(v) } },
      };
    },

    productItems() {
      return this.products.map((p) => ({
        label: p.title,
        current: p.current_revenue,
        previous: p.previous_revenue,
        change: p.change,
      }));
    },
  },

  mounted() {
    this.fetchAll();
  },

  methods: {
    fetchAll() {
      this.fetchCards();
      this.fetchSalesChart();
      this.fetchBreakdown();
      this.fetchChannel();
      this.fetchAov();
      this.fetchProducts();
    },

    async fetchCards() {
      this.loadingCards = true;
      try {
        const { data } = await axios.get('/sadmin/analytics/sales-stat-cards', { params: { from: this.from, to: this.to } });
        this.cards = data;
      } catch (e) { console.error(e); } finally { this.loadingCards = false; }
    },

    async fetchSalesChart() {
      this.loadingSalesChart = true;
      try {
        const { data } = await axios.get('/sadmin/analytics/total-sales-over-time', { params: { from: this.from, to: this.to } });
        this.totalSales = data;
      } catch (e) { console.error(e); } finally { this.loadingSalesChart = false; }
    },

    async fetchBreakdown() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sales-breakdown', { params: { from: this.from, to: this.to } });
        this.breakdown = data.breakdown;
      } catch (e) { console.error(e); }
    },

    async fetchChannel() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sales-by-channel', { params: { from: this.from, to: this.to } });
        this.channelSeries = (data.channels || []).map((c) => c.value);
      } catch (e) { console.error(e); }
    },

    async fetchAov() {
      try {
        const { data } = await axios.get('/sadmin/analytics/aov-over-time', { params: { from: this.from, to: this.to } });
        this.aov = data;
      } catch (e) { console.error(e); }
    },

    async fetchProducts() {
      try {
        const { data } = await axios.get('/sadmin/analytics/sales-by-product', { params: { from: this.from, to: this.to, limit: 10 } });
        this.products = data.products || [];
      } catch (e) { console.error(e); }
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value || 0);
    },
  },
};
</script>

<style scoped>
.breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  font-size: 13px;
}
.breakdown-row:last-child { border-bottom: none; }
</style>
