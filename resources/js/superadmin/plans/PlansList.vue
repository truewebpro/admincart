<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="6" md="6">
                <h2>Plans List</h2>
            </v-col>
            <v-col cols="6" md="6">
                <v-btn variant="elevated" color="primary" density="comfortable">Add Plan</v-btn>
            </v-col>
<!--            <v-col cols="12" md="12">-->
<!--                <v-card>-->
<!--                    <v-text-field v-model="psearch" variant="outlined" density="compact" hide-details-->
<!--                                  class="px-2 py-2" prependInnerIcon="mdi-magnify" placeholder="Search Plans"></v-text-field>-->
<!--                    <v-data-table :items="plans" :headers="planHeaders" :search="psearch"-->
<!--                                  :hideDefaultFooter="plans?.length < 10" noDataText="Loading Plans">-->
<!--                        <template #item.name="{item}">-->
<!--                            <div>-->
<!--                                <div>{{item.name}}</div>-->
<!--                                <div>{{item.slug}}</div>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                        <template #item.stripe_price_id="{item}">-->
<!--                            <div>-->
<!--                                <div>{{item.stripe_price_id}}</div>-->
<!--                                <div>{{item.stripe_product_id}}</div>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                        <template #item.price="{item}">-->
<!--                            <div class="d-flex align-center ga-1">-->
<!--                                <h2>£{{(item.price/100).toFixed(2)}}</h2>-->
<!--                                <div>{{item.interval}}</div>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                        <template #item.is_active="{item}">-->
<!--                            <div>-->
<!--                                <div>{{item.sort_order}}</div>-->
<!--                                <div>{{item.is_active}}</div>-->
<!--                                <div>{{item.is_popular}}</div>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                        <template #item.features="{item}">-->
<!--                            <div>-->
<!--                                <div>{{item.features}}</div>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                        <template #item.actions="{item}">-->
<!--                            <div>-->
<!--                                <v-btn color="info" density="comfortable" prependIcon="mdi-pencil">Edit</v-btn>-->
<!--                            </div>-->
<!--                        </template>-->
<!--                    </v-data-table>-->
<!--                </v-card>-->
<!--            </v-col>-->
        </v-row>
        <v-row>
            <v-col cols="12" md="3" v-for="(plan,index) in plans" :key="plan.id">
                <v-card class="border-sm" :class="plan.is_active === true ? 'bg-green-lighten-5' : 'bg-red-lighten-4' " :elevation="plan.is_popular === true ? '10' : '2' ">
                    <v-card-title class="d-flex justify-space-between align-center">
                        <span>{{plan.name}}</span>
                        <v-chip v-if="plan.is_popular" color="green" variant="elevated" density="comfortable">Popular</v-chip>
                    </v-card-title>
                    <v-card-title class="d-flex ga-2 align-center">
                        <span>£{{(plan.price/100).toFixed(0)}}</span>
                        <span class="text-body-2">{{plan.interval}}</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-for="feature in planFeatures" :key="feature.key"
                             class="d-flex ga-2 justify-space-between align-center py-1">
                            <span class="font-weight-medium">{{ feature.label }}</span>
                            <span>
                              <!-- BOOLEAN -->
                              <template v-if="feature.type === 'boolean'">
                                <v-icon size="18" :color="plan.features?.[feature.key] ? 'green' : 'red'">
                                  {{ plan.features?.[feature.key] ? 'mdi-check' : 'mdi-close' }}
                                </v-icon>
                              </template>
                              <template v-else>
                                {{ plan.features?.[feature.key] ?? '-' }}
                              </template>
                            </span>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn color="success" variant="elevated" density="comfortable">Edit</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
export default {
    name: "PlansList",
    data(){
        return {
            psearch:"",
            plans:[],
            planHeaders:[
                {title:"Name",key:'name'},
                {title:"stripe_price_id",key:'stripe_price_id'},
                {title:"price",key:'price'},
                {title:"is_active",key:'is_active'},
                {title:"features",key:'features'},
                {title:"Actions",key:'actions'},
            ]
        }
    },
    computed: {
        planFeatures() {
            return this.$store.getters.planFeatures
        }
    },
    async mounted() {
        await this.getPlans();
    },
    methods:{
        createPlan(type) {
            const features = this.$store.getters.getPlanPreset(type)

            const newPlan = {
                name: type.charAt(0).toUpperCase() + type.slice(1),
                slug: type,
                price: 0,
                interval: 'month',
                is_active: true,
                is_popular: false,
                features
            }

            this.plans.push(newPlan)
        },
        getPlans() {
            axios.get('/superadmin/plans')
                .then((resp) => {
                    this.plans = resp.data.plans.map(plan => {
                        const preset = this.$store.getters.getPlanPreset(plan.slug)
                        return {
                            ...plan,
                            features: {
                                ...preset,
                                ...(plan.features || {})
                            }
                        }
                    })
                })
        },
        openFeatureDialog(plan){
            console.log("plan",plan);
        }
    }
}
</script>

<style scoped>

</style>
