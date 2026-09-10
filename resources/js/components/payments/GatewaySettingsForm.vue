<script>
import { paymentsApi } from '@/services/payments.js'

export default {
    name: 'GatewaySettingsForm',
    data() {
        return {
            loading: true,
            saving: false,
            testing: null,
            testResults: {},
            tab: null,
            schema: {},
            gateways: [],
            errors: {},
            notice: '',
            revealed: {},
            forms: {},
            environmentOptions: [
                { value: 'sandbox', title: 'Test' },
                { value: 'production', title: 'Live' },
            ],
        }
    },
    computed: {
        // The API never receives a shop id — the server reads session('shop_id').
        // This only exists so the form refetches when the operator switches shops.
        shopId() {
            return this.$store.state.shop_id
        },
    },
    watch: {
        shopId() {
            this.testResults = {}
            this.load()
        },
    },
    mounted() {
        this.load()
    },
    methods: {
        async load() {
            this.loading = true
            try {
                const [providers, saved] = await Promise.all([
                    paymentsApi.gatewaySchema(),
                    paymentsApi.listGateways(),
                ])

                this.schema = providers
                this.gateways = saved
                this.tab = this.tab ?? saved[0]?.provider ?? null

                const forms = {}
                saved.forEach((row) => {
                    const credentials = {}
                    providers[row.provider].fields.forEach((field) => {
                        credentials[field.key] =
                            row.credentials?.[field.key] ?? field.default ?? null
                    })

                    forms[row.provider] = {
                        environment: row.environment,
                        is_active: row.is_active,
                        is_default: row.is_default,
                        credentials,
                    }
                })
                this.forms = forms
            } catch (e) {
                this.notice =
                    e.response?.status === 409
                        ? 'No shop is selected. Pick a shop to manage its payment providers.'
                        : 'Could not load the payment providers.'
            } finally {
                this.loading = false
            }
        },

        /** A field only applies when all of its show_if dependencies are satisfied. */
        isVisible(provider, field) {
            if (!field.show_if) return true

            return Object.entries(field.show_if).every(([key, values]) =>
                values.includes(this.forms[provider]?.credentials?.[key]),
            )
        },

        visibleFields(provider) {
            return (this.schema[provider]?.fields ?? []).filter((field) =>
                this.isVisible(provider, field),
            )
        },

        requiredRule(field) {
            return field.required ? [(v) => !!v || `${field.label} is required.`] : []
        },

        fieldErrors(field) {
            return this.errors[`credentials.${field.key}`] ?? []
        },

        isConfigured(provider) {
            return this.gateways.find((g) => g.provider === provider)?.configured
        },

        hasSecrets(definition) {
            return definition.fields.some((field) => field.secret)
        },

        inputType(field) {
            return field.type === 'password' && !this.revealed[field.key] ? 'password' : 'text'
        },

        revealIcon(field) {
            if (field.type !== 'password') return undefined
            return this.revealed[field.key] ? 'mdi-eye-off' : 'mdi-eye'
        },

        toggleReveal(field) {
            this.revealed[field.key] = !this.revealed[field.key]
        },

        async save(provider) {
            this.saving = true
            this.notice = ''
            this.errors = {}

            // Send only the fields that currently apply, so switching Worldpay
            // products does not carry stale credentials from the other one.
            const credentials = {}
            this.visibleFields(provider).forEach((field) => {
                credentials[field.key] = this.forms[provider].credentials[field.key]
            })

            try {
                const result = await paymentsApi.saveGateway(provider, {
                    ...this.forms[provider],
                    credentials,
                })
                this.notice = result.message
                delete this.testResults[provider] // stale once the keys change
                await this.load()
            } catch (e) {
                this.errors = e.response?.data?.errors ?? {}
                this.notice =
                    e.response?.data?.message ?? 'Check the highlighted fields and save again.'
            } finally {
                this.saving = false
            }
        },

        /**
         * Read-only check against the saved credentials. It cannot move money,
         * but it also only proves authentication — not that a refund would be
         * accepted. Save first: the server tests what is stored, not the form.
         */
        async testConnection(provider) {
            this.testing = provider
            delete this.testResults[provider]

            try {
                const result = await paymentsApi.testGateway(provider)
                this.testResults[provider] = { ok: true, message: result.message }
            } catch (e) {
                this.testResults[provider] = {
                    ok: false,
                    message:
                        e.response?.data?.message ??
                        'The check could not be completed. Try again shortly.',
                }
            } finally {
                this.testing = null
            }
        },
    },
}
</script>

<template>
    <v-card :loading="loading">
        <v-card-title>Payment providers</v-card-title>
        <v-card-subtitle>
            Each provider is stored separately for this shop, with only the credentials it needs.
        </v-card-subtitle>

        <v-tabs v-model="tab" density="comfortable">
            <v-tab v-for="(definition, provider) in schema" :key="provider" :value="provider">
                {{ definition.label }}
                <v-icon
                    v-if="isConfigured(provider)"
                    icon="mdi-check-circle"
                    size="small"
                    color="success"
                    class="ml-2"
                />
            </v-tab>
        </v-tabs>

        <v-divider />

        <v-window v-model="tab">
            <v-window-item v-for="(definition, provider) in schema" :key="provider" :value="provider">
                <v-card-text v-if="forms[provider]">
                    <v-alert
                        v-if="notice && tab === provider"
                        type="info"
                        variant="tonal"
                        class="mb-4"
                        :text="notice"
                    />

                    <v-alert
                        v-if="testResults[provider]"
                        :type="testResults[provider].ok ? 'success' : 'error'"
                        variant="tonal"
                        class="mb-4"
                        :text="testResults[provider].message"
                    />

                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <v-select
                                v-model="forms[provider].environment"
                                :items="environmentOptions"
                                label="Environment"
                                density="comfortable"
                            />
                        </v-col>
                        <v-col cols="12" sm="6" class="d-flex align-center ga-4">
                            <v-switch
                                v-model="forms[provider].is_active"
                                label="Active"
                                color="primary"
                                density="comfortable"
                                hide-details
                            />
                            <v-switch
                                v-model="forms[provider].is_default"
                                label="Use by default"
                                color="primary"
                                density="comfortable"
                                hide-details
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <v-row dense>
                        <v-col
                            v-for="field in visibleFields(provider)"
                            :key="field.key"
                            cols="12"
                            :sm="field.type === 'select' ? 12 : 6"
                        >
                            <v-select
                                v-if="field.type === 'select'"
                                v-model="forms[provider].credentials[field.key]"
                                :items="field.options"
                                :label="field.label"
                                :hint="field.hint"
                                :rules="requiredRule(field)"
                                :error-messages="fieldErrors(field)"
                                persistent-hint
                                density="comfortable"
                            />

                            <v-switch
                                v-else-if="field.type === 'switch'"
                                v-model="forms[provider].credentials[field.key]"
                                :label="field.label"
                                color="primary"
                                density="comfortable"
                            />

                            <v-text-field
                                v-else
                                v-model="forms[provider].credentials[field.key]"
                                :label="field.label"
                                :hint="field.hint"
                                :rules="requiredRule(field)"
                                :error-messages="fieldErrors(field)"
                                :type="inputType(field)"
                                :append-inner-icon="revealIcon(field)"
                                persistent-hint
                                density="comfortable"
                                autocomplete="off"
                                @click:append-inner="toggleReveal(field)"
                            />
                        </v-col>
                    </v-row>

                    <p v-if="hasSecrets(definition)" class="text-caption text-medium-emphasis mt-2">
                        Saved secrets are shown masked. Leave a masked field untouched to keep the stored value.
                    </p>
                </v-card-text>

                <v-card-actions>
                    <v-btn
                        variant="text"
                        :loading="testing === provider"
                        :disabled="!isConfigured(provider) || saving"
                        @click="testConnection(provider)"
                    >
                        Test connection
                    </v-btn>
                    <span class="text-caption text-medium-emphasis ml-2">
            Checks the saved credentials only — no money moves.
          </span>
                    <v-spacer />
                    <v-btn color="primary" variant="flat" :loading="saving" @click="save(provider)">
                        Save {{ definition.label }}
                    </v-btn>
                </v-card-actions>
            </v-window-item>
        </v-window>
    </v-card>
</template>
