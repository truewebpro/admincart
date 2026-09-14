<template>
  <v-dialog v-model="isOpen" max-width="800">
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        Choose from Media Library
        <v-btn icon="mdi-close" variant="text" size="small" @click="isOpen = false" />
      </v-card-title>

      <v-card-text>
        <v-text-field
          v-model="search"
          label="Search filename"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          class="mb-4"
          @update:model-value="debouncedFetch"
        />

        <v-progress-linear v-if="loading" indeterminate class="mb-2" />

        <v-row v-if="!loading && items.length" dense>
          <v-col v-for="item in items" :key="item.id" cols="4" sm="3">
            <v-card
              variant="outlined"
              class="pa-1 cursor-pointer"
              :class="{ 'border-primary': selectingId === item.id }"
              @click="choose(item)"
            >
              <v-img
                :src="cdnBase + item.path"
                height="90"
                cover
                class="rounded"
              />
              <div class="text-caption text-truncate mt-1">{{ item.filename }}</div>
            </v-card>
          </v-col>
        </v-row>

        <div v-else-if="!loading" class="text-body-2 text-medium-emphasis text-center py-8">
          No files found.
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
import axios from 'axios';

export default {
  name: 'MediaLibraryPicker',

  props: {
    modelValue: { type: Boolean, default: false }, // v-model controls dialog open/close
    cdnBase: { type: String, default: '' }, // prefix to turn a stored path into a real displayable URL
  },

  emits: ['update:modelValue', 'select'],

  data() {
    return {
      items: [],
      search: '',
      loading: false,
      selectingId: null,
      debounceTimer: null,
    };
  },

  computed: {
    isOpen: {
      get() { return this.modelValue; },
      set(val) { this.$emit('update:modelValue', val); },
    },
  },

  watch: {
    isOpen(open) {
      if (open) this.fetchFiles();
    },
  },

  methods: {
    debouncedFetch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(this.fetchFiles, 300);
    },

    fetchFiles() {
      this.loading = true;
      return axios.get('/sadmin/media-files', { params: { search: this.search || undefined, per_page: 24 } })
        .then((resp) => {
          this.items = resp.data.items?.data || [];
        })
        .finally(() => {
          this.loading = false;
        });
    },

    // Doesn't call any "set image" endpoint itself — this component
    // has no idea what it's being used for (a blog, a product, a
    // brand). It just hands the chosen file back to whatever opened
    // it, via emit. The PARENT decides what to do with the selection.
    choose(item) {
      this.selectingId = item.id;
      this.$emit('select', item);
      this.isOpen = false;
    },
  },
};
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.border-primary { border-color: rgb(var(--v-theme-primary)) !important; border-width: 2px !important; }
</style>
