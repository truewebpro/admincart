<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6"><h2>Account</h2></v-col>
            <v-col cols="12" md="6" class="text-md-end">
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="6">
                <div v-if="billing">
                    <h3>Current Plan: {{ billing.plan?.name }}</h3>
                    <p v-if="billing?.subscribed && !billing?.grace_period">
                        Status: Active
                    </p>
                    <p v-if="billing?.grace_period">
                        Status: Cancelling (Active until {{ billing.ends_at }})
                    </p>
                    <p v-if="!billing?.subscribed">
                        No active subscription. Please choose a plan.
                    </p>
                    <div v-if="billing?.card?.last4">
                        Card: {{ billing.card.brand }} **** {{ billing.card.last4 }}
                    </div>
                    <div v-if="billing?.grace_period">
                        <v-alert type="warning">
                            Your subscription will end on {{ new Date(billing.ends_at).toLocaleDateString() }}
                        </v-alert>
                    </div>
<!--                    <v-btn-->
<!--                        v-if="billing?.subscribed"-->
<!--                        color="red"-->
<!--                        @click="cancelSubscription"-->
<!--                    >-->
<!--                        Cancel Subscription-->
<!--                    </v-btn>-->
                    <v-card class="mt-4">
                        <v-card-title>Invoices</v-card-title>
                        <v-list v-if="billing.invoices?.length">
                            <v-list-item v-for="inv in billing.invoices" :key="inv.id">
                                <div class="d-flex justify-space-between w-100">
                                    <span>{{ inv.date }} - {{inv.total}}</span>
                                    <a :href="inv.url" target="_blank">Download</a>
                                </div>
                            </v-list-item>
                        </v-list>
                        <div v-else class="pa-4">No invoices yet</div>
                    </v-card>
                </div>
            </v-col>
            <v-col cols="12" md="6">
            </v-col>
        </v-row>
        <v-row v-if="plans?.length > 0">
            <v-col cols="12" md="12">
                <v-alert type="info" class="mb-4" v-if="!billing?.subscribed">
                    You are not subscribed to any plan yet.
                </v-alert>
            </v-col>

            <v-col v-for="(plan,index) in plans" cols="12" md="6" lg="3">
                <v-card :elevation="currentPlan?.slug === plan.slug ? 10 : 2"
                        :variant="plan.is_popular ? 'outlined' : 'flat' " class="border-sm">
                    <v-chip density="compact" v-if="plan.is_popular"
                            color="primary" variant="outlined" class="position-absolute right-0 top-0">
                        Popular
                    </v-chip>
                    <v-card-title>{{plan.name}}</v-card-title>
                    <v-card-text>
                        <div class="d-flex align-end ga-2">
                            <h2 class="dd">£{{(plan.price/100)}}</h2>
                            <div>{{plan.interval}}</div>
                        </div>
                        <div v-for="feature in planFeatures" :key="feature.key"
                             class="d-flex ga-2 justify-space-between align-center py-2 border-b-sm">
                            <span class="font-weight-medium">{{ feature.label }}</span>
                            <span>
                              <!-- BOOLEAN -->
                              <template v-if="feature.type === 'boolean'">
                                <v-icon size="18" :color="plan.features?.[feature.key] ? 'green' : 'red'">
                                  {{ plan.features?.[feature.key] ? 'mdi-check-circle-outline' : 'mdi-close-circle-outline' }}
                                </v-icon>
                              </template>
                              <template v-else>
                                {{ plan.features?.[feature.key] ?? '-' }}
                              </template>
                            </span>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn
                               :color="currentPlan?.slug === plan.slug ? 'grey' : 'success'"
                               block
                               @click="subscribe(plan.slug)"
                               :disabled="currentPlan?.slug === plan.slug"
                               variant="elevated" density="comfortable">
                            {{ currentPlan?.slug === plan.slug ? 'Current Plan' : (billing?.subscribed ? 'Change Plan' : 'Subscribe') }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>

export default {
    name:"SettingsAccount",
    data(){
        return{
            stripe: null,
            card: null,
            billing: null,
            cardComplete: false,
            plans:[],
            currentPlan: null,
            isSubscribed: false
        }
    },
    computed: {
        planFeatures() {
            return this.$store.getters.planFeatures
        }
    },
    async mounted() {
        await this.init();
    },
    methods:{
        async init(){
            await this.getPlans();
            await this.getCurrentPlan();
            await this.getBilling();
        },
        async getCurrentPlan(){
            try {
                const respo = await axios.get('/subscription');
                this.currentPlan = respo.data.plan;
                this.isSubscribed = respo.data.subscribed;
            } catch (e){
                console.log(e)
            }
        },
        async getPlans(){
            try {
                const res = await axios.get('/sadmin/plans')
                this.plans = res.data.map(plan => {
                    const preset = this.$store.getters.getPlanPreset(plan.slug)
                    return {
                        ...plan,
                        features: {
                            ...preset,
                            ...(plan.features || {})
                        }
                    }
                })
            } catch (e){
                console.log(e)
            }
        },
        async getBilling(){
            const res = await axios.get('/billing');
            this.billing = res.data;
        },
        async cancelSubscription(){
            if (!confirm("Are you sure you want to cancel your subscription?")) return;
            await axios.post('/subscription/cancel');
            await this.getBilling();
        },
        async subscribe(planSlug){
            const res = await axios.post('/subscribe', {
                plan: planSlug
            });
            if (res.data.url) {
                window.location.href = res.data.url;
            } else {
                await this.getBilling();
                await this.getCurrentPlan();
            }
        }
    }
}

</script>

<style scoped>

</style>
