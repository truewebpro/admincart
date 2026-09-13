<template>
    <v-row dense>
        <v-col cols="12">
            <v-card class="mb-3" v-if="spros?.length">
                <v-card-text>
                    <h2>Total: {{spros?.length || 0}}</h2>
                    <h3>Products Created: {{products_created || 0}} / {{stotal || 0}}</h3>
                    <v-btn @click="syncProductsSeo" :loading="syncLoading" class="mt-2 me-2"
                           variant="tonal" color="success" density="compact" prependIcon="mdi-sync">
                        Sync Products SEO
                    </v-btn>
                    <v-btn v-if="spros?.length" class="mt-2 me-2" variant="tonal" color="success"
                           density="compact" :loading="syncLoading" @click="backfillThirdpartyVariantIds"
                           prependIcon="mdi-sync">Sync Old Product Variants</v-btn>
                    <v-btn v-if="spros?.length" class="mt-2" variant="tonal" color="success"
                           density="compact" :loading="syncLoading" @click="syncVariantCostPrice"
                           prependIcon="mdi-sync">Sync Variants Cost Price</v-btn>
                </v-card-text>
            </v-card>
        </v-col>
        <v-col cols="12">
            <v-card>
                <v-card-title>Fetched Products</v-card-title>
                <v-text-field v-model="product_search" class="ma-2" clearable density="compact" variant="outlined"
                              hide-details appendInnerIcon="mdi-magnify" placeholder="Search Products..."></v-text-field>
                <v-row dense v-if="selectedPros.length > 0" class="mb-2 px-2">
                    <v-col cols="6" md="3">
                        <v-btn variant="text" density="compact" class="font-weight-bold text-none">{{selectedPros.length}} Selected</v-btn>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-btn @click="selectedPros = []" variant="outlined" density="compact" class="text-none">Unselect All</v-btn>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-menu>
                            <template v-slot:activator="{ props: menu }">
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props: tooltip }">
                                        <v-btn variant="outlined" class="text-none me-5" v-bind="mergeProps(menu, tooltip)"
                                               density="compact" append-icon="mdi-chevron-down">
                                            More Actions
                                        </v-btn>
                                    </template>
                                    <span>More Actions</span>
                                </v-tooltip>
                            </template>
                            <!--                                <v-list nav density="compact">-->
                            <!--                                    <v-list-item @click="archiveProducts">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-archive-outline</v-icon>Archive Products</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-list-item base-color="error" @click="deleteProducts">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Products</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-divider class="mt-1"/>-->
                            <!--                                    <v-list-item @click="addBulkTagDialog = true">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-tag-plus-outline</v-icon>Add Tags</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-list-item base-color="error" @click="removeBulkTagDialog = true">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-tag-remove-outline</v-icon>Remove Tags</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-divider class="mt-1"/>-->
                            <!--                                    <v-list-item base-color="success" @click="exportSelectedProducts">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-export</v-icon>Export Products</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                </v-list>-->
                        </v-menu>
                    </v-col>
                </v-row>
                <v-data-table-server
                    v-model:page="page"
                    v-model:items-per-page="itemsPerPage"
                    :items="spros"
                    :headers="sprosHeader"
                    :items-length="totalItems"
                    :items-per-page="itemsPerPage"
                    v-model="selectedPros"
                    hover show-select
                    return-object
                    :loading="isLoading"
                    @update:options="loadItems"
                    density="comfortable"
                    mobileBreakpoint="sm"
                >
                    <template #item.image_one="{item}">
                            <span>
                                <v-img v-if="item.image_one" :src="item.image_one" max-width="60" max-height="60"/>
                                <v-img v-else :src="cdn+'noimage.png'" max-width="60" max-height="60"/>
                            </span>
                    </template>
                    <template #item.title="{item}">
                        <div class="py-1">
                            <div>{{item.title}}</div>
                            <div v-if="Array.isArray(item.options) && item.options.length">
                                <div v-for="(option,odx) in item.options" :key="odx">
                                    <div><span class="font-weight-medium">{{option.name}} :</span> <div v-if="Array.isArray(option.values)" class="d-flex flex-wrap ga-1">
                                        <v-btn v-for="(val,vdx) in option.values" :key="vdx"
                                               size="small" density="compact" variant="tonal" color="success">
                                            {{val}}
                                        </v-btn>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #item.actions="{item}">
                        <v-btn v-if="!item.product_id" @click="createProductInSystem(item)" size="small"
                               color="success" variant="outlined" density="comfortable">
                            Create
                        </v-btn>
                        <v-btn v-else size="small"
                               color="success" variant="tonal" density="comfortable">
                            Done
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card>
        </v-col>
    </v-row>
</template>
<script>
import {mergeProps} from "vue";
import debounce from "lodash/debounce";
import dayjs from "dayjs";
import axios from "axios";

export default {
    name: "ShopifyProducts",
    data(){
        return{
            shop_id:this.$store.state.shop_id,
            syncLoading:false,
            products_created:0,
            product_search:"",
            stotal:0,
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by: '',
            sort_order: 'desc',
            only_not_imported: true,
            sortBy: [],
            selectedPros:[],
            selectedProduct:{},
            spros:[],
            isLoading: false,
            sprosHeader:[
                {title:"Image",key:'image_one'},
                {title:"title",key:'title'},
                {title:"Brand",key:'vendor'},
                {title:"Type",key:'product_type'},
                {title:"Tags",key:'tags'},
                {title:"ProductId",key:'product_id'},
                {title:"Actions",key:'actions'},
            ],
        }
    },
    computed: {
        dayjs() {
            return dayjs
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getSyncedProducts();
        }, 400);
    },
    watch:{
        product_search() {
            this.debouncedSearch();
        },
    },
    methods:{
        mergeProps,
        loadItems(options) {
            this.page = options.page;
            this.itemsPerPage = options.itemsPerPage;
            if (options.sortBy.length > 0) {
                this.sort_by = options.sortBy[0].key;
                this.sort_order = options.sortBy[0].order;
            } else {
                this.sort_by = 'id';
                this.sort_order = 'desc';
            }
            this.getSyncedProducts();
        },
        async getSyncedProducts(){
            this.isLoading = true;
            try {
                const resp = await axios.get('/superadmin/shopify/all-products',{
                    params:{
                        page:this.page,
                        search:this.product_search,
                        per_page: this.itemsPerPage,
                        sort_by:this.sort_by,
                        sort_order:this.sort_order,
                        only_not_imported:this.only_not_imported,
                    }
                })
                const respData = resp.data;
                const allData = respData.items;
                this.spros = allData.data;
                this.totalItems = allData.total;
                this.page = allData.current_page;
                this.stotal = respData.stotal;
                this.products_created = respData.products_created;
            }
            catch (e) {
                console.error("Failed to load products", e);
            } finally {
                this.isLoading = false;
            }
        },
        syncProductsSeo(){
            this.syncLoading = true;
            axios.get('/superadmin/shopify/sync-products-seo/'+this.shop_id)
                .then((resp)=>{
                    const respData = resp.data;
                    if(resp.data.success){
                        window.Toast.success(`Success ${respData.message}`)
                    }
                    return this.getSyncedProducts();
                })
                .catch((err)=>{
                    console.log("Sync Errors",err)
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
        async backfillThirdpartyVariantIds(){
            this.syncLoading = true;
            await axios.post(`/superadmin/shopify/${this.shop_id}/variants/backfill-thirdparty-ids`)
                .then((resp)=>{
                    const respData = resp.data;
                    if(resp.data.success){
                        window.Toast.success(`Success ${respData.message}`)
                    }
                    return this.getSyncedProducts(); // refresh so newly-backfilled rows show as "Saved" immediately
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
        async syncVariantCostPrice(){
            this.syncLoading = true;
            await axios.post(`/superadmin/shopify/${this.shop_id}/variants/sync-cost-price`)
                .then((resp)=>{
                    const respData = resp.data;
                    if(resp.data.success){
                        window.Toast.success(`Success ${respData.message}`)
                    }
                    return this.getSyncedProducts(); // refresh so newly-backfilled rows show as "Saved" immediately
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
        createProductInSystem(item){
            this.isLoading = true;
            axios.post('/superadmin/shopify/create-single-product',{
                id:item.id
            }).then((resp)=>{
                console.log("respIn",resp.data);
                this.getSyncedProducts();
            })
                .catch((err)=>{
                    console.log(err)
                })
                .finally(()=>{
                    this.isLoading = false
                })
        },

    }
}
</script>
