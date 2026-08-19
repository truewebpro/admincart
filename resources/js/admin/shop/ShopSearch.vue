<template>
    <v-container class="pa-1">
        <v-tabs v-model="htab" density="compact" color="primary" selectedClass="bg-lblue"
                bgColor="grey-lighten-3" sliderColor="primary"
                class="my-2">
            <v-tab value="searchtags" class="bg-white">Search Tags</v-tab>
            <v-tab value="searchbrands" class="bg-white">Search Brands</v-tab>
            <v-tab value="searchcats" class="bg-white">Search Cats</v-tab>
        </v-tabs>
        <v-window v-model="htab">
            <v-window-item value="searchtags">
                <ShopSearchTags/>
            </v-window-item>
            <v-window-item value="searchbrands">
                <ShopSearchBrands/>
            </v-window-item>
            <v-window-item value="searchcats">
                <ShopSearchCats/>
            </v-window-item>
        </v-window>
    </v-container>

</template>
<script>
import ShopSearchTags from "@/admin/shop/ShopSearchTags.vue";
import ShopSearchBrands from "@/admin/shop/ShopSearchBrands.vue";
import ShopSearchCats from "@/admin/shop/ShopSearchCats.vue";

export default {
    name:"ShopSearch",
    components: {ShopSearchCats, ShopSearchBrands, ShopSearchTags},
    data(){
        return{
            htab:'searchtags',
        }
    },

}

</script>

<style scoped>

</style>
