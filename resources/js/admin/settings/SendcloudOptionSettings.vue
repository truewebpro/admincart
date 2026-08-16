<template>
    <v-container class="pa-2">
        <v-card class="border-sm">
            <v-card-title class="d-flex justify-space-between align-center">
                Sendcloud — Shipping Services
                <v-text-field
                    v-model="search"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-magnify"
                    placeholder="Search carrier or service..."
                    hide-details
                    style="max-width: 300px"
                />
            </v-card-title>
            <v-card-subtitle>
                Choose which shipping services show up when creating a label. Unchecked services stay hidden from the order picker.
            </v-card-subtitle>

            <v-card-text style="max-height: 60vh; overflow-y: auto;" v-if="!loading">
                <div v-if="Object.keys(filteredGrouped).length === 0" class="text-body-2 text-grey py-4">
                    No matching shipping options.
                </div>

                <div v-for="(services, carrier) in filteredGrouped" :key="carrier" class="mb-5">
                    <div class="d-flex justify-space-between align-center mb-1 pb-1 border-b-sm">
                        <h4>{{ carrier }}</h4>
                        <div>
                            <v-btn size="x-small" variant="text" @click="toggleAll(carrier, true)">Select all</v-btn>
                            <v-btn size="x-small" variant="text" @click="toggleAll(carrier, false)">Clear</v-btn>
                        </div>
                    </div>

                    <v-checkbox
                        v-for="opt in services"
                        :key="opt.id"
                        v-model="enabledIds"
                        :value="opt.id"
                        :disabled="!opt.is_active"
                        density="compact"
                        hide-details
                        class="mb-1"
                    >
                        <template v-slot:label>
                            <div>
                                <div>{{ opt.name }}</div>
                                <div class="text-caption text-grey">
                                    {{ extractServiceSuffix(opt.shipping_option_code) }}
                                    <span v-if="!opt.is_active" class="text-error ms-1">(no longer offered by Sendcloud)</span>
                                </div>
                            </div>
                        </template>
                    </v-checkbox>
                </div>
            </v-card-text>

            <v-card-text v-else class="text-center py-6">
                <v-progress-circular indeterminate color="primary" />
            </v-card-text>

            <v-card-actions>
                <v-spacer />
                <v-btn @click="save" color="success" variant="elevated" :loading="saving">
                    Save Changes
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
</template>

<script>
import axios from "axios";

export default {
    name: "SendcloudOptionSettings",
    data() {
        return {
            options: [],
            enabledIds: [],
            search: '',
            loading: false,
            saving: false,
        };
    },
    computed: {
        grouped() {
            const groups = {};
            for (const opt of this.options) {
                (groups[opt.carrier] ||= []).push(opt);
            }
            return groups;
        },
        filteredGrouped() {
            const term = this.search.trim().toLowerCase();
            if (!term) return this.grouped;

            const result = {};
            for (const [carrier, services] of Object.entries(this.grouped)) {
                const matches = services.filter(opt =>
                    `${opt.carrier} ${opt.name} ${opt.shipping_option_code}`.toLowerCase().includes(term)
                );
                if (matches.length) result[carrier] = matches;
            }
            return result;
        },
    },
    created() {
        this.load();
    },
    methods: {
        extractServiceSuffix(code) {
            if (!code) return '';
            const idx = code.indexOf(':');
            return idx >= 0 ? code.slice(idx + 1) : code;
        },
        load() {
            this.loading = true;
            axios.get('/sadmin/settings/sendcloud/shipping-options')
                .then((resp) => {
                    this.options = resp.data.data;
                    this.enabledIds = this.options.filter(o => o.is_enabled).map(o => o.id);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        toggleAll(carrier, value) {
            const ids = this.grouped[carrier].filter(o => o.is_active).map(o => o.id);
            if (value) {
                this.enabledIds = [...new Set([...this.enabledIds, ...ids])];
            } else {
                this.enabledIds = this.enabledIds.filter(id => !ids.includes(id));
            }
        },
        save() {
            this.saving = true;
            axios.post('/sadmin/settings/sendcloud/shipping-options', {
                enabled_ids: this.enabledIds,
            })
                .then(() => {
                    window.Toast.success('Shipping options updated');
                    this.load();
                })
                .catch((err) => {
                    window.Toast.error(err.response?.data?.message || err.message);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
    },
};
</script>
