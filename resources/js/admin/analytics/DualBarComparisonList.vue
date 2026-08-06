<template>
  <div>
    <div v-for="(item, idx) in items" :key="idx" class="dual-bar-row">
      <div class="dual-bar-label">{{ item.label }}</div>

      <div class="dual-bar-line">
        <div class="dual-bar-track">
          <div class="dual-bar-fill dual-bar-fill--current" :style="{ width: barWidth(item.current) }" />
        </div>
        <div class="dual-bar-value">
          {{ formatValue(item.current) }}
          <span v-if="item.change !== null" :class="changeClass(item.change)">
            {{ changeArrow(item.change) }} {{ formatChange(item.change) }}
          </span>
          <span v-else class="text-medium-emphasis">—</span>
        </div>
      </div>

      <div class="dual-bar-line dual-bar-line--previous">
        <div class="dual-bar-track">
          <div class="dual-bar-fill dual-bar-fill--previous" :style="{ width: barWidth(item.previous) }" />
        </div>
        <div class="dual-bar-value">{{ formatValue(item.previous) }}</div>
      </div>
    </div>

    <div v-if="!items.length" class="text-body-2 text-medium-emphasis text-center py-8">
      No data for this date range
    </div>
  </div>
</template>

<script>
export default {
  name: 'DualBarComparisonList',

  props: {
    items: {
      // each item: { label: string, current: number, previous: number, change: number|null }
      type: Array,
      default: () => [],
    },
    // 'number' | 'currency' | 'percent'
    valueFormat: {
      type: String,
      default: 'number',
    },
  },

  computed: {
    maxValue() {
      const all = this.items.flatMap((i) => [i.current, i.previous]);
      return Math.max(...all, 1); // avoid divide-by-zero if everything is 0
    },
  },

  methods: {
    barWidth(value) {
      return `${(value / this.maxValue) * 100}%`;
    },

    formatValue(value) {
      if (this.valueFormat === 'currency') {
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value);
      }
      if (this.valueFormat === 'percent') {
        return `${Number(value).toFixed(2)}%`;
      }
      return Number(value).toLocaleString();
    },

    formatChange(value) {
      const abs = Math.abs(value);
      return `${abs >= 1000 ? (abs / 1000).toFixed(1) + 'K' : abs}%`;
    },

    changeArrow(value) {
      return value > 0 ? '↗' : value < 0 ? '↘' : '—';
    },

    changeClass(value) {
      if (value > 0) return 'text-success font-weight-bold';
      if (value < 0) return 'text-error font-weight-bold';
      return 'text-medium-emphasis';
    },
  },
};
</script>

<style scoped>
.dual-bar-row { margin-top: 16px; }
.dual-bar-row:first-child { margin-top: 0; }

.dual-bar-label {
  font-size: 12px;
  color: #333;
  margin-bottom: 5px;
  line-height: 1.4;
}

.dual-bar-line {
  display: flex;
  align-items: center;
  gap: 8px;
}
.dual-bar-line--previous { margin-top: 4px; }

.dual-bar-track {
  flex: 1;
  height: 8px;
  background: #eef0f3;
  border-radius: 4px;
  overflow: hidden;
}

.dual-bar-fill { height: 100%; border-radius: 4px; }
.dual-bar-fill--current { background: #1E88E5; }
.dual-bar-fill--previous { background: #a9d9f5; }

.dual-bar-value {
  font-size: 12px;
  color: #555;
  white-space: nowrap;
  min-width: 40px;
  text-align: right;
}
</style>
