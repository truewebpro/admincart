<template>
    <v-container>
        <v-card class="section-card">
            <v-card-title>Product Point Overrides</v-card-title>
            <v-card-subtitle>
                Override the earn rate for a specific product variant — set to 0 to exclude it entirely, or give a flat bonus regardless of price.
            </v-card-subtitle>

            <v-card-text>
                <v-row align="center">
                    <v-col cols="5">
                        <v-text-field variant="outlined"
                                      v-model="search"
                                      label="Search product / SKU"
                                      density="compact"
                                      clearable
                                      @update:model-value="loadOverrides"
                        />
                    </v-col>
                    <v-col cols="7" class="text-right">
                        <v-btn color="primary" density="comfortable" prependIcon="mdi-plus" @click="openNewDialog">Add Override</v-btn>
                    </v-col>
                </v-row>

                <v-table>
                    <thead>
                    <tr>
                        <th>Product / Variant</th>
                        <th>SKU</th>
                        <th>Points per unit</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="override in overrides" :key="override.id">
                        <td>{{ override.variant?.title }}</td>
                        <td>{{ override.variant?.sku }}</td>
                        <td>{{ override.points_per_unit }}</td>
                        <td>
                            <v-chip variant="outlined" density="comfortable" :color="override.is_active ? 'success' : 'grey'" size="small">
                                {{ override.is_active ? 'Active' : 'Inactive' }}
                            </v-chip>
                        </td>
                        <td>
                            <v-btn icon="mdi-pencil" size="small" variant="text" @click="editOverride(override)" />
                            <v-btn icon="mdi-delete" size="small" variant="text" @click="deleteOverride(override)" />
                        </td>
                    </tr>
                    <tr v-if="!overrides.length">
                        <td colspan="5" class="text-center text-medium-emphasis py-4">
                            No overrides yet — every product earns points at the global rate.
                        </td>
                    </tr>
                    </tbody>
                </v-table>
            </v-card-text>

            <v-dialog v-model="dialog" max-width="480">
                <v-card>
                    <v-card-title>{{ editing.id ? 'Edit' : 'New' }} Override</v-card-title>
                    <v-card-text>
                        <v-autocomplete variant="outlined" density="compact"
                                        v-if="!editing.id"
                                        v-model="editing.variant_id"
                                        :items="variantOptions"
                                        item-title="label"
                                        item-value="variant_id"
                                        label="Variant"
                                        :loading="searchingVariants"
                                        @update:search="searchVariants"
                        />
                        <v-text-field variant="outlined" density="compact"
                                      v-model.number="editing.points_per_unit"
                                      label="Points per unit"
                                      type="number"
                                      hint="0 excludes this product from earning points"
                                      persistent-hint
                        />
                        <v-switch color="success" density="compact" v-model="editing.is_active" label="Active" />
                        <v-textarea v-model="editing.notes" variant="outlined" density="compact"
                                    hide-details
                                    label="Notes (optional)" />
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                        <v-btn variant="elevated" density="comfortable" color="primary" :loading="saving" @click="saveOverride">Save</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-card>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'LoyaltyProductPointsOverride',

    data() {
        return {
            search: '',
            overrides: [],
            dialog: false,
            saving: false,
            editing: {},
            variantOptions: [],
            searchingVariants: false,
        };
    },

    created() {
        this.loadOverrides();
    },

    methods: {
        async loadOverrides() {
            const { data } = await axios.get('/sadmin/loyalty/product-points', {
                params: { search: this.search },
            });
            this.overrides = data.data;
        },

        openNewDialog() {
            this.editing = { variant_id: null, points_per_unit: 0, is_active: true, notes: '' };
            this.dialog = true;
        },

        editOverride(override) {
            this.editing = { ...override };
            this.dialog = true;
        },

        async searchVariants(query) {
            if (!query) return;
            this.searchingVariants = true;
            try {
                // Point this at your existing product/variant search endpoint.
                const { data } = await axios.get('/sadmin/products/variants/search', { params: { q: query } });
                // Point this at your existing variant search endpoint. Adjust v.title/v.sku
                // below if your variants API returns different field names (e.g. variant
                // title often comes from the parent product rather than the variant itself).
                this.variantOptions = data.map((v) => ({ variant_id: v.variant_id, label: `${v.title} (${v.sku})` }));
            } finally {
                this.searchingVariants = false;
            }
        },

        async saveOverride() {
            this.saving = true;
            try {
                if (this.editing.id) {
                    await axios.put(`/sadmin/loyalty/product-points/${this.editing.id}`, this.editing);
                } else {
                    await axios.post('/sadmin/loyalty/product-points', this.editing);
                }
                this.dialog = false;
                await this.loadOverrides();
                this.$store.dispatch('snackbar/show', { text: 'Override saved.', color: 'success' });
            } catch (err) {
                this.$store.dispatch('snackbar/show', {
                    text: err.response?.data?.message || 'Something went wrong.',
                    color: 'error',
                });
            } finally {
                this.saving = false;
            }
        },

        async deleteOverride(override) {
            if (!confirm(`Remove override for "${override.variant?.title}"? It will fall back to the global rate.`)) return;
            await axios.delete(`/sadmin/loyalty/product-points/${override.id}`);
            await this.loadOverrides();
        },
    },
};
</script>
<style scoped>
.section-card { border: 1px solid #e1e3e6; box-shadow: 0 1px 2px rgba(16,24,40,0.04); border-radius: 8px; }
</style>
