<template>
    <v-container>
        <v-row class="mb-2" align="center">
            <v-col cols="12" md="6"><h2 class="text-h5 font-weight-bold">Media Library</h2></v-col>
            <v-col cols="12" md="6" class="d-flex justify-end ga-2">
                <v-btn prepend-icon="mdi-upload" @click="showUploadDialog = true">
                    Upload File
                </v-btn>
                <v-btn prepend-icon="mdi-link" @click="showUrlDialog = true">
                    Add from URL
                </v-btn>
            </v-col>
            <v-col cols="12" md="12">
                <v-card>
                    <div class="pa-2">
                        <v-row dense>
                            <v-col cols="12" md="9">
                                <v-text-field
                                    v-model="search" label="Search filename" density="compact" variant="outlined"
                                    hide-details clearable
                                    @update:model-value="debouncedFetch"
                                />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="fileType"
                                    :items="[{title: 'All types', value: null}, {title: 'Images', value: 'image'}, {title: 'Files', value: 'generic'}]"
                                    density="compact" variant="outlined" hide-details
                                    @update:model-value="fetchItems"
                                />
                            </v-col>
                        </v-row>
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
                            <v-img v-if="item.file_type === 'image'" :src="cdnBase + item.path" width="48" height="48" cover class="rounded my-1" />
                            <v-avatar v-else size="48" color="grey-lighten-3" class="rounded">
                                <v-icon icon="mdi-file-outline" />
                            </v-avatar>
                        </template>
                        <template #item.filename="{item}">
                            <div class="text-body-2 text-truncate" style="max-width: 260px;">{{ item.filename }}</div>
                            <div class="text-caption text-medium-emphasis">{{ item.mime_type }}</div>
                            <div v-if="item.width && item.height" class="text-caption text-medium-emphasis">
                                {{ item.width }}×{{ item.height }}
                            </div>
                        </template>
                        <template #item.alt_text="{item}">
                            <div class="d-flex align-center ga-1">
                            <span class="text-body-2 text-truncate" style="max-width: 180px;">
                                {{ item.alt_text || '—' }}
                            </span>
                                <v-btn
                                    size="x-small" variant="text" icon="mdi-pencil"
                                    @click="openEditDialog(item)"
                                />
                            </div>
                        </template>
                        <template #item.source="{item}">
                            <v-chip size="small" variant="tonal" :color="item.thirdparty_id || item.thirdparty_url ? 'blue' : 'grey'">
                                {{ item.thirdparty_id || item.thirdparty_url ? 'Shopify' : 'Manual' }}
                            </v-chip>
                        </template>
                        <template #item.created_at="{item}">
                            {{ dayjs(item.created_at).format('D MMM YYYY') }}
                        </template>
                        <template #item.actions="{item}">
                            <v-btn
                                title="Copy Url"
                                icon="mdi-content-copy"
                                @click="copyUrl(item)"
                            />
                        </template>
                    </v-data-table-server>
                </v-card>
            </v-col>
        </v-row>

        <!-- Upload dialog -->
        <v-dialog v-model="showUploadDialog" max-width="460">
            <v-card>
                <v-card-title>Upload File</v-card-title>
                <v-card-text>
                    <v-file-upload
                        v-model="uploadFile"
                        density="compact"
                        title="Drop a file here"
                        browse-text="or browse"
                        accept="image/*,application/pdf"
                    />
                    <v-text-field v-model="uploadAlt" label="Alt text (optional)" density="compact" variant="outlined" class="mt-3" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showUploadDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="uploading" :disabled="!uploadFile" @click="doUpload">Upload</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add from URL dialog -->
        <v-dialog v-model="showUrlDialog" max-width="420">
            <v-card>
                <v-card-title>Add from URL</v-card-title>
                <v-card-text>
                    <v-text-field v-model="urlInput" label="Image or file URL" density="compact" variant="outlined" />
                    <v-text-field v-model="urlAlt" label="Alt text (optional)" density="compact" variant="outlined" class="mt-2" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showUrlDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="addingFromUrl" :disabled="!urlInput" @click="doAddFromUrl">Add</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Edit alt text dialog -->
        <v-dialog v-model="showEditDialog" max-width="420">
            <v-card>
                <v-card-title>Edit Alt Text</v-card-title>
                <v-card-text>
                    <div class="text-caption text-medium-emphasis mb-2">{{ editingItem?.filename }}</div>
                    <v-text-field v-model="editAlt" label="Alt text" density="compact" variant="outlined" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showEditDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="editing" @click="doEditAltText">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="showResultSnackbar" :timeout="3000">{{ resultMessage }}</v-snackbar>

    </v-container>
</template>

<script>
import axios from 'axios';
import dayjs from 'dayjs';
import { VFileUpload } from 'vuetify/labs/VFileUpload';

export default {
    name: 'MediaLibraryList',

    components: { VFileUpload },

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

            showUploadDialog: false,
            uploadFile: null,
            uploadAlt: '',
            uploading: false,

            showUrlDialog: false,
            urlInput: '',
            urlAlt: '',
            addingFromUrl: false,

            showEditDialog: false,
            editingItem: null,
            editAlt: '',
            editing: false,

            showResultSnackbar: false,
            resultMessage: '',

            headers: [
                { title: '', key: 'preview', sortable: false, width: 70 },
                { title: 'Filename', key: 'filename', sortable: false },
                { title: 'Alt Text', key: 'alt_text', sortable: false },
                { title: 'Source', key: 'source', sortable: false },
                { title: 'Added', key: 'created_at', sortable: false },
                { title: '', key: 'actions', sortable: false, align: 'end' },
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
        async copyUrl(item) {
            const fullUrl = this.cdnBase + item.path;
            try {
                await navigator.clipboard.writeText(fullUrl);
                this.resultMessage = 'URL copied to clipboard.';
            } catch (e) {
                this.resultMessage = 'Could not copy — your browser may have blocked clipboard access.';
            }
            this.showResultSnackbar = true;
        },
        async doUpload() {
            if (!this.uploadFile) return;

            this.uploading = true;
            const formData = new FormData();
            // VFileUpload's v-model can be a single File or an array
            // depending on how it's used — normalize defensively so the
            // upload always sends exactly one file regardless.
            const fileToSend = Array.isArray(this.uploadFile) ? this.uploadFile[0] : this.uploadFile;
            formData.append('file', fileToSend);
            if (this.uploadAlt) formData.append('alt_text', this.uploadAlt);

            try {
                await axios.post('/sadmin/media-files/upload', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                this.resultMessage = 'File uploaded.';
                this.showUploadDialog = false;
                this.uploadFile = null;
                this.uploadAlt = '';
                await this.fetchItems();
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Upload failed.';
            } finally {
                this.uploading = false;
                this.showResultSnackbar = true;
            }
        },
        async doAddFromUrl() {
            if (!this.urlInput) return;

            this.addingFromUrl = true;
            try {
                await axios.post('/sadmin/media-files/upload-from-url', {
                    url: this.urlInput,
                    alt_text: this.urlAlt || undefined,
                });
                this.resultMessage = 'File added to library.';
                this.showUrlDialog = false;
                this.urlInput = '';
                this.urlAlt = '';
                await this.fetchItems();
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Failed to add from URL.';
            } finally {
                this.addingFromUrl = false;
                this.showResultSnackbar = true;
            }
        },

        openEditDialog(item) {
            this.editingItem = item;
            this.editAlt = item.alt_text || '';
            this.showEditDialog = true;
        },

        async doEditAltText() {
            if (!this.editingItem) return;

            this.editing = true;
            try {
                const { data } = await axios.put(`/sadmin/media-files/${this.editingItem.id}`, {
                    alt_text: this.editAlt,
                });
                const index = this.items.findIndex((i) => i.id === this.editingItem.id);
                if (index !== -1) this.items[index] = data.media_file;

                this.resultMessage = 'Alt text updated.';
                this.showEditDialog = false;
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Failed to update.';
            } finally {
                this.editing = false;
                this.showResultSnackbar = true;
            }
        },

    },
};
</script>
