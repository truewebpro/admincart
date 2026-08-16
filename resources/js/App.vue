<template>
    <v-app>
        <v-navigation-drawer
            v-model="drawer"
            :rail="isDesktop"
            :rail-width="50"
            expand-on-hover
            :temporary="!isDesktop"
            :location="$vuetify.display.mobile ? 'left' : undefined">
            <v-list density="compact" nav color="primary"
                    class="fw-bold d-flex flex-column fill-height overflow-y-auto"
                    activeClass="bg-blue-lighten-5">
                <v-list-item v-if="this.$store.state.shop.shop_slug" base-color="dark" :prepend-avatar="cdn+this.$store.state.shop.shop_slug+'/favicon.ico'">
                    <v-list-item-title>
                        <v-img class="ms-2" width="100" height="25" :src="cdn+this.$store.state.shop.shop_slug+'/logo.png'"></v-img>
                    </v-list-item-title>
                </v-list-item>
                <v-divider></v-divider>
                <v-list-item v-if="isSuperAdmin" link :to="{name:'SuperAdminDashboard'}" prepend-icon="mdi-view-dashboard-outline">
                    <v-list-item-title>Super Dashboard</v-list-item-title>
                </v-list-item>
                <v-list-item v-if="isSuperAdmin" link :to="{name:'SuperAdminShops'}" prepend-icon="mdi-store">
                    <v-list-item-title>Shops</v-list-item-title>
                </v-list-item>
                <v-list-item v-if="isSuperAdmin" link :to="{name:'SuperShopifysetup'}" :prependAvatar="cdn+'icons/shopify.png'">
                    <v-list-item-title>Shopify</v-list-item-title>
                </v-list-item>
                <v-list-item class="d-none" link to="/dashboard" prepend-icon="mdi-view-dashboard-outline">
                    <v-list-item-title> <span v-if="isSuperAdmin">Shop</span> Dashboard</v-list-item-title>
                </v-list-item>
                <v-list-item link :to="{name:'AnalyticsOverview'}" prepend-icon="mdi-view-dashboard-outline">
                    <v-list-item-title> <span v-if="isSuperAdmin">Shop</span> Dashboard</v-list-item-title>
                </v-list-item>
                <v-list-group value="acarts">
                    <template v-slot:activator="{ props }">
                        <v-list-item v-bind="props" prepend-icon="mdi-cart-check">
                            <template #title>
                                All Orders
                                <v-chip size="small" color="primary" class="bg-light-subtle font-weight-bold">
                                   {{orderStats.pending}}
                                </v-chip>
                            </template>
                        </v-list-item>
                    </template>
                    <v-list-item link :to="{name:'AdminOrders'}" color="primary" prepend-icon="mdi-cart-arrow-right" title="Orders"></v-list-item>
                    <v-list-item link :to="{name:'DraftOrdersList'}" color="primary" prepend-icon="mdi-cart-plus" title="Drafts"></v-list-item>
                    <v-list-item link :to="{name:'AbandonedCarts'}" color="primary" prepend-icon="mdi-cart-arrow-down" title="Abandoned Carts"></v-list-item>
                </v-list-group>
                <v-list-group value="pros">
                    <template v-slot:activator="{ props }">
                        <v-list-item v-bind="props" prepend-icon="mdi-tag-multiple-outline" title="Products" />
                    </template>
                    <v-list-item link :to="{name:'products'}" color="primary" prepend-icon="mdi-tag-outline" title="Products List"></v-list-item>
                    <v-list-item :to="{name:'cats'}" color="primary" prepend-icon="mdi-format-list-text" title="Categories"/>
                    <v-list-item :to="{name:'ptypes'}" color="primary" title="Types" prepend-icon="mdi-list-status" link/>
                    <v-list-item :to="{name:'brands'}" color="primary" title="Brands" prepend-icon="mdi-shield-star" link/>
                    <v-list-item :to="{name:'tags'}" color="primary" title="Tags"  prepend-icon="mdi-tag-multiple" link/>
                    <v-list-item :to="{name:'poptions'}" color="primary" title="Options" prepend-icon="mdi-filter-variant" link/>
                    <v-list-item :to="{name:'Features'}" color="primary" title="Features" prepend-icon="mdi-feature-search-outline" link/>
                </v-list-group>
                <v-list-item link :to="{name:'InventoryList'}" color="primary" prepend-icon="mdi-format-list-numbered"
                             title="Inventory" subtitle="All Products">
                </v-list-item>
                <v-list-item link :to="{name:'customers'}" color="primary" prepend-icon="mdi-account" title="Customers">
                </v-list-item>
                <v-list-group value="analytic">
                    <template v-slot:activator="{ props }">
                        <v-list-item v-bind="props" prepend-icon="mdi-google-analytics">
                            <template #title>
                                Analytics
                            </template>
                        </v-list-item>
                    </template>
                    <v-list-item link :to="{name:'LiveViewPage'}" prepend-icon="mdi-timer-marker-outline"  title="Live View"></v-list-item>
                    <v-list-item link :to="{name:'AnalyticsTraffic'}" prepend-icon="mdi-chart-timeline" title="Traffic & Cart"></v-list-item>
                    <v-list-item link :to="{name:'SessionsAnalytics'}" prepend-icon="mdi-chart-timeline-variant-shimmer" title="Sessions"></v-list-item>
                    <v-list-item link :to="{name:'ReferrerProductAnalytics'}" prepend-icon="mdi-chart-box-outline"  title="Referrers"></v-list-item>

                    <v-list-item link :to="{name:'AnalyticsOrders'}" color="primary" prepend-icon="mdi-finance" title="Customers"></v-list-item>
                </v-list-group>
                <v-list-group value="themes">
                    <template v-slot:activator="{props}">
                        <v-list-item v-bind="props" prepend-icon="mdi-store-settings" title="Shop Settings"></v-list-item>
                    </template>
                    <v-list-item link :to="{name:'ShopHome'}" color="primary" prepend-icon="mdi-store-cog" title="Theme Settings">
                    </v-list-item>
<!--                    <v-list-item link :to="{name:'ThemeView'}" color="primary" title="Theme" prepend-icon="mdi-tablet-cellphone"></v-list-item>-->
                    <v-list-item link :to="{name:'Preferences'}" color="primary" title="Preferences" prepend-icon="mdi-web-plus"></v-list-item>
                    <v-list-item link :to="{name:'MenusList'}" color="primary" title="Menus" prepend-icon="mdi-menu"></v-list-item>
                    <v-list-item link :to="{name:'PagesList'}" title="Pages" color="primary" prepend-icon="mdi-page-next-outline"></v-list-item>
                    <v-list-item link :to="{name:'BlogsList'}" title="Blogs" color="primary" prepend-icon="mdi-post-outline"></v-list-item>
                    <v-list-item link :to="{name:'PoliciesList'}" title="Policies" color="primary" prepend-icon="mdi-file-sign"></v-list-item>
                </v-list-group>
                <v-list-group value="loyalty" base-color="success">
                    <template v-slot:activator="{props}">
                        <v-list-item v-bind="props" prepend-icon="mdi-shield-star-outline" title="Loyalty Program"></v-list-item>
                    </template>
                    <v-list-item link :to="{name:'LoyaltySettings'}" color="success" prepend-icon="mdi-star-cog-outline" title="Earn Setting"></v-list-item>
                    <v-list-item link :to="{name:'LoyaltyProductPointsOverride'}" color="success" prepend-icon="mdi-octagram-edit-outline" title="Product Override"></v-list-item>
                    <v-list-item link :to="{name:'LoyaltyEarnActions'}" color="success" prepend-icon="mdi-qrcode-plus" title="Earn Actions"></v-list-item>
                    <v-list-item link :to="{name:'LoyaltyActionReviewQueue'}" color="success" prepend-icon="mdi-queue-first-in-last-out" title="Review Queue"></v-list-item>
                </v-list-group>
                <v-spacer/>
                <v-list-item v-if="isSuperAdmin" link :to="{name:'PlansList'}" prepend-icon="mdi-credit-card">
                    <v-list-item-title>Plans</v-list-item-title>
                </v-list-item>


                <v-list-item link :to="{name:'IntegrateList'}" color="primary" prepend-icon="mdi-apps" title="Integration">
                </v-list-item>
                <v-list-item link :to="{name:'Settings'}" color="primary" prepend-icon="mdi-cog" title="Settings">
                </v-list-item>
                <v-divider class="my-1"></v-divider>
                <v-list-item prepend-icon="mdi-logout">
                    <v-list-item-title>
                        <v-btn @click="logout" variant="outlined" size="small" block title="Logout">Logout</v-btn>
                    </v-list-item-title>
                </v-list-item>
            </v-list>
        </v-navigation-drawer>
        <v-main class="py-1 bg-grey-lighten-5">
            <v-container class="pa-0 mt-1 mb-1" v-if="isSuperAdmin">
                <v-row dense>
                    <v-col cols="6" md="9">
                        <h2 class="text-caption">Viewing shop: {{currentShopName}}</h2>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-select variant="underlined" hide-details density="compact"
                                  :items="shops" :disabled="this.$store.state.switchingShop"
                                  item-title="shop_name"
                                  item-value="shop_id"
                                  v-model="selectedShop"
                                  label="Selected Shop"
                        />
                    </v-col>
                </v-row>

            </v-container>
            <router-view/>
        </v-main>
        <v-bottom-navigation grow active v-if="$vuetify.display.mobile" density="compact" bg-color="secondary" base-color="white">
            <v-btn value="mmenu" @click.stop="drawer = !drawer" min-width="60">
                <v-icon>mdi-menu</v-icon>
                <span>Menu</span>
            </v-btn>

            <v-btn link to="/orders" value="orders" min-width="60">
                <v-icon>mdi-invoice-text-clock</v-icon>
                <span>Orders</span>
            </v-btn>

            <v-btn link to="/products" value="products" min-width="60">
                <v-icon>mdi-tag-outline</v-icon>
                <span>Products</span>
            </v-btn>
            <v-btn link to="/inventory" value="inventory" min-width="60">
                <v-icon>mdi-format-list-numbered</v-icon>
                <span>Stock</span>
            </v-btn>
            <v-btn link :to="{name:'Settings'}" value="settings" min-width="60">
                <v-icon>mdi-cog</v-icon>
                <span>Settings</span>
            </v-btn>
        </v-bottom-navigation>
    </v-app>
</template>
<script>
import axios from "axios";
export default {
    name:"App",
    data(){
        return{
            cdn:this.$store.state.cdn,
            drawer:false,
        }
    },
    computed: {
        shops() {
            return this.$store.state.shops
        },
        role() {
            return this.$store.state.role
        },
        isSuperAdmin() {
            return this.$store.state.role === 'superadmin'
        },
        currentShopName() {
            return this.$store.getters.currentShopName
        },
        selectedShop: {
            get() {
                return this.$store.state.shop;
            },
            set(val) {
                if (val !== this.$store.state.shop) {
                    this.switchShop(val)
                }
            }
        },

        isDesktop() {
            return this.$vuetify.display.mdAndUp;
        },
        orderStats() {
            return this.$store.state.orderStats;
        }
    },
    watch: {
        isDesktop(val) {
            this.drawer = val; // open by default on desktop
        }
    },
    async mounted() {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            this.$store.dispatch('fetchOrderStats');
        });
        const channel = window.Echo.channel('orders');
        channel.listen('.order.created', (e) => {
            this.$store.dispatch('fetchOrderStats');
            window.Toast.success(`New Order: ${e.order.order_number}`);
        });

        channel.listen('.order.updated', (e) => {
            this.$store.dispatch('fetchOrderStats');
        });
        this.drawer = this.isDesktop;
        await this.$store.dispatch('loadContext',true);
    },
    methods: {
        async switchShop(shopId) {
            await this.$store.dispatch('switchShop',shopId)
            this.$router.push('/analytics');
        },
        async logout(){
            await axios.post('/logout')
                .then(()=>{
                    this.$store.commit('LOGOUT');
                    window.location.href = '/login';
                })
        }
    }
}

</script>

<style>
.section-card {
    border: 1px solid #e1e3e6 !important;
    box-shadow: 0 1px 2px rgba(16,24,40,0.04) !important;
    border-radius: 8px !important;
}
.v-list-group__items {
    --indent-padding: -6px !important;
}
html{font-size: 0.875rem !important;}

.v-list-group__items .v-list-item {
    padding-inline-start: calc(5px + var(--indent-padding)) !important;
}
#ctimeline > div {
    width: 100%;
}
</style>
