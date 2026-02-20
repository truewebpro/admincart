<template>
    <v-container class="settings">
        <h2>Settings</h2>
        <v-row>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsGeneral'}">
                    <v-card-title>
                        <v-icon>mdi-cog</v-icon>
                        General
                    </v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Set Company  default Address, Phone and Other details for store and more
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsShipping'}">
                    <v-card-title>
                        <v-icon>mdi-truck</v-icon>
                        Shipping Methods</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Set Store Shipping Methods e.g: Royal Mail, DPD or more
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsPayment'}">
                    <v-card-title>
                        <v-icon>mdi-account-credit-card-outline</v-icon>
                        Payment Methods</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Set company / Store Payment Methods e.g: Bank Transfer, Stripe, Viva Wallet or more
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsPrinting'}">
                    <v-card-title>
                        <v-icon>mdi-printer</v-icon>
                        Printing Templates</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Set Order Picking, Packing, Shipping Invoices, Notes Templates and more
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsMarketing'}">
                    <v-card-title>
                        <v-icon>mdi-sale</v-icon>
                        Marketing</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Set Store Marketing Campaigns with Coupons. Applicable with logical conditions.
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsInventory'}">
                    <v-card-title>
                        <v-icon>mdi-tag</v-icon>
                        Inventory</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Location and Inventory settings with Tier Logic for B2B Portals
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsReview'}">
                    <v-card-title>
                        <v-icon>mdi-star-check-outline</v-icon>
                        Reviews</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Manage Product Reviews by the customers. Interactions for existing reviews and more.
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm" :to="{name:'SettingsAccount'}">
                    <v-card-title>
                        <v-icon>mdi-account-sync-outline</v-icon>
                        Accounts</v-card-title>
                    <v-card-text>
                        <div class="text-body-1">
                            Manage Store Subscription and Payouts with Truewebcart.
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
