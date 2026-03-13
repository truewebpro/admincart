<template>
    <v-container class="settings pa-2">
        <h2>Settings</h2>
        <v-row>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsGeneral'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg pa-1 me-2 elevation-1">
                            <v-icon size="45" class="rounded-lg">mdi-cog</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">General</div>
                            <div class="text-body-1">
                                Set Company Informational Details
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsShipping'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45" class="rounded-lg">mdi-truck-fast</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Shipping Methods</div>
                            <div class="text-body-1">
                                Set Store Shipping Methods
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsPayment'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45">mdi-account-credit-card-outline</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Payment Methods</div>
                            <div class="text-body-1">
                                Set Bank Transfer, Stripe, Viva etc
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsPrinting'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45">mdi-printer</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Printing Templates</div>
                            <div class="text-body-1">
                                Set Order Picking, Packing, Templates
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsMarketing'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45">mdi-sale</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Marketing</div>
                            <div class="text-body-1">
                                Set Marketing Campaigns, Coupons
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsInventory'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45">mdi-tag</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Inventory</div>
                            <div class="text-body-1">
                                Locations & Stock Tier Logic
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsReview'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45">mdi-star-check-outline</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Reviews</div>
                            <div class="text-body-1">
                                Manage Product Reviews & Ratings
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm rounded-lg" :to="{name:'SettingsAccount'}">
                    <v-card-text class="d-flex flex align-center">
                        <div class="bg-grey-lighten-3 rounded-lg me-2 pa-1 elevation-1">
                            <v-icon size="45">mdi-account-sync-outline</v-icon>
                        </div>
                        <div>
                            <div class="font-weight-bold text-h6">Accounts</div>
                            <div class="text-body-1">
                                Manage Store Subscription & Payouts.
                            </div>
                        </div>
                        <div class="ms-auto">
                            <v-icon size="x-large">mdi-chevron-right</v-icon>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
export default {
    name:"Settings",
    data(){
        return{
            cdn:this.$store.state.cdn,
        }
    }
}

</script>

<style scoped>

</style>
