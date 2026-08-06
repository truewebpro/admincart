<template>
    <v-card class="section-card">
        <v-card-title>Store Credit</v-card-title>

        <v-card-text>
            <div class="text-h4 mb-4">£{{ balance.toFixed(2) }}</div>

            <v-form ref="formRef" v-model="formValid" @submit.prevent="submitAdjustment">
                <v-radio-group v-model="form.type" inline>
                    <v-radio label="Add credit" value="credit" />
                    <v-radio label="Deduct credit" value="debit" />
                </v-radio-group>

                <v-text-field variant="outlined" density="compact"
                    v-model.number="form.amount"
                    label="Amount (£)"
                    type="number"
                    step="0.01"
                    :rules="[(v) => !!v && v > 0 || 'Enter an amount greater than 0']"
                />

                <v-textarea variant="outlined" density="compact"
                    v-model="form.notes"
                    label="Notes (required)"
                    hint="Explain why this adjustment is being made — visible in the customer's credit history"
                    :rules="[(v) => !!v || 'Notes are required for manual adjustments']"
                />

                <v-btn
                    type="submit"
                    color="primary"
                    :loading="submitting"
                    :disabled="!formValid"
                >
                    Apply Adjustment
                </v-btn>
            </v-form>

            <v-divider class="my-4" />

            <v-table density="compact">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="txn in history" :key="txn.id">
                    <td>{{ formatDate(txn.created_at) }}</td>
                    <td>
                        <v-chip :color="txn.type === 'credit' ? 'success' : 'error'" size="small">
                            {{ txn.type }}
                        </v-chip>
                    </td>
                    <td>{{ formatSource(txn.source) }}</td>
                    <td>£{{ txn.amount }}</td>
                    <td>{{ txn.notes }}</td>
                </tr>
                </tbody>
            </v-table>
        </v-card-text>
    </v-card>
</template>

<script>
import axios from 'axios';

export default {
    name: 'StoreCreditAdjustment',

    props: {
        // The customer_shops.cshop_id for this customer AT THIS SHOP — not a global
        // customer id. Balance is per-shop, so this component needs the cshop_id,
        // typically resolved by your customer search/lookup screen first.
        cshopId: {
            type: [Number, String],
            required: true,
        },
    },

    data() {
        return {
            balance: 0,
            history: [],
            formValid: false,
            submitting: false,
            form: {
                type: 'credit',
                amount: null,
                notes: '',
            },
        };
    },

    // created() {
    //     this.loadData();
    // },
    async mounted() {
        await this.loadData()
    },

    methods: {
        async loadData() {
            const cshop_id = String(this.cshopId);
            const { data } = await axios.get(`/sadmin/customer-shops/${cshop_id}/store-credit`);
            // const { data } = await axios.get(`/sadmin/customer-shops/997/store-credit`);
            this.balance = data.balance;
            this.history = data.history.data;
        },

        async submitAdjustment() {
            const valid = await this.$refs.formRef.validate();
            if (!valid.valid) return;

            this.submitting = true;
            try {
                const { data } = await axios.post(
                    `/sadmin/customer-shops/${this.cshopId}/store-credit/adjust`,
                    this.form
                );
                this.balance = data.balance;
                this.$refs.formRef.reset();
                this.form.type = 'credit';
                await this.loadData();
                this.$emit('adjusted', data.balance);
                this.showSnackbar('Store credit updated.');
            } catch (err) {
                this.showSnackbar(
                    err.response?.data?.message || 'Something went wrong.',
                    'error'
                );
            } finally {
                this.submitting = false;
            }
        },

        formatDate(value) {
            return new Date(value).toLocaleDateString('en-GB');
        },

        formatSource(source) {
            const labels = {
                manual_admin: 'Manual (Admin)',
                order_refund_partial: 'Partial Refund',
                order_refund_full: 'Full Refund',
                loyalty_redeem: 'Loyalty Redeem',
                checkout_usage: 'Checkout Usage',
                expired: 'Expired',
                other: 'Other',
            };
            return labels[source] || source;
        },

        showSnackbar(text, color = 'success') {
            // wire this up to your existing Vuex snackbar module
            this.$store.dispatch('snackbar/show', { text, color });
        },
    },
};
</script>
