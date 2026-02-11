<template>
    <v-app>
        <v-navigation-drawer permanent ral expad-on-hover>
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
                        <v-list-item v-bind="props" prepend-icon="mdi-invoice-text-clock-outline">
                            <div class="d-flex ga-3 align-center">
                                <span>All Orders</span> <v-chip size="small" color="primary" class="bg-light-subtle font-weight-bold">22</v-chip>
                            </div>
                        </v-list-item>
                    </template>
                    <v-list-item link to="/orders" color="primary" prepend-icon="mdi-invoice-text-clock" title="Orders"></v-list-item>
                    <v-list-item link to="/carts" color="primary" prepend-icon="mdi-cart-check" title="Drafts"></v-list-item>
                </v-list-group>
                <v-list-item link to="/products" color="primary" prepend-icon="mdi-tag-outline" title="Products"></v-list-item>
                <v-list-item link to="/inventory" color="primary" prepend-icon="mdi-format-list-numbered"
                             title="Inventory" subtitle="All Products">
                </v-list-item>
                <v-list-group value="settings">
                    <template v-slot:activator="{ props }">
                        <v-list-item v-bind="props" prepend-icon="mdi-tag-multiple-outline" title="Product Settings" />
                    </template>
                    <v-list-item to="/categories" color="primary" prepend-icon="mdi-format-list-text" title="Categories"/>
                    <v-list-item to="/settings/ptypes" color="primary" title="Types" prepend-icon="mdi-list-status" link/>
                    <v-list-item to="/settings/brands" color="primary" title="Brands" prepend-icon="mdi-shield-star" link/>
                    <v-list-item to="/settings/tags" color="primary" title="Tags"  prepend-icon="mdi-tag-multiple" link/>
                    <v-list-item to="/settings/poptions" color="primary" title="Options" prepend-icon="mdi-filter-variant" link/>
                    <v-list-item to="/settings/features" color="primary" title="Features" prepend-icon="mdi-feature-search-outline" link/>
                </v-list-group>
                <v-spacer/>
                <v-list-item link :to="{name:'AdminOrders'}" color="primary" prepend-icon="mdi-cog" title="Settings">
                </v-list-item>
                <v-divider class="my-1"></v-divider>
                <v-list-item prepend-icon="mdi-logout">
                    <v-list-item-title>
                        <v-btn variant="outlined" size="small" block title="Logout">Logout</v-btn>
                    </v-list-item-title>
                </v-list-item>
            </v-list>
        </v-navigation-drawer>
        <v-main class="py-4 bg-light-subtle">
            <router-view/>
        </v-main>
    </v-app>
</template>
<script>
export default {
    name:"App",
    data(){
        return{
            cdn:this.$store.state.cdn
        }
    }
}

</script>

<style scoped>
:root{
    --font-sans:"Roboto, sans-serif"
}
html{font-size: 0.875rem !important;}

.v-list-group__items .v-list-item {
    padding-inline-start: calc(5px + var(--indent-padding)) !important;
}
.v-list-group {
    --list-indent-size: -30px;
}

</style>
