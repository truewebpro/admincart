<template>
    <v-container>
        <v-card class="mb-6 section-card">
            <v-card-title class="d-flex justify-space-between align-center mt-2">
                Earn Actions
                <v-btn density="comfortable" color="primary" @click="openNewDialog">Add Action</v-btn>
            </v-card-title>
            <v-card-subtitle>
                Non-purchase ways customers earn points — reviews, social follows, shares, or
                anything custom. Automatic actions award instantly; everything else lands in
                the Review Queue.
            </v-card-subtitle>

            <v-table>
                <thead>
                <tr>
                    <th>Label</th>
                    <th>Category</th>
                    <th class="text-right">Points</th>
                    <th>Verification</th>
                    <th>Repeat</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="action in actions" :key="action.id">
                    <td>
                        {{ action.label }}
                        <div v-if="action.platform" class="text-caption text-medium-emphasis">
                            {{ action.platform }}
                        </div>
                    </td>
                    <td><v-chip size="small" variant="tonal" density="comfortable">{{ categoryLabel(action.category) }}</v-chip></td>
                    <td class="text-right">{{ action.points }}</td>
                    <td>{{ action.verification === 'automatic' ? 'Automatic' : 'Manual review' }}</td>
                    <td>{{ repeatLabel(action.repeat_scope) }}</td>
                    <td>
                        <v-chip variant="outlined" density="comfortable"
                                :color="action.is_active ? 'success' : 'grey'" size="small">
                            {{ action.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </td>
                    <td>
                        <v-btn icon="mdi-pencil" size="small" variant="text" @click="editAction(action)" />
                        <v-btn icon="mdi-delete" size="small" variant="text" @click="deleteAction(action)" />
                    </td>
                </tr>
                <tr v-if="!actions.length">
                    <td colspan="7" class="text-center text-medium-emphasis py-6">
                        No earn actions yet — purchases are still the only way to earn.
                    </td>
                </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-dialog v-model="dialog" max-width="560">
            <v-card>
                <v-card-title>{{ editing.id ? 'Edit' : 'New' }} Earn Action</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" persistent-placeholder
                                  v-model="editing.label" label="Label" placeholder="e.g. Leave us a Google review" />

                    <v-row>
                        <v-col cols="6">
                            <v-select variant="outlined" density="compact"
                                v-model="editing.category"
                                label="Category"
                                :items="[
                  { title: 'Review', value: 'review' },
                  { title: 'Social Follow', value: 'social_follow' },
                  { title: 'Social Share', value: 'social_share' },
                  { title: 'Custom', value: 'custom' },
                ]"
                            />
                        </v-col>
                        <v-col cols="6">
                            <v-text-field variant="outlined" density="compact" persistent-placeholder
                                          v-model="editing.platform" label="Platform (optional)"
                                          placeholder="e.g. google, instagram, onsite" />
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="6">
                            <v-text-field
                                variant="outlined" density="compact"
                                v-model.number="editing.points" label="Points" type="number" min="0" />
                        </v-col>
                        <v-col cols="6">
                            <v-select variant="outlined" density="compact"
                                v-model="editing.verification"
                                label="Verification"
                                :items="[
                  { title: 'Manual admin review', value: 'manual_admin' },
                  { title: 'Automatic (system-triggered)', value: 'automatic' },
                ]"
                            />
                        </v-col>
                    </v-row>

                    <v-select variant="outlined" density="compact"
                        v-model="editing.repeat_scope"
                        label="Repeat rule"
                        :items="[
                          { title: 'Once per customer', value: 'once_per_customer' },
                          { title: 'Once per item (e.g. per product)', value: 'once_per_reference' },
                          { title: 'Unlimited', value: 'unlimited' },
                        ]"
                    />

                    <v-text-field variant="outlined" density="compact" persistent-placeholder
                                  v-model="editing.action_url" label="Action URL (optional)" placeholder="e.g. https://instagram.com/yourshop" />
                    <v-textarea variant="outlined" density="compact" persistent-placeholder
                                v-model="editing.description"
                                label="Description (optional, shown to customer)" rows="2" />
                    <v-switch color="success" density="compact" hide-details v-model="editing.is_active" label="Active" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveAction">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script>
import axios from 'axios';

const CATEGORY_LABELS = {
    review: 'Review',
    social_follow: 'Social Follow',
    social_share: 'Social Share',
    custom: 'Custom',
    order: 'Order',
};

const REPEAT_LABELS = {
    once_per_customer: 'Once per customer',
    once_per_reference: 'Once per item',
    unlimited: 'Unlimited',
};

export default {
    name: 'LoyaltyEarnActions',

    data() {
        return {
            actions: [],
            dialog: false,
            saving: false,
            editing: {},
        };
    },

    created() {
        this.loadActions();
    },

    methods: {
        async loadActions() {
            const { data } = await axios.get('/sadmin/loyalty/earn-actions');
            this.actions = data;
        },

        categoryLabel(category) {
            return CATEGORY_LABELS[category] || category;
        },

        repeatLabel(scope) {
            return REPEAT_LABELS[scope] || scope;
        },

        openNewDialog() {
            this.editing = {
                label: '', category: 'custom', platform: '', points: 0,
                verification: 'manual_admin', repeat_scope: 'once_per_customer',
                action_url: '', description: '', is_active: true,
            };
            this.dialog = true;
        },

        editAction(action) {
            this.editing = { ...action };
            this.dialog = true;
        },

        async saveAction() {
            this.saving = true;
            try {
                if (this.editing.id) {
                    await axios.put(`/sadmin/loyalty/earn-actions/${this.editing.id}`, this.editing);
                } else {
                    await axios.post('/sadmin/loyalty/earn-actions', this.editing);
                }
                this.dialog = false;
                await this.loadActions();
                this.showSnackbar(`"${this.editing.label}" saved.`);
            } catch (err) {
                this.showSnackbar(err.response?.data?.message || 'Something went wrong.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteAction(action) {
            if (!confirm(`Delete "${action.label}"? Customers will no longer see this as a way to earn.`)) return;
            await axios.delete(`/sadmin/loyalty/earn-actions/${action.id}`);
            await this.loadActions();
            this.showSnackbar(`"${action.label}" deleted.`);
        },

        showSnackbar(text, color = 'success') {
            this.$store.dispatch('snackbar/show', { text, color });
        },
    },
};
</script>
<style scoped>
.section-card { border: 1px solid #e1e3e6; box-shadow: 0 1px 2px rgba(16,24,40,0.04); border-radius: 8px; }
</style>
