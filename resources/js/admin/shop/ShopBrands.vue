<template>
    <v-container class="pa-1">
        <v-tabs v-model="htab" density="compact" color="primary" selectedClass="bg-lblue"
                bgColor="grey-lighten-3" sliderColor="primary"
                class="my-2">
            <v-tab value="general" class="bg-white">Brands Page Setting</v-tab>
            <v-tab value="tpromo" class="bg-white">Top Promo</v-tab>
            <v-tab value="bpromo" class="bg-white">Bottom Promo</v-tab>
        </v-tabs>
        <v-window v-model="htab">
            <v-window-item value="general">
                <PageSettings
                    page_type="brands"
                    slug="/brands"
                />
            </v-window-item>
            <v-window-item value="tpromo">
                <PromoSection page_type="brands" position="top"/>
            </v-window-item>
            <v-window-item value="bpromo">
                <PromoSection page_type="brands" position="bottom"/>
            </v-window-item>
        </v-window>
    </v-container>
</template>
<script>
import PromoSection from "@/admin/shop/PromoSection.vue";
import PageSettings from "@/admin/shop/PageSettings.vue";

export default {
    name: "ShopBrands",
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
