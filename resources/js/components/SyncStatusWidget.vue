<template>
  <v-chip
    v-if="loading"
    size="small"
    variant="tonal"
    color="grey"
  >
    Checking status...
  </v-chip>

  <div v-else class="d-flex align-center ga-2 flex-wrap">
    <!-- Never run -->
    <v-chip v-if="!run" size="small" variant="tonal" color="grey">
      Not yet synced
    </v-chip>

    <!-- Currently running -->
    <v-chip v-else-if="run.status === 'processing'" size="small" variant="tonal" color="info">
      <v-progress-circular indeterminate size="12" width="2" class="mr-1" />
      Running...
    </v-chip>

    <!-- Failed -->
    <v-tooltip v-else-if="run.status === 'failed'" location="top">
      <template #activator="{ props }">
        <v-chip v-bind="props" size="small" variant="tonal" color="error" prepend-icon="mdi-alert-circle">
          Failed {{ relativeTime(run.finished_at) }}
        </v-chip>
      </template>
      <span>{{ run.error_message || 'Unknown error' }}</span>
    </v-tooltip>

    <!-- Completed -->
    <template v-else-if="run.status === 'completed'">
      <v-chip size="small" variant="tonal" color="success" prepend-icon="mdi-check">
        Completed {{ relativeTime(run.finished_at) }}
      </v-chip>
      <span v-for="(value, key) in displaySummary" :key="key" class="text-caption text-medium-emphasis">
        {{ formatLabel(key) }}: {{ value }}
      </span>
      <v-chip
        v-if="run.result_summary?.likely_permission_issue"
        size="small"
        variant="tonal"
        color="warning"
        prepend-icon="mdi-lock-alert"
      >
        Check "View product costs" permission
      </v-chip>
    </template>

    <!-- Trigger button — only shown if a syncEndpoint was provided -->
    <v-btn
      v-if="syncEndpoint"
      size="small"
      variant="text"
      :disabled="run?.status === 'processing' || triggering"
      :loading="triggering"
      @click="trigger"
    >
      {{ run ? 'Sync again' : 'Sync now' }}
    </v-btn>
  </div>
</template>

<script>
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

const POLL_INTERVAL_MS = 4000;

export default {
  name: 'SyncStatusWidget',

  props: {
    shopId: { type: [Number, String], required: true },

    // Must match one of the JOB_TYPES in JobRunController — e.g.
    // 'collection_seo_sync', 'product_seo_sync', 'blog_seo_sync',
    // 'variant_cost_sync', 'variant_backfill'.
    jobType: { type: String, required: true },

    // Optional — if provided, shows a "Sync now" button that POSTs
    // here. If omitted, this widget is purely a status display (for
    // pages that already have their own sync button elsewhere).
    syncEndpoint: { type: String, default: null },
  },

  data() {
    return {
      run: null,
      loading: true,
      triggering: false,
      pollTimer: null,
    };
  },

  computed: {
    // Renders whatever fields exist in result_summary generically,
    // EXCEPT likely_permission_issue (handled as its own distinct
    // warning chip above, not a plain key-value line) — this is what
    // makes the same widget work across all five job types without
    // per-type customization.
    displaySummary() {
      if (!this.run?.result_summary) return {};
      const { likely_permission_issue, ...rest } = this.run.result_summary;
      return rest;
    },
  },

  mounted() {
    this.fetchStatus();
  },

  beforeUnmount() {
    clearInterval(this.pollTimer);
  },

  methods: {
    formatLabel(key) {
      return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    },

    relativeTime(timestamp) {
      return timestamp ? dayjs(timestamp).fromNow() : '';
    },

    async fetchStatus() {
      try {
        const { data } = await axios.get(`/superadmin/shopify/${this.shopId}/job-runs`);
        this.run = data.latest_by_type?.[this.jobType] ?? null;

        // Auto-poll while running, so the widget updates live without
        // the user needing to refresh the page — stop as soon as it's
        // no longer processing.
        if (this.run?.status === 'processing' && !this.pollTimer) {
          this.pollTimer = setInterval(this.fetchStatus, POLL_INTERVAL_MS);
        } else if (this.run?.status !== 'processing' && this.pollTimer) {
          clearInterval(this.pollTimer);
          this.pollTimer = null;
        }
      } catch (e) {
        console.error('Failed to fetch sync status', e);
      } finally {
        this.loading = false;
      }
    },

    async trigger() {
      if (!this.syncEndpoint) return;

      this.triggering = true;
      try {
        await axios.post(this.syncEndpoint);
        await this.fetchStatus(); // picks up the new "processing" state immediately, starting the poll loop
      } catch (e) {
        console.error('Failed to trigger sync', e);
      } finally {
        this.triggering = false;
      }
    },
  },
};
</script>
