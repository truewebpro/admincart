<template>
    <v-row>
        <v-col cols="12">
            <v-card class="mb-3" v-if="liveCollections?.length">
                <v-card-text>
                    <h2>Total: {{liveCollections?.length || 0}}</h2>
                    <h3>Collections Created: {{acats?.length || 0}} / {{liveCollections?.length || 0}}</h3>
                    <v-btn v-if="liveCollections?.length" class="mt-2 me-2" variant="tonal" color="success"
                           density="compact" :loading="seoSyncLoading" @click="syncCollectionsSeo" prependIcon="mdi-sync">Sync Seo</v-btn>
                    <v-btn v-if="acats?.length" class="mt-2" variant="tonal" color="success"
                           density="compact" :loading="syncLoading" @click="backfillThirdpartyIds" prependIcon="mdi-sync">Sync Old Collections</v-btn>

                </v-card-text>
            </v-card>
        </v-col>

        <v-col cols="12" md="12">
            <v-card>
                <v-card-text class="d-flex ga-2">
                    <v-btn-toggle v-model="typeFilter" density="compact" mandatory color="primary">
                        <v-btn value="all" size="small">All</v-btn>
                        <v-btn value="manual" size="small">Custom</v-btn>
                        <v-btn value="smart" size="small">Smart</v-btn>
                    </v-btn-toggle>
                </v-card-text>

                <v-data-table
                    :items="filteredCollections"
                    :headers="liveHeaders"
                    :loading="loading"
                    itemsPerPage="-1"
                    mobileBreakpoint="sm"
                >
                    <template #item.title="{item}">
                        <div class="font-weight-bold">{{ item.title }}</div>
                        <div class="text-caption text-medium-emphasis">{{item.handle}}</div>
                    </template>
                    <template #item.cat_type="{item}">
                        <v-chip
                            size="small"
                            density="compact"
                            :color="item.cat_type === 'smart' ? 'purple' : 'blue'"
                            variant="tonal"
                        >
                            {{ item.cat_type === 'smart' ? 'Smart' : 'Custom' }}
                        </v-chip>
                    </template>
                    <template #item.rules="{item}">
                        <span v-if="item.cat_type === 'smart'" class="text-body-2">
                            {{ item.rules?.length || 0 }} rule{{ (item.rules?.length || 0) !== 1 ? 's' : '' }}
                        </span>
                        <span v-else class="text-medium-emphasis">—</span>
                    </template>
                    <template #item.products_count="{item}">
                        {{ item.products_count ?? '—' }}
                    </template>
                    <template #item.handle="{item}">
                        <v-btn icon="mdi-eye" density="compact" :href="'https://'+shopifyDomain+'/collections/'+item.handle" target="_blank">
                        </v-btn>
                    </template>
                    <template #item.actions="{item}">
                        <v-chip
                            v-if="isAlreadySaved(item.id)"
                            size="small"
                            color="success"
                            variant="tonal"
                            prepend-icon="mdi-check"
                        >
                            Saved
                        </v-chip>

                        <v-btn
                            v-else
                            size="small"
                            variant="tonal"
                            :disabled="creatingIds.includes(item.id)"
                            :loading="creatingIds.includes(item.id)"
                            @click="createSingle(item)"
                        >
                            Create
                        </v-btn>
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
import axios from "axios";

export default {
    name: "ShopifyCollections",
    props: {
        shopifyDomain: [String],
    },
    computed: {
        savedCatIds() {
            return new Set(this.acats.map((c) => String(c.thirdparty_id)));
        },
        filteredCollections() {
            if (this.typeFilter === 'all') return this.liveCollections;
            return this.liveCollections.filter((c) => c.cat_type === this.typeFilter);
        },
    },
    data() {
        return {
            shop_id: this.$store.state.shop_id,
            liveCollections: [],
            typeFilter: 'all',

            loading: false,
            creatingIds: [],
            showResultSnackbar: false,
            resultMessage: '',

            liveHeaders: [
                { title: 'Title', key: 'title', sortable: false, width: 320 },
                { title: 'Type', key: 'cat_type', sortable: false, width: 110 },
                { title: 'Rules', key: 'rules', sortable: false, width: 100 },
                { title: 'Products', key: 'products_count', align: 'end', width: 100 },
                { title: 'Link', key: 'handle', sortable: false, width: 80 },
                { title: '', key: 'actions', sortable: false, align: 'end' },
            ],

            acats: [],
            syncLoading: false,
        };
    },
    async mounted() {
        await this.getLiveCollections();
        await this.getAllCats();
    },
    methods: {
        isAlreadySaved(collectionId) {
            return this.savedCatIds.has(String(collectionId));
        },

        getLiveCollections() {
            this.loading = true;
            return axios.get(`/superadmin/shopify/${this.shop_id}/collections/live`)
                .then((resp) => {
                    this.liveCollections = resp.data.collections || [];
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        async createSingle(item) {
            this.creatingIds.push(item.id);
            try {
                const { data } = await axios.post(
                    `/superadmin/shopify/${this.shop_id}/collections/${item.cat_type}/${item.id}/create`
                );
                this.resultMessage = data.message || 'Collection created.';
                await this.getAllCats(); // refresh so isAlreadySaved() reflects the new save immediately
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Failed to create collection.';
            } finally {
                this.creatingIds = this.creatingIds.filter((id) => id !== item.id);
                this.showResultSnackbar = true;
            }
        },

        // Adjust this endpoint to whatever your existing "list local
        // categories" route actually is — mirrors getAllBlogs()'s role
        // in ShopifyBlogs.vue.
        getAllCats() {
            return axios.get('/sadmin/categories')
                .then((resp) => {
                    this.acats = resp.data.cats || resp.data.data || [];
                });
        },

        syncCollectionsSeo() {
            this.seoSyncLoading = true;
            return axios.get(`/superadmin/shopify/${this.shop_id}/collections/sync-seo`)
                .then((resp) => {
                    if (resp.data.success) {
                        window.Toast.success('SEO sync complete — ' + (resp.data.updated ?? 0) + ' updated');
                    }
                })
                .finally(() => {
                    this.seoSyncLoading = false;
                });
        },

        backfillThirdpartyIds() {
            this.syncLoading = true;
            return axios.post(`/superadmin/shopify/${this.shop_id}/collections/backfill-thirdparty-ids`)
                .then((resp) => {
                    const respData = resp.data;
                    if (resp.data.success) {
                        window.Toast.success(`Checked ${respData.checked}, matched ${respData.matched}`);
                    }
                    return this.getAllCats();
                })
                .finally(() => {
                    this.syncLoading = false;
                });
        },
    },
};
</script>

<style scoped>
</style>
