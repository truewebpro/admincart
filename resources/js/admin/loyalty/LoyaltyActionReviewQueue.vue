<template>
    <v-container>
        <v-card class="section-card">
            <v-card-title class="d-flex justify-space-between align-center">
                Points Review Queue
                <v-chip v-if="pendingCount" color="warning" size="small" variant="outlined">{{ pendingCount }} pending</v-chip>
            </v-card-title>
            <v-card-subtitle>
                Claims that need a human check — reviews and social follows can't be verified automatically.
            </v-card-subtitle>

            <v-tabs v-model="statusFilter" class="px-4">
                <v-tab value="pending">Pending</v-tab>
                <v-tab value="approved">Approved</v-tab>
                <v-tab value="rejected">Rejected</v-tab>
            </v-tabs>

            <v-table>
                <thead>
                <tr>
                    <th>Customer</th>
                    <th>Action</th>
                    <th class="text-right">Points</th>
                    <th>Proof</th>
                    <th>Submitted</th>
                    <th v-if="statusFilter === 'pending'"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in completions" :key="item.id">
                    <td>{{ item.customer?.name }}</td>
                    <td>{{ item.action?.label }}</td>
                    <td class="text-right">+{{ item.action?.points }}</td>
                    <td>
                        <a v-if="item.proof_url" :href="item.proof_url" target="_blank" rel="noopener">
                            View link ↗
                        </a>
                        <span v-else class="text-medium-emphasis">—</span>
                    </td>
                    <td class="text-medium-emphasis">{{ formatDate(item.created_at) }}</td>

                    <td v-if="statusFilter === 'pending'">
                        <v-btn size="small" color="primary" variant="tonal" :loading="approvingId === item.id" @click="approve(item)">
                            Approve
                        </v-btn>
                        <v-btn size="small" color="error" variant="text" @click="openReject(item)">
                            Reject
                        </v-btn>
                    </td>
                </tr>
                <tr v-if="!completions.length">
                    <td :colspan="statusFilter === 'pending' ? 6 : 5" class="text-center text-medium-emphasis py-6">
                        {{ statusFilter === 'pending' ? 'Nothing waiting for review.' : `No ${statusFilter} claims yet.` }}
                    </td>
                </tr>
                </tbody>
            </v-table>
            <v-dialog v-model="rejectDialog" max-width="440">
                <v-card>
                    <v-card-title>Reject Claim</v-card-title>
                    <v-card-text>
                        <p class="text-body-2 mb-3">
                            {{ rejecting?.customer?.name }} — {{ rejecting?.action?.label }}
                        </p>
                        <v-textarea
                            v-model="rejectNotes"
                            label="Reason (required)"
                            hint="Kept on record so the team knows why this was declined"
                            persistent-hint
                        />
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn variant="text" @click="rejectDialog = false">Cancel</v-btn>
                        <v-btn color="error" :loading="rejecting !== null && submittingReject" @click="confirmReject">
                            Confirm Reject
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-card>
    </v-container>
</template>

<script>
import axios from 'axios';

export default {
    name: 'LoyaltyActionReviewQueue',

    data() {
        return {
            completions: [],
            statusFilter: 'pending',
            pendingCount: 0,
            approvingId: null,
            rejectDialog: false,
            rejecting: null,
            rejectNotes: '',
            submittingReject: false,
        };
    },

    watch: {
        statusFilter() {
            this.loadCompletions();
        },
    },

    created() {
        this.loadCompletions();
    },

    methods: {
        async loadCompletions() {
            const { data } = await axios.get('/sadmin/loyalty/action-completions', {
                params: { status: this.statusFilter },
            });
            this.completions = data.data;

            if (this.statusFilter === 'pending') {
                this.pendingCount = data.total ?? data.data.length;
            }
        },

        async approve(item) {
            this.approvingId = item.id;
            try {
                const { data } = await axios.post(`/sadmin/loyalty/action-completions/${item.id}/approve`);
                this.showSnackbar(data.message);
                await this.loadCompletions();
            } catch (err) {
                this.showSnackbar(err.response?.data?.message || 'Something went wrong.', 'error');
            } finally {
                this.approvingId = null;
            }
        },

        openReject(item) {
            this.rejecting = item;
            this.rejectNotes = '';
            this.rejectDialog = true;
        },

        async confirmReject() {
            if (!this.rejectNotes.trim()) {
                this.showSnackbar('A reason is required to reject a claim.', 'error');
                return;
            }
            this.submittingReject = true;
            try {
                await axios.post(`/sadmin/loyalty/action-completions/${this.rejecting.id}/reject`, {
                    notes: this.rejectNotes,
                });
                this.rejectDialog = false;
                this.showSnackbar(`Rejected claim from ${this.rejecting.customer?.name}.`);
                await this.loadCompletions();
            } catch (err) {
                this.showSnackbar(err.response?.data?.message || 'Something went wrong.', 'error');
            } finally {
                this.submittingReject = false;
            }
        },

        formatDate(value) {
            return new Date(value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
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
