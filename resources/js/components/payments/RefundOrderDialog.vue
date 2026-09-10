<template>
    <v-dialog v-model="dialog" max-width="620" persistent scrollable>
        <v-card>
            <v-card-item>
                <template #prepend>
                    <v-icon>mdi-cash-refund</v-icon>
                </template>
                <template #title>
                    <div class="text-h6 font-weight-bold">
                        Refund order #{{ summary.order.number || orderId }}
                    </div>
                </template>
                <template #append>
                    <v-chip v-if="charge" size="small" variant="tonal" color="success">
                        {{ providerLabel(charge.provider) }}
                    </v-chip>
                </template>
            </v-card-item>

            <v-divider/>

            <v-card-text>
                <v-skeleton-loader v-if="loading" type="article"/>

                <v-alert v-else-if="!charge" type="info" variant="tonal"
                         :text="summary.message || 'This order has no payment that can be refunded from here.'"/>

                <template v-else>
                    <v-alert v-if="error" type="error" variant="tonal" density="compact"
                             class="mb-4" :text="error"/>

                    <v-row dense class="mb-2">
                        <v-col cols="4">
                            <div class="text-caption text-medium-emphasis">Paid</div>
                            <div class="text-body-1">{{ money(charge.amount) }}</div>
                        </v-col>
                        <v-col cols="4">
                            <div class="text-caption text-medium-emphasis">Already refunded</div>
                            <div class="text-body-1">{{ money(charge.refunded) }}</div>
                        </v-col>
                        <v-col cols="4">
                            <div class="text-caption text-medium-emphasis">Available</div>
                            <div class="text-body-1 font-weight-bold">{{ money(charge.refundable) }}</div>
                        </v-col>
                    </v-row>

                    <v-divider class="my-3"/>

                    <h4>Amount</h4>
                    <v-radio-group v-model="mode" inline hide-details>
                        <v-radio value="full" label="Full amount" color="green"></v-radio>
                        <v-radio value="partial" label="Part of it" color="blue"></v-radio>
                    </v-radio-group>

                    <v-text-field v-if="mode === 'partial'"
                                  v-model="amount"
                                  type="number" step="0.01"
                                  variant="outlined" density="compact"
                                  class="mt-2"
                                  :prefix="charge.currency || ''"
                                  :rules="amountRules"
                                  label="Amount to refund"/>

                    <h4 class="mt-3">How to reverse it</h4>
                    <v-select v-model="action"
                              :items="actionOptions"
                              variant="outlined" density="compact" class="mt-1"
                              hint="Cancelling before settlement usually costs less and clears faster."
                              persistent-hint/>

                    <v-textarea v-model="reason"
                                label="Reason"
                                variant="outlined" density="compact" rows="2"
                                counter="255" class="mt-3"
                                hint="Kept on the order and sent to the provider."
                                persistent-hint/>

                    <template v-if="summary.history.length">
                        <v-divider class="my-3"/>
                        <h4 class="mb-2">Earlier refunds</h4>
                        <v-list density="compact" class="py-0">
                            <v-list-item v-for="item in summary.history" :key="item.id" class="px-0">
                                <template #prepend>
                                    <v-chip :color="statusColour(item.status)" size="x-small"
                                            variant="flat" class="mr-3">
                                        {{ item.status }}
                                    </v-chip>
                                </template>
                                <v-list-item-title>
                                    {{ money(item.amount) }}
                                    <span class="text-medium-emphasis">· {{ item.type }}</span>
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ item.error || item.reason || formatDate(item.created_at) }}
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </template>
                </template>
            </v-card-text>

            <v-divider/>

            <v-card-actions>
                <v-spacer/>
                <v-btn variant="text" :disabled="saving" @click="close">Close</v-btn>
                <v-btn color="success" variant="flat" density="comfortable"
                       prepend-icon="mdi-cash-refund"
                       :loading="saving"
                       :disabled="!charge || charge.refundable <= 0"
                       @click="submit">
                    {{ submitLabel }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import axios from "axios";

export default {
    name: "RefundOrderDialog",
    props: {
        modelValue: {type: Boolean, default: false},
        orderId: {type: [Number, String], required: true},
    },
    // Declared as an option, not defineEmits — that only exists in <script setup>.
    emits: ["update:modelValue", "refunded"],
    data() {
        return {
            loading: false,
            saving: false,
            summary: {order: {}, charge: null, history: [], message: null},
            mode: "full",
            amount: null,
            reason: "",
            action: "auto",
            idempotencyKey: null,
            error: "",
            actionOptions: [
                {value: "auto", title: "Decide automatically"},
                {value: "cancel", title: "Cancel — payment has not settled yet"},
                {value: "refund", title: "Refund — payment has settled"},
            ],
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        charge() {
            return this.summary.charge;
        },
        // Store keeps the shop under state.shop — same as SettingsPayment.vue.
        shopId() {
            return this.$store.state.shop.shop_id;
        },
        amountRules() {
            return [
                (v) => this.mode === "full" || !!v || "Enter an amount",
                (v) => this.mode === "full" || Number(v) > 0 || "Enter an amount greater than zero",
                (v) => this.mode === "full" || Number(v) <= this.charge.refundable
                    || `Only ${this.money(this.charge.refundable)} is left to refund`,
            ];
        },
        submitLabel() {
            if (!this.charge) return "Refund";
            const value = this.mode === "full" ? this.charge.refundable : Number(this.amount || 0);
            return this.action === "cancel"
                ? `Cancel ${this.money(value)}`
                : `Refund ${this.money(value)}`;
        },
    },
    watch: {
        modelValue(open) {
            if (open) this.load();
        },
        // Shop switched underneath us — the session shop no longer matches.
        shopId() {
            if (this.modelValue) this.close();
        },
    },
    methods: {
        money(value) {
            const currency = this.charge?.currency || "GBP";
            return new Intl.NumberFormat(undefined, {
                style: "currency",
                currency,
            }).format(value ?? 0);
        },

        statusColour(status) {
            return {succeeded: "success", pending: "warning", failed: "error"}[status] || "grey";
        },

        providerLabel(provider) {
            return {
                viva_wallet: "Viva Wallet",
                worldpay: "Worldpay",
                cybersource: "Cybersource",
                paypal: "PayPal",
            }[provider] || provider;
        },

        formatDate(value) {
            return value ? new Date(value).toLocaleString() : "";
        },

        // A fresh key per opening, so a double-click cannot refund twice but a
        // deliberate second refund still goes through.
        newKey() {
            return crypto.randomUUID
                ? crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        },

        load() {
            this.loading = true;
            this.error = "";
            this.idempotencyKey = this.newKey();

            axios.get(`/sadmin/orders/${this.orderId}/refunds`)
                .then((resp) => {
                    this.summary = {
                        order: resp.data.order || {},
                        charge: resp.data.charge,
                        history: resp.data.history || [],
                        message: resp.data.message,
                    };
                    this.amount = resp.data.charge?.refundable ?? null;
                    this.mode = "full";
                    this.reason = "";
                    this.action = resp.data.charge?.settled ? "refund" : "auto";
                })
                .catch((err) => {
                    this.error = err.response?.status === 409
                        ? "No shop is selected."
                        : "Could not load this order's payment details.";
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        submit() {
            if (this.mode === "partial") {
                const failed = this.amountRules.find((rule) => rule(this.amount) !== true);
                if (failed) {
                    this.error = failed(this.amount);
                    return;
                }
            }

            this.saving = true;
            this.error = "";

            axios.post(`/sadmin/orders/${this.orderId}/refunds`, {
                amount: this.mode === "full" ? null : Number(this.amount),
                action: this.action,
                reason: this.reason || null,
                idempotency_key: this.idempotencyKey,
            })
                .then((resp) => {
                    window.Toast.success(resp.data.message);
                    this.$emit("refunded", resp.data);
                    this.idempotencyKey = this.newKey();
                    this.load();
                })
                .catch((err) => {
                    const data = err.response?.data;
                    this.error = data?.message
                        || Object.values(data?.errors || {})[0]?.[0]
                        || "The provider did not accept this refund.";
                    window.Toast.error("Refund failed");
                })
                .finally(() => {
                    this.saving = false;
                    this.close();
                });
        },

        close() {
            this.dialog = false;
        },
    },
};
</script>

<style scoped>

</style>
