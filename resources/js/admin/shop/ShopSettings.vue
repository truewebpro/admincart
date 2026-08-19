<template>
    <div>
        <v-tabs density="comfortable" color="primary" selectedClass="bg-lblue"
                bgColor="grey-lighten-3" sliderColor="primary"
                class="my-2" slider-transition="fade" spaced="both">
            <v-tab link :to="{name:'MainHomePage'}" class="bg-white">Home Page</v-tab>
            <v-tab link :to="{name:'ShopHome'}" class="bg-white">Theme Settings</v-tab>
            <v-tab link :to="{name:'ShopFooter'}" class="bg-white">Footer</v-tab>
            <v-tab link :to="{name:'ShopSearch'}" class="bg-white">Search</v-tab>
            <v-tab link :to="{name:'ShopAnnouncement'}" class="bg-white">Announcement</v-tab>
            <v-tab link :to="{name:'MainCartPage'}" class="bg-white">Cart Page</v-tab>
            <v-tab link :to="{name:'ShopSubscribe'}" class="bg-white">Subscribe</v-tab>
            <v-tab link :to="{name:'ShopProducts'}" class="bg-white">Products</v-tab>
            <v-tab link :to="{name:'ShopCats'}" class="bg-white">Collections</v-tab>
            <v-tab link :to="{name:'ShopBrands'}" class="bg-white">Brands</v-tab>
            <v-tab link :to="{name:'ShopBlogs'}" class="bg-white">Blogs</v-tab>
            <v-tab link :to="{name:'ShopPages'}" class="bg-white">Pages</v-tab>
            <v-tab link :to="{name:'ShopPolicies'}" class="bg-white">Policies</v-tab>
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
