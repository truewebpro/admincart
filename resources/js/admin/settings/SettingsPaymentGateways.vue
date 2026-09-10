<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12">
                <v-card>
                    <v-card-item>
                        <template #prepend>
                            <v-icon>mdi-shield-key</v-icon>
                        </template>
                        <template #title>
                            <div class="text-h5 font-weight-bold">Payment Gateway Credentials</div>
                        </template>
                        <template #subtitle>
                            Used for refunds and cancellations from the order screen.
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>

            <v-col cols="12" md="7">
                <v-card :loading="loading">
                    <v-card-title>Providers</v-card-title>
                    <v-list nav>
                        <v-list-item v-for="row in gateways" :key="row.provider"
                                     class="border-b"
                                     :active="selected === row.provider"
                                     @click="selectProvider(row.provider)">
                            <template #prepend>
                                <v-icon :color="row.configured ? 'success' : 'grey'">
                                    {{ row.configured ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                </v-icon>
                            </template>
                            <template #title>
                                <v-btn variant="text" color="black">{{ row.label }}</v-btn>
                            </template>
                            <template #subtitle>
                                <v-btn v-if="!row.configured" append-icon="mdi-alert-circle" color="grey"
                                       size="small" density="compact">Not set up</v-btn>
                                <template v-else>
                                    <v-btn :color="row.environment === 'production' ? 'red' : 'success'"
                                           size="small" density="compact"
                                           :append-icon="row.environment === 'production' ? 'mdi-alert' : 'mdi-flask'">
                                        {{ row.environment === 'production' ? 'Live' : 'Test' }}
                                    </v-btn>
                                    <v-btn :color="row.is_active ? 'success' : 'red'" size="small"
                                           density="compact" class="ms-1"
                                           :append-icon="row.is_active ? 'mdi-check-circle' : 'mdi-close-circle'">
                                        Status
                                    </v-btn>
                                </template>
                            </template>
                            <template #append>
                                <v-btn color="success" density="compact" class="text-none"
                                       @click.stop="selectProvider(row.provider)">
                                    {{ row.configured ? 'Edit' : 'Add' }}
                                </v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-col>

            <v-col cols="12" md="5">
                <v-card v-if="selected">
                    <v-card-title>{{ schema[selected]?.label }}</v-card-title>
                    <v-card-text>
                        <v-alert v-if="testResult"
                                 :type="testResult.ok ? 'success' : 'error'"
                                 variant="tonal" density="compact" class="mb-3"
                                 :text="testResult.message"/>

                        <v-form v-model="fvalid">
                            <div v-for="field in visibleFields" :key="field.key">
                                <div class="font-weight-medium">{{ field.label }}</div>

                                <v-select v-if="field.type === 'select'"
                                          v-model="form.credentials[field.key]"
                                          :items="field.options" variant="outlined" density="compact"
                                          :rules="ruleFor(field)"
                                          :error-messages="errorFor(field)"
                                          :hint="field.hint" persistent-hint class="mb-2"/>

                                <v-text-field v-else
                                              v-model="form.credentials[field.key]"
                                              variant="outlined" density="compact"
                                              :type="field.type === 'password' && !revealed[field.key] ? 'password' : 'text'"
                                              :append-inner-icon="field.type === 'password'
                                                  ? (revealed[field.key] ? 'mdi-eye-off' : 'mdi-eye')
                                                  : undefined"
                                              :rules="ruleFor(field)"
                                              :error-messages="errorFor(field)"
                                              :hint="field.hint" persistent-hint
                                              autocomplete="off" class="mb-2"
                                              @click:append-inner="revealed[field.key] = !revealed[field.key]"/>
                            </div>

                            <h4 class="mt-2">Environment</h4>
                            <v-radio-group v-model="form.environment" inline hide-details>
                                <v-radio value="sandbox" label="Test" color="green"></v-radio>
                                <v-radio value="production" label="Live" color="red"></v-radio>
                            </v-radio-group>

                            <h4 class="mt-2">Status</h4>
                            <v-radio-group v-model="form.is_active" inline hide-details>
                                <v-radio :value="true" label="Active" color="green"></v-radio>
                                <v-radio :value="false" label="Inactive" color="red"></v-radio>
                            </v-radio-group>

                            <p v-if="hasSecrets" class="text-caption text-medium-emphasis mt-3">
                                Saved secrets show as dots. Leave them untouched to keep the stored value.
                            </p>

                            <v-divider class="my-3"/>

                            <div class="d-flex ga-2">
                                <v-btn @click="save" :disabled="!fvalid" :loading="saving"
                                       density="compact" prepend-icon="mdi-content-save" color="success">
                                    Save
                                </v-btn>
                                <v-btn @click="testConnection" :disabled="!isConfigured || saving"
                                       :loading="testing" density="compact"
                                       prepend-icon="mdi-lan-connect" color="info" variant="tonal">
                                    Test connection
                                </v-btn>
                            </div>
                            <p class="text-caption text-medium-emphasis mt-2">
                                Test checks the saved credentials only. No money moves.
                            </p>
                        </v-form>
                    </v-card-text>
                </v-card>

                <v-card v-else>
                    <v-card-text class="text-medium-emphasis">
                        Pick a provider on the left to enter its details.
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import axios from "axios";

export default {
    name: "SettingsPaymentGateways",
    data() {
        return {
            loading: false,
            saving: false,
            testing: false,
            fvalid: false,
            schema: {},
            gateways: [],
            selected: null,
            form: {credentials: {}, environment: 'production', is_active: true, is_default: false},
            errors: {},
            revealed: {},
            testResult: null,
        }
    },
    computed: {
        // Matches SettingsPayment.vue — the store keeps the shop under state.shop.
        // The API itself reads session('shop_id'); this only triggers a refetch.
        shopId() {
            return this.$store.state.shop.shop_id;
        },
        fields() {
            return this.schema[this.selected]?.fields ?? [];
        },
        // Worldpay asks for a different set depending on the product chosen.
        visibleFields() {
            return this.fields.filter((field) => {
                if (!field.show_if) return true;
                return Object.entries(field.show_if).every(([key, values]) =>
                    values.includes(this.form.credentials[key])
                );
            });
        },
        hasSecrets() {
            return this.fields.some((f) => f.secret);
        },
        isConfigured() {
            return this.gateways.find((g) => g.provider === this.selected)?.configured;
        },
    },
    watch: {
        shopId() {
            this.selected = null;
            this.testResult = null;
            this.getGateways();
        },
    },
    mounted() {
        this.getGateways();
    },
    methods: {
        getGateways() {
            this.loading = true;
            Promise.all([
                axios.get('/sadmin/payment-gateways/schema'),
                axios.get('/sadmin/payment-gateways'),
            ]).then(([schemaResp, listResp]) => {
                this.schema = schemaResp.data.providers;
                this.gateways = listResp.data.gateways;
                if (this.selected) this.buildForm(this.selected);
            }).catch((err) => {
                window.Toast.error(err.response?.status === 409
                    ? 'No shop is selected.'
                    : 'Could not load payment providers.');
            }).finally(() => {
                this.loading = false;
            });
        },

        selectProvider(provider) {
            this.selected = provider;
            this.errors = {};
            this.testResult = null;
            this.revealed = {};
            this.buildForm(provider);
        },

        buildForm(provider) {
            const row = this.gateways.find((g) => g.provider === provider) || {};
            const credentials = {};

            (this.schema[provider]?.fields ?? []).forEach((field) => {
                credentials[field.key] = row.credentials?.[field.key] ?? field.default ?? null;
            });

            this.form = {
                credentials,
                environment: row.environment || 'production',
                is_active: row.is_active !== undefined ? row.is_active : true,
                is_default: row.is_default || false,
            };
        },

        ruleFor(field) {
            if (!field.required) return [];
            return [(v) => !!v || `${field.label} is required`];
        },

        errorFor(field) {
            return this.errors[`credentials.${field.key}`] ?? [];
        },

        save() {
            this.saving = true;
            this.errors = {};
            this.testResult = null;

            // Only the fields on screen, so switching Worldpay product does not
            // carry credentials over from the other one.
            const credentials = {};
            this.visibleFields.forEach((field) => {
                credentials[field.key] = this.form.credentials[field.key];
            });

            axios.put(`/sadmin/payment-gateways/${this.selected}`, {
                credentials,
                environment: this.form.environment,
                is_active: this.form.is_active,
                is_default: this.form.is_default,
            }).then(() => {
                window.Toast.success('Credentials saved');
            }).catch((err) => {
                this.errors = err.response?.data?.errors ?? {};
                window.Toast.error(err.response?.data?.message ?? 'Check the highlighted fields');
            }).finally(() => {
                this.saving = false;
                this.getGateways();
            });
        },

        testConnection() {
            this.testing = true;
            this.testResult = null;

            axios.post(`/sadmin/payment-gateways/${this.selected}/test`)
                .then((resp) => {
                    this.testResult = {ok: true, message: resp.data.message};
                    window.Toast.success('Credentials accepted');
                })
                .catch((err) => {
                    this.testResult = {
                        ok: false,
                        message: err.response?.data?.message ?? 'The check could not be completed.',
                    };
                    window.Toast.error('Credential check failed');
                })
                .finally(() => {
                    this.testing = false;
                });
        },
    }
}
</script>

<style scoped>

</style>
