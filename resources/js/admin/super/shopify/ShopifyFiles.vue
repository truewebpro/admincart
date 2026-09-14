<template>
  <v-row>
    <v-col cols="12">
      <v-card class="mb-3">
        <v-card-text class="d-flex align-center justify-space-between flex-wrap ga-2">
          <div>
            <h2 class="text-h5 font-weight-bold">Shopify Files</h2>
            <span class="text-caption text-medium-emphasis">
              Browse files from your Shopify Files library and pull them into your own media library.
            </span>
          </div>

          <v-btn-toggle v-model="onlyUnused" density="compact" mandatory @update:model-value="resetAndFetch">
            <v-btn :value="true" size="small">Unused only</v-btn>
            <v-btn :value="false" size="small">All files</v-btn>
          </v-btn-toggle>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col cols="12">
      <v-card>
        <v-data-table
          :items="files"
          :headers="headers"
          :loading="loading"
          itemsPerPage="-1"
          mobileBreakpoint="sm"
        >
          <template #item.preview="{item}">
            <v-img
              v-if="isImage(item)"
              :src="previewUrl(item)"
              width="56" height="56" cover class="rounded"
            />
            <v-avatar v-else size="56" color="grey-lighten-3" class="rounded">
              <v-icon icon="mdi-file-outline" />
            </v-avatar>
          </template>

          <template #item.filename="{item}">
            <div class="text-body-2 text-truncate" style="max-width: 320px;">
              {{ displayName(item) }}
            </div>
            <div class="text-caption text-medium-emphasis">{{ mimeType(item) }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.alt }}</div>
          </template>

          <template #item.type="{item}">
            <v-chip size="small" variant="tonal" :color="isImage(item) ? 'blue' : 'grey'">
              {{ isImage(item) ? 'Image' : 'File' }}
            </v-chip>
          </template>

          <template #item.status="{item}">
            <v-chip
              size="small" variant="tonal"
              :color="item.fileStatus === 'READY' ? 'success' : (item.fileStatus === 'FAILED' ? 'error' : 'warning')"
            >
              {{ item.fileStatus }}
            </v-chip>
          </template>

          <template #item.actions="{item}">
            <v-chip
              v-if="isAlreadySaved(item)"
              size="small" color="success" variant="tonal" prepend-icon="mdi-check"
            >
              Saved
            </v-chip>
            <v-btn
              v-else
              size="small" variant="tonal"
              :disabled="item.fileStatus !== 'READY' || creatingIds.includes(item.id)"
              :loading="creatingIds.includes(item.id)"
              @click="createSingle(item)"
            >
              Import
            </v-btn>
          </template>

          <template #bottom>
            <div class="d-flex align-center justify-space-between pa-3">
              <span class="text-caption text-medium-emphasis">{{ files.length }} files on this page</span>
              <div class="d-flex ga-2">
                <v-btn size="small" variant="tonal" :disabled="!pageInfo.hasPreviousPage || loading" @click="goPrevious">
                  <v-icon icon="mdi-chevron-left" start />
                  Previous
                </v-btn>
                <v-btn size="small" variant="tonal" :disabled="!pageInfo.hasNextPage || loading" @click="goNext">
                  Next
                  <v-icon icon="mdi-chevron-right" end />
                </v-btn>
              </div>
            </div>
          </template>
        </v-data-table>

        <v-snackbar v-model="showResultSnackbar" :timeout="4000">
          {{ resultMessage }}
        </v-snackbar>
      </v-card>
    </v-col>
  </v-row>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ShopifyFiles',

  data() {
    return {
      shop_id: this.$store.state.shop_id,

      files: [],
      pageInfo: {},
      onlyUnused: true,
      loading: false,

      localMediaUrls: new Set(), // thirdparty_url values already in your library — drives the Saved badge
      creatingIds: [],
      showResultSnackbar: false,
      resultMessage: '',

      headers: [
        { title: '', key: 'preview', sortable: false, width: 70 },
        { title: 'Filename', key: 'filename', sortable: false },
        { title: 'Type', key: 'type', sortable: false, width: 90 },
        { title: 'Status', key: 'status', sortable: false, width: 110 },
        { title: '', key: 'actions', sortable: false, align: 'end' },
      ],
    };
  },

  async mounted() {
    await this.fetchLocalMedia();
    await this.fetchFiles();
  },

  methods: {
    isImage(item) {
      return !!item.image;
    },

    previewUrl(item) {
      return item.image?.url || '';
    },

    displayName(item) {
      const url = this.isImage(item) ? item.image?.url : item.url;
      if (!url) return 'Untitled';
      try {
        const path = new URL(url).pathname;
        return decodeURIComponent(path.split('/').pop());
      } catch {
        return url;
      }
    },

    mimeType(item) {
      return item.mimeType || '';
    },

    sourceUrl(item) {
      return this.isImage(item) ? item.image?.url : item.url;
    },

    isAlreadySaved(item) {
      return this.localMediaUrls.has(this.sourceUrl(item));
    },

    fetchLocalMedia() {
      return axios.get('/sadmin/media-files', { params: { per_page: 200 } })
        .then((resp) => {
          const items = resp.data.items?.data || [];
          this.localMediaUrls = new Set(items.map((m) => m.thirdparty_url).filter(Boolean));
        });
    },

    resetAndFetch() {
      this.pageInfo = {};
      this.fetchFiles();
    },

    fetchFiles(cursor = null, direction = 'next') {
      this.loading = true;
      return axios.get(`/superadmin/shopify/${this.shop_id}/files/live`, {
        params: {
          cursor: cursor || undefined,
          direction,
          only_unused: this.onlyUnused,
          limit: 20,
        },
      })
        .then((resp) => {
          this.files = resp.data.files || [];
          this.pageInfo = resp.data.page_info || {};
        })
        .finally(() => {
          this.loading = false;
        });
    },

    goNext() {
      if (this.pageInfo.hasNextPage) this.fetchFiles(this.pageInfo.endCursor, 'next');
    },

    goPrevious() {
      if (this.pageInfo.hasPreviousPage) this.fetchFiles(this.pageInfo.startCursor, 'previous');
    },

    async createSingle(item) {
      this.creatingIds.push(item.id);
      try {
        const { data } = await axios.post(`/superadmin/shopify/${this.shop_id}/files/create`, {
          file_id: item.id,
          file_type: this.isImage(item) ? 'MediaImage' : 'GenericFile',
        });

        this.resultMessage = data.success ? 'Imported into your library.' : (data.message || 'Import failed.');

        if (data.success) {
          this.localMediaUrls.add(this.sourceUrl(item)); // updates the Saved badge immediately, no full re-fetch needed
        }
      } catch (e) {
        this.resultMessage = e.response?.data?.message || 'Import failed.';
      } finally {
        this.creatingIds = this.creatingIds.filter((id) => id !== item.id);
        this.showResultSnackbar = true;
      }
    },
  },
};
</script>
