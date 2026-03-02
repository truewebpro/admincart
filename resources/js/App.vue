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
                        <v-list-item v-bind="props" prepend-icon="mdi-invoice-text-clock-outline" title="All Orders">
                            <div class="d-flex ga-3 align-center">
                               <v-chip size="small" color="primary" class="bg-light-subtle font-weight-bold">22</v-chip>
                            </div>
                        </v-list-item>
                    </template>
                    <v-list-item link :to="{name:'AdminOrders'}" color="primary" prepend-icon="mdi-invoice-text-clock" title="Orders"></v-list-item>
                    <v-list-item link :to="{name:'AdminCarts'}" color="primary" prepend-icon="mdi-cart-check" title="Drafts"></v-list-item>
                </v-list-group>
                <v-list-item link :to="{name:'products'}" color="primary" prepend-icon="mdi-tag-outline" title="Products"></v-list-item>
                <v-list-item link :to="{name:'InventoryList'}" color="primary" prepend-icon="mdi-format-list-numbered"
                             title="Inventory" subtitle="All Products">
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
                <v-list-item link :to="{name:'customers'}" color="primary" prepend-icon="mdi-account" title="Customers">
                </v-list-item>
                <v-list-item link :to="{name:'ShopHome'}" color="primary" prepend-icon="mdi-store-cog" title="Theme Settings">
                </v-list-item>
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
                <v-spacer/>
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
        }
    },
    watch: {
        isDesktop(val) {
            this.drawer = val; // open by default on desktop
        }
    },
    mounted() {
        this.drawer = this.isDesktop;
    },
    methods: {
        async logout() {
            try {
                // Call backend logout if using Laravel Sanctum/JWT
                await fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

            } catch (e) {
                console.log('Logout request failed, continuing...');
            }

            // Clear store/localStorage
            localStorage.removeItem('token');
            this.$store.commit('SET_USER', null);

            // Redirect
            window.location.href = '/login';
        }
    }
}

</script>

<style>
.v-list-group__items {
    --indent-padding: -4px !important;
}
html{font-size: 0.875rem !important;}

.v-list-group__items .v-list-item {
    padding-inline-start: calc(5px + var(--indent-padding)) !important;
}


</style>
