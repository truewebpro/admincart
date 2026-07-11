<template>
    <v-container class="pa-1">
        <v-tabs v-model="htab" density="compact" color="primary" selectedClass="bg-grey-lighten-3"
                bgColor="white" sliderColor="red"
                class="my-2">
            <v-tab value="general">Blogs Page Setting</v-tab>
            <v-tab value="tpromo">Top Promo</v-tab>
            <v-tab value="bpromo">Bottom Promo</v-tab>
        </v-tabs>
        <v-window v-model="htab">
            <v-window-item value="general">
                <PageSettings
                    page_type="blogs"
                    slug="/blogs"
                />
            </v-window-item>
            <v-window-item value="tpromo">
                <PromoSection page_type="blogs" position="top"/>
            </v-window-item>
            <v-window-item value="bpromo">
                <PromoSection page_type="blogs" position="bottom"/>
            </v-window-item>
        </v-window>
    </v-container>
</template>
<script>
import PromoSection from "@/admin/shop/PromoSection.vue";
import PageSettings from "@/admin/shop/PageSettings.vue";

export default {
    name: "ShopBlogs",
    components: {PageSettings, PromoSection},
    data(){
        return{
            htab:'general'
        }
    }
}
</script>

<style scoped>

</style>
