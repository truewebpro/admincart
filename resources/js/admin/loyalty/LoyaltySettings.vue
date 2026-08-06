<template>
    <v-container>
        <v-card class="mb-6 section-card">
            <v-card-title>Earn Settings & Rules</v-card-title>
            <v-card-subtitle>The shop-wide rate used for any product without a specific override. Takes effect on the next order</v-card-subtitle>
            <v-card-text>
                <v-switch color="success" density="compact" v-model="settings.is_active" label="Loyalty program active" />
                <v-row>
                    <v-col cols="6">
                        <v-text-field variant="outlined" density="compact"
                            v-model.number="settings.spend_amount"
                            label="Spend amount (£)"
                            type="number"
                            step="0.01"
                        />
                    </v-col>
                    <v-col cols="6">
                        <v-text-field variant="outlined" density="compact"
                            v-model.number="settings.points_per_spend"
                            label="Points earned"
                            type="number"
                        />
                    </v-col>
                </v-row>
                <p class="text-caption text-medium-emphasis mb-1">
                    e.g. Spend £{{ settings.spend_amount || 1 }} → earn {{ settings.points_per_spend || 1 }} point(s)
                </p>

                <v-row>
                    <v-col cols="6">
                        <v-text-field variant="outlined" density="compact"
                            v-model.number="settings.min_order_amount_to_earn"
                            label="Minimum order amount to earn (optional)"
                            type="number"
                            step="0.01"
                        />
                    </v-col>
                    <v-col cols="6">
                        <v-text-field variant="outlined" density="compact"
                            v-model.number="settings.max_points_per_order"
                            label="Maximum points per order (optional)"
                            type="number"
                        />
                    </v-col>
                </v-row>

                <v-btn color="primary" density="comfortable" :loading="savingSettings" @click="saveSettings">
                    Save Settings
                </v-btn>
            </v-card-text>
        </v-card>

        <v-card class="section-card">
            <v-card-title class="d-flex justify-space-between align-center">
                Redeem Rules
                <v-btn size="small" color="primary" @click="openNewRuleDialog">Add Rule</v-btn>
            </v-card-title>

            <v-table>
                <thead>
                <tr>
                    <th>Label</th>
                    <th>Points Required</th>
                    <th>Store Credit Value</th>
                    <th>Active</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="rule in rules" :key="rule.id">
                    <td>{{ rule.label }}</td>
                    <td>{{ rule.points_required }}</td>
                    <td>£{{ rule.credit_value }}</td>
                    <td>
                        <v-chip density="comfortable" variant="outlined" :color="rule.is_active ? 'success' : 'grey'" size="small">
                            {{ rule.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </td>
                    <td>
                        <v-btn icon="mdi-pencil" size="small" variant="text" @click="editRule(rule)" />
                        <v-btn icon="mdi-delete" size="small" variant="text" @click="deleteRule(rule)" />
                    </td>
                </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-dialog v-model="ruleDialog" max-width="480">
            <v-card>
                <v-card-title>{{ editingRule.id ? 'Edit' : 'New' }} Redeem Rule</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" v-model="editingRule.label" label="Label (e.g. '£5 off')" />
                    <v-text-field variant="outlined" density="compact" v-model.number="editingRule.points_required" label="Points Required" type="number" />
                    <v-text-field variant="outlined" density="compact" v-model.number="editingRule.credit_value" label="Store Credit Value (£)" type="number" step="0.01" />
                    <v-switch color="success" v-model="editingRule.is_active" label="Active" density="compact" hide-details />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn color="red" variant="text" @click="ruleDialog = false">Cancel</v-btn>
                    <v-btn color="primary" variant="elevated" density="comfortable" :loading="savingRule" @click="saveRule">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'LoyaltySettings',

    data() {
        return {
            settings: {
                is_active: true,
                spend_amount: 1,
                points_per_spend: 1,
                min_order_amount_to_earn: null,
                max_points_per_order: null,
            },
            rules: [],
            savingSettings: false,
            ruleDialog: false,
            savingRule: false,
            editingRule: {},
        };
    },

    created() {
        this.loadData();
    },

    methods: {
        async loadData() {
            const { data } = await axios.get('/sadmin/loyalty/settings');
            this.settings = data.settings;
            this.rules = data.rules;
        },

        async saveSettings() {
            this.savingSettings = true;
            try {
                const { data } = await axios.put('/sadmin/loyalty/settings', this.settings);
                this.settings = data;
                this.$store.dispatch('snackbar/show', { text: 'Settings saved.', color: 'success' });
            } finally {
                this.savingSettings = false;
            }
        },

        openNewRuleDialog() {
            this.editingRule = { label: '', points_required: null, credit_value: null, is_active: true };
            this.ruleDialog = true;
        },

        editRule(rule) {
            this.editingRule = { ...rule };
            this.ruleDialog = true;
        },

        async saveRule() {
            this.savingRule = true;
            try {
                if (this.editingRule.id) {
                    await axios.put(`/sadmin/loyalty/redeem-rules/${this.editingRule.id}`, this.editingRule);
                } else {
                    await axios.post('/sadmin/loyalty/redeem-rules', this.editingRule);
                }
                this.ruleDialog = false;
                await this.loadData();
            } finally {
                this.savingRule = false;
            }
        },

        async deleteRule(rule) {
            if (!confirm(`Delete "${rule.label}"?`)) return;
            await axios.delete(`/sadmin/loyalty/redeem-rules/${rule.id}`);
            await this.loadData();
        },
    },
};
</script>
<style scoped>
.section-card { border: 1px solid #e1e3e6; box-shadow: 0 1px 2px rgba(16,24,40,0.04); border-radius: 8px; }
</style>
