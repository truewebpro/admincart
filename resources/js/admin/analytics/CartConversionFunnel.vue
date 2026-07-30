<template>
    <div class="mt-2">
        <h2 class="mb-2">Cart Conversion Funnel</h2>
        <v-row class="mb-2">
            <v-col cols="12" sm="4">
                <v-card rounded="lg">
                    <v-card-item title="Conversion Rate" append-icon="mdi-cart-percent"></v-card-item>
                    <v-card-text>
                        <div class="text-h5 font-weight-bold">{{ conversion.conversion_rate }}%</div>
                        <div class="text-body-2 text-medium-emphasis">
                            {{ conversion.converted_carts }} of {{ conversion.total_carts }} carts
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="4">
                <v-card rounded="lg">
                    <v-card-item title="Recovered Revenue" append-icon="mdi-cart-check"></v-card-item>
                    <v-card-text>
                        <div class="text-h5 font-weight-bold">{{ formatCurrency(conversion.converted_value) }}</div>
                        <div class="text-body-2 text-medium-emphasis">
                            {{ conversion.converted_carts }} of {{ conversion.total_carts }} carts
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" sm="4">
                <v-card rounded="lg">
                    <v-card-item title="Abandoned Value" append-icon="mdi-cart-minus"></v-card-item>
                    <v-card-text>
                        <div class="text-h5 font-weight-bold text-error">{{ formatCurrency(conversion.abandoned_value) }}</div>
                        <div class="text-body-2 text-medium-emphasis">{{ conversion.abandoned_carts }} carts</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-card elevation="2" rounded="lg">
            <v-card-text>
                <v-progress-linear v-show="loading" indeterminate class="mb-4" />
                <apexchart
                    v-show="!loading"
                    type="bar"
                    height="280"
                    :options="chartOptions"
                    :series="chartSeries"
                />
            </v-card-text>
        </v-card>
        <v-card class="mt-4" rounded="lg">
            <v-table density="compact" hover>
                <thead class="bg-grey-lighten-4">
                <tr>
                    <th>Stage</th>
                    <th class="text-right">Carts</th>
                    <th class="text-right">Drop-off</th>
                    <th class="text-right">% of Start</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="stage in funnel" :key="stage.stage">
                    <td class="font-weight-bold">{{ stageLabel(stage.stage) }}</td>
                    <td class="text-right">{{ stage.cart_count }}</td>
                    <td class="text-right">
              <span v-if="stage.drop_off_from_previous !== null" class="text-error">
                −{{ stage.drop_off_from_previous }}%
              </span>
                        <span v-else class="text-medium-emphasis">—</span>
                    </td>
                    <td class="text-right">{{ stage.percent_of_start }}%</td>
                </tr>
                </tbody>
            </v-table>
        </v-card>
    </div>
</template>

<script>
export default {
    name: 'CartConversionFunnel',

    props: {
        from: { type: String, required: true },
        to: { type: String, required: true },
    },

    data() {
        return {
            loading: false,
            funnel: [],
            conversion: {
                total_carts: 0,
                converted_carts: 0,
                abandoned_carts: 0,
                conversion_rate: 0,
                abandoned_value: 0,
                converted_value: 0,
            },

            stageLabels: {
                item_added: 'Item Added',
                checkout_started: 'Checkout Started',
                customer_attached: 'Customer Details',
                payment_selected: 'Payment Selected',
                shipping_selected: 'Shipping Selected',
                order_created: 'Order Placed',
            },
        };
    },

    computed: {
        chartSeries() {
            return [{ name: 'Carts', data: this.funnel.map((s) => s.cart_count) }];
        },

        chartOptions() {
            return {
                chart: { toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, distributed: true, barHeight: '60%' } },
                dataLabels: { enabled: true },
                legend: { show: false },
                xaxis: {
                    categories: this.funnel.map((s) => this.stageLabel(s.stage)),
                },
                colors: ['#1976D2', '#2196F3', '#42A5F5', '#64B5F6', '#90CAF9', '#4CAF50'],
            };
        },
    },

    watch: {
        from() { this.fetchAll(); },
        to() { this.fetchAll(); },
    },

    mounted() {
        this.fetchAll();
    },

    methods: {
        fetchAll() {
            this.fetchConversion();
            this.fetchFunnel();
        },

        async fetchConversion() {
            try {
                const { data } = await axios.get('/sadmin/analytics/cart-conversion', {
                    params: { from: this.from, to: this.to },
                });
                this.conversion = data;
            } catch (e) {
                console.error('Failed to load cart conversion', e);
            }
        },

        async fetchFunnel() {
            this.loading = true;
            try {
                const { data } = await axios.get('/sadmin/analytics/cart-funnel', {
                    params: { from: this.from, to: this.to },
                });
                this.funnel = data.funnel || [];
            } catch (e) {
                console.error('Failed to load cart funnel', e);
            } finally {
                this.loading = false;
            }
        },

        stageLabel(stage) {
            return this.stageLabels[stage] || stage;
        },

        formatCurrency(value) {
            if (value === null || value === undefined) return '—';
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value);
        },
    },
};
</script>
