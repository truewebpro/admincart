<template>
  <v-container>
    <v-row class="mb-2" align="center">
      <v-col cols="12" md="8"><h2 class="text-h5 font-weight-bold">Media Library</h2></v-col>
      <v-col cols="12" md="4" class="d-flex justify-end ga-2">
        <v-select
          v-model="fileType"
          :items="[{title: 'All types', value: null}, {title: 'Images', value: 'image'}, {title: 'Files', value: 'generic'}]"
          density="compact" variant="outlined" hide-details
          @update:model-value="fetchItems"
        />

      </v-col>
        <v-col cols="12" md="12">
            <v-card>
                <div class="pa-2">
                    <v-text-field
                        v-model="search" label="Search filename" density="compact" variant="outlined"
                        hide-details clearable
                        @update:model-value="debouncedFetch"
                    />
                </div>
                <v-data-table-server
                    :items="items"
                    :items-length="total"
                    :headers="headers"
                    :loading="loading"
                    :items-per-page="perPage"
                    density="comfortable"
                    @update:options="onOptionsUpdate"
                >
                    <template #item.preview="{item}">
                        <v-img :src="cdnBase + item.path" width="48" height="48" cover class="my-1" />
                    </template>
                    <template #item.filename="{item}">
                        <div class="text-body-2 text-truncate" style="max-width: 260px;">{{ item.filename }}</div>
                        <div class="text-caption text-medium-emphasis">{{ item.mime_type }}</div>
                        <div class="text-caption text-medium-emphasis" style="max-width: 260px;">{{ item.alt_text }}</div>
                    </template>
                    <template #item.dimensions="{item}">
                        <span v-if="item.width && item.height">{{ item.width }}×{{ item.height }}</span>
                        <span v-else class="text-medium-emphasis">—</span>
                    </template>
                    <template #item.source="{item}">
                        <v-chip size="small" variant="tonal" :color="item.thirdparty_id || item.thirdparty_url ? 'blue' : 'grey'">
                            {{ item.thirdparty_id || item.thirdparty_url ? 'Shopify' : 'Manual' }}
                        </v-chip>
                    </template>
                    <template #item.created_at="{item}">
                        {{ dayjs(item.created_at).format('D MMM YYYY') }}
                    </template>
                </v-data-table-server>
            </v-card>
        </v-col>
    </v-row>


  </v-container>
</template>

<script>
import axios from 'axios';
import dayjs from 'dayjs';

export default {
  name: 'MediaLibraryList',

  props: {
    cdnBase: { type: String, default: 'https://cdn.truewebcart.com/' },
  },

  data() {
    return {
      items: [],
      total: 0,
      loading: false,
      search: '',
      fileType: null,
      perPage: 30,
      currentPage: 1,
      debounceTimer: null,

      headers: [
        { title: '', key: 'preview', sortable: false, width: 70 },
        { title: 'Filename', key: 'filename', sortable: false },
        { title: 'Dimensions', key: 'dimensions', sortable: false },
        { title: 'Source', key: 'source', sortable: false },
        { title: 'Added', key: 'created_at', sortable: false },
      ],
    };
  },

  methods: {
    dayjs,

    debouncedFetch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.fetchItems(), 300);
    },

    onOptionsUpdate({ page, itemsPerPage }) {
      this.currentPage = page;
      this.perPage = itemsPerPage;
      this.fetchItems();
    },

    fetchItems() {
      this.loading = true;
      return axios.get('/sadmin/media-files', {
        params: {
          search: this.search || undefined,
          file_type: this.fileType || undefined,
          per_page: this.perPage,
          page: this.currentPage,
        },
      })
        .then((resp) => {
          this.items = resp.data.items?.data || [];
          this.total = resp.data.items?.total || 0;
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
};
</script>
