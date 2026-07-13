<template>
    <div>
        <v-tabs bg-color="grey-lighten-3" selected-class="bg-primary" density="compact">
            <v-tab link :to="{name:'MainHomePage'}">Home Page</v-tab>
            <v-tab link :to="{name:'ShopHome'}">Theme Settings</v-tab>
            <v-tab link :to="{name:'ShopFooter'}">Footer</v-tab>
            <v-tab link :to="{name:'ShopSearch'}">Search</v-tab>
            <v-tab link :to="{name:'ShopAnnouncement'}">Announcement</v-tab>
            <v-tab link :to="{name:'MainCartPage'}">Cart Page</v-tab>
            <v-tab link :to="{name:'ShopSubscribe'}">Subscribe</v-tab>
            <v-tab link :to="{name:'ShopProducts'}">Products</v-tab>
            <v-tab link :to="{name:'ShopCats'}">Collections</v-tab>
            <v-tab link :to="{name:'ShopBrands'}">Brands</v-tab>
            <v-tab link :to="{name:'ShopBlogs'}">Blogs</v-tab>
            <v-tab link :to="{name:'ShopPages'}">Pages</v-tab>
            <v-tab link :to="{name:'ShopPolicies'}">Policies</v-tab>
<!--            <v-tab link :to="{name:'ShopBanner'}">Banner</v-tab>-->
<!--            <v-tab link :to="{name:'FeaturedGrid'}">Featured Grid</v-tab>-->
<!--            <v-tab link :to="{name:'FeaturedCats'}">Featured Cats</v-tab>-->
<!--            <v-tab link :to="{name:'FeaturedBrands'}">Featured Brands</v-tab>-->
<!--            <v-tab link :to="{name:'ShopbyCats'}">Shop By Cats</v-tab>-->
<!--            <v-tab link :to="{name:'ProductGrids'}">Top Sellers</v-tab>-->
        </v-tabs>
        <router-view/>
    </div>
</template>
<script>
export default {
    name:"ShopSettings",
}
</script>

<style scoped>

</style>
