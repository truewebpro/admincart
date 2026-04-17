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
                <v-list-item base-color="dark" :prepend-avatar="cdn+'trpng.png'">
                    <v-list-item-title>Truewebcart</v-list-item-title>
                </v-list-item>
                <v-divider></v-divider>
                <v-list-item link to="/dashboard" prepend-icon="mdi-view-dashboard-outline">
                    <v-list-item-title>Dashboard</v-list-item-title>
                </v-list-item>
                <v-list-group value="acarts">
                    <template v-slot:activator="{ props }">
                        <v-list-item v-bind="props" prepend-icon="mdi-cart-check">
                            <template #title>
                                All Orders
                                <v-chip size="small" color="primary" class="bg-light-subtle font-weight-bold">
                                   {{orderStats.unfulfilled}}
                                </v-chip>
                            </template>
                        </v-list-item>
                    </template>
                    <v-list-item link :to="{name:'AdminOrders'}" color="primary" prepend-icon="mdi-cart-check" title="Orders"></v-list-item>
                    <v-list-item link :to="{name:'AbandonedCarts'}" color="primary" prepend-icon="mdi-cart-remove" title="Abandoned Carts"></v-list-item>
                </v-list-group>
                <v-list-item link :to="{name:'products'}" color="primary" prepend-icon="mdi-tag-outline" title="Products"></v-list-item>
                <v-list-item link :to="{name:'InventoryList'}" color="primary" prepend-icon="mdi-format-list-numbered"
                             title="Inventory" subtitle="All Products">
                </v-list-item>
                <v-list-item link :to="{name:'customers'}" color="primary" prepend-icon="mdi-account" title="Customers">
                </v-list-item>
                <v-list-group value="pros">
                    <template v-slot:activator="{ props }">
                        <v-list-item v-bind="props" prepend-icon="mdi-tag-multiple-outline" title="Product Settings" />
                    </template>
                    <v-list-item :to="{name:'cats'}" color="primary" prepend-icon="mdi-format-list-text" title="Categories"/>
                    <v-list-item :to="{name:'ptypes'}" color="primary" title="Types" prepend-icon="mdi-list-status" link/>
                    <v-list-item :to="{name:'brands'}" color="primary" title="Brands" prepend-icon="mdi-shield-star" link/>
                    <v-list-item :to="{name:'tags'}" color="primary" title="Tags"  prepend-icon="mdi-tag-multiple" link/>
                    <v-list-item :to="{name:'poptions'}" color="primary" title="Options" prepend-icon="mdi-filter-variant" link/>
                    <v-list-item :to="{name:'Features'}" color="primary" title="Features" prepend-icon="mdi-feature-search-outline" link/>
                </v-list-group>
                <v-list-group value="themes">
                    <template v-slot:activator="{props}">
                        <v-list-item v-bind="props" prepend-icon="mdi-store-settings" title="Shop Settings"></v-list-item>
                    </template>
                    <v-list-item link :to="{name:'ThemeView'}" color="primary" title="Theme" prepend-icon="mdi-tablet-cellphone"></v-list-item>
                    <v-list-item link :to="{name:'Preferences'}" color="primary" title="Preferences" prepend-icon="mdi-web-plus"></v-list-item>
                    <v-list-item link :to="{name:'MenusList'}" color="primary" title="Menus" prepend-icon="mdi-menu"></v-list-item>
                    <v-list-item link :to="{name:'PagesList'}" title="Pages" color="primary" prepend-icon="mdi-page-next-outline"></v-list-item>
                    <v-list-item link :to="{name:'BlogsList'}" title="Blogs" color="primary" prepend-icon="mdi-post-outline"></v-list-item>
                    <v-list-item link :to="{name:'PoliciesList'}" title="Policies" color="primary" prepend-icon="mdi-file-sign"></v-list-item>
                </v-list-group>
                <v-list-item link :to="{name:'ShopHome'}" color="primary" prepend-icon="mdi-store-cog" title="Theme Settings">
                </v-list-item>
                <v-spacer/>
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
    mounted() {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('🔄 Reconnected');
            this.$store.dispatch('fetchOrders', true);
        });
        const channel = window.Echo.channel('orders');

        channel.listen('.order.created', (e) => {
            this.$store.commit('ADD_ORDER', e.order);
            window.Toast.success(`New Order: ${e.order.order_number}`);
        });

        channel.listen('.order.updated', (e) => {
            this.$store.commit('UPDATE_ORDER', e.order);
        });
        this.drawer = this.isDesktop;
        this.$store.dispatch('fetchBrands');
        this.$store.dispatch('fetchTags');
        this.$store.dispatch('fetchPtypes');
        this.$store.dispatch('fetchPoptions');
        this.$store.dispatch('fetchCats');
        this.$store.dispatch('fetchInstocks');
        this.$store.dispatch('fetchProducts');
        this.$store.dispatch('fetchOrders');
    },
    methods: {
        async logout(){
            await axios.post('/logout')
                .then(()=>{
                    this.$store.commit('LOGOUT');
                    window.location.href = '/login';
                })
        },
        // async boostfetchShopResources(){
        //     const [brands,productTypes,tags,poptions,cats,orders] = await Promise.all([
        //         axios.get('/sadmin/brands'),
        //         axios.get('/sadmin/ptypes'),
        //         axios.get('/sadmin/tags'),
        //         axios.get('/sadmin/poptions'),
        //         axios.get('/sadmin/categories'),
        //         axios.get('/sadmin/orders'),
        //     ])
        //     this.$store.commit('SET_BRANDS', brands.data.brands)
        //     this.$store.commit('SET_PRODUCT_TYPES', productTypes.data.ptypes)
        //     this.$store.commit('SET_TAGS', tags.data.tags)
        //     this.$store.commit('SET_POPTIONS', poptions.data.poptions)
        //     this.$store.commit('SET_CATS',cats.data.cats)
        //     this.$store.commit('SET_ORDERS',orders.data.orders)
        // },
    }
}

</script>

<style>
.v-list-group__items {
    --indent-padding: -6px !important;
}
html{font-size: 0.875rem;}

.v-list-group__items .v-list-item {
    padding-inline-start: calc(5px + var(--indent-padding)) !important;
}
#ctimeline > div {
    width: 100%;
}
</style>
