<template>
    <v-container class="pa-1">
        <v-row>
            <v-col cols="12" md="6">
                <h2>Shopify Setup</h2>
            </v-col>
            <v-col cols="12" md="12" class="d-flex justify-center">
                <v-btn v-if="shopifyDetail === null" @click="showAddDialog" class="my-6"
                       variant="outlined" density="comfortable" color="success"
                >
                    <span class="iconify mr-2" data-icon="logos:shopify"/>
                    Add Shopify App
                </v-btn>
            </v-col>
        </v-row>
        <v-row v-if="shopifyDetail !== null">
            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Shopify Store Details</v-card-title>
                    <v-card-text>
                        <div v-if="!shopifyDetail?.access_token">
                            <v-list>
                                <v-list-item title="Shop Domain" :subtitle="shopifyDetail.shop_domain"></v-list-item>
                                <v-list-item title="Client ID" :subtitle="shopifyDetail.client_id"></v-list-item>
                                <v-list-item title="Client Secret" :subtitle="shopifyDetail.client_secret"></v-list-item>
                            </v-list>
                            <v-btn @click="getAccessToken" variant="elevated" color="success">Get Access Token</v-btn>
                        </div>
                        <div v-else class="d-flex flex-column ga-2">
                            <div class="text-body-1"><span class="font-weight-semibold">Shop Domain:</span> {{ shopifyDetail.shop_domain }}</div>
                            <div class="text-body-1">
                                <span class="font-weight-semibold">Scope :</span> {{shopifyDetail.access_token ?? 'not available'}}
                            </div>
                            <div class="text-body-1">
                                <span class="font-weight-semibold">Scope :</span> {{shopifyDetail.scope ?? 'not scope yet'}}
                            </div>
                            <div class="text-body-1">
                                <span class="font-weight-semibold">Token to be Expired :</span> {{dayjs(shopifyDetail.token_expires_at).format('D MMM [at] h:mm a')}}
                            </div>
                            <v-btn @click="getAccessToken" variant="elevated" color="success"
                                   size="small" max-width="200">Refresh Token</v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card class="mb-3" v-if="counts.products">
                    <v-card-title>Products</v-card-title>
                    <v-card-text>
                        <div v-if="counts.products">
                            <h2>Total: {{counts?.products?.count}}</h2>
                            <h3>Products Fetched: {{stotal}} / {{counts?.products?.count}} </h3>
                            <div class="text-body-1" v-if="counts?.products?.available">Scope: Available</div>
                            <div v-if="counts?.products?.reason">Reason: {{counts?.custom_collections?.reason}}</div>
                            <v-btn @click="startSyncPros" :loading="syncLoading" class="mt-2"
                                   variant="outlined" color="success" density="compact">
                                Import Products
                            </v-btn>
                        </div>
                    </v-card-text>
                </v-card>
                <v-card class="mb-3" v-if="counts.custom_collections">
                    <v-card-title>Collections</v-card-title>
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <h4 class="font-weight-semibold">Custom</h4>
                                <div v-if="counts.custom_collections">
                                    <h2>Total: {{counts?.custom_collections?.count}}</h2>
                                    <div class="text-body-1" v-if="counts?.custom_collections?.available">Scope: Available</div>
                                    <div v-if="counts?.custom_collections?.reason">Reason: {{counts?.custom_collections?.reason}}</div>
                                    <v-btn class="mt-2" variant="outlined" color="success" density="compact">Import Custom Collections</v-btn>
                                </div>

                            </v-col>
                            <v-col cols="12" md="6">
                                <h4 class="font-weight-semibold">Smart</h4>
                                <div v-if="counts.smart_collections">
                                    <h2>Total: {{counts?.smart_collections?.count}}</h2>
                                    <div class="text-body-1" v-if="counts?.smart_collections?.available">Scope: Available</div>
                                    <div class="text-red" v-if="counts?.smart_collections?.reason">Reason: {{counts?.smart_collections?.reason}}</div>
                                    <v-btn class="mt-2" variant="outlined" color="success" density="compact">Import Smart Collections</v-btn>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <v-card class="mb-3" v-if="counts.custom_collections">
                    <v-card-title>Blogs & News Articles</v-card-title>
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <h4 class="font-weight-semibold">Blogs</h4>
                                <div v-if="counts.blogs">
                                    <h2>Total: {{blogs?.length || 0}} / {{counts?.blogs?.count || 0}}</h2>
                                    <div class="text-body-1" v-if="counts?.blogs?.available">Scope: Available</div>
                                    <div class="text-red" v-if="counts?.blogs?.reason">Reason: {{counts?.blogs?.reason}}</div>
                                    <v-btn v-if="counts?.blogs?.available" class="mt-2" variant="outlined"
                                           color="success" density="compact">Import Blogs</v-btn>
                                </div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <h4 class="font-weight-semibold">Articles</h4>
                                <div v-if="counts.articles">
                                    <h2>Total: {{blogs_count}} / {{counts?.articles?.count || 0}}</h2>
                                    <div class="text-body-1" v-if="counts?.articles?.available">Scope: Available</div>
                                    <div class="text-red" v-if="counts?.articles?.reason">Reason: {{counts?.articles?.reason}}</div>
                                    <v-btn v-if="counts?.articles?.available"
                                           class="mt-2" variant="outlined"
                                           @click="getAndUpdateArticles" :loading="isLoading"
                                           color="primary" density="compact">Import Articles</v-btn>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <v-card class="mb-3" v-if="counts.pages">
                    <v-card-title>Pages</v-card-title>
                    <v-card-text>
                        <h2>Total: {{counts?.pages?.count || 0}}</h2>
                        <div class="text-body-1" v-if="counts?.pages?.available">Scope: Available</div>
                        <div class="text-red" v-if="counts?.pages?.reason">Reason: {{counts?.pages?.reason}}</div>
                        <v-btn v-if="counts?.pages?.available" class="mt-2" variant="outlined" color="success" density="compact">Import Pages</v-btn>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="6" md="3">
                <v-btn variant="text" density="compact" class="font-weight-bold text-none">{{selectedPros.length}} Selected</v-btn>
            </v-col>
            <v-col cols="6" md="3">
                <v-btn @click="selectedPros = []" variant="outlined" density="compact" class="text-none">Unselect All</v-btn>
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
                                <div v-if="item.options">
                                    <div v-for="(option,odx) in item.options" :key="odx">
                                        <div><span class="font-weight-medium">{{option.name}} :</span> <div v-if="option.values" class="d-flex flex-wrap ga-1">
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
        <v-dialog v-model="addDialog" max-width="450">
            <v-form v-model="addValid" @submit.prevent="addOrUpdateDetail">
                <v-card>
                    <v-card-title>Shopify Shop Details</v-card-title>
                    <v-card-text class="d-flex flex-column ga-2">
                        <div>
                            <v-text-field v-model="sdetail.shop_domain" label="Shop Domain" variant="underlined"
                                          density="comfortable" :rules="domainRule" placeholder="shop_name.myshopify.com"
                                          hint="shopname followed by myshopify.com" persistentPlaceholder persistentHint counter/>
                        </div>
                        <div>
                            <v-text-field v-model="sdetail.client_id" label="Client ID" variant="underlined"
                            density="comfortable" :rules="idRule" counter persistentPlaceholder placeholder="a....."
                            hint="a...."/>
                        </div>
                        <div>
                            <v-text-field v-model="sdetail.client_secret" label="Client Secret" variant="underlined"
                                          density="comfortable" :rules="secretRule" counter
                                          placeholder="shpss_...." persistentHint persistentPlaceholder
                                          hint="shpss_...."/>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn :disabled="!addValid" type="submit" variant="elevated" density="comfortable" color="success">Add</v-btn>
                        <v-btn @click="addDialog = false" variant="elevated" density="comfortable" color="red">Cancel</v-btn>
                    </v-card-actions>
                </v-card>
            </v-form>
        </v-dialog>
    </v-container>
</template>
<script>
import dayjs from "dayjs";
import debounce from "lodash/debounce";
import {mergeProps} from "vue";

export default {
    name: "SuperShopifysetup",
    computed: {
        dayjs() {
            return dayjs
        }
    },
    data(){
        return{
            cdn:this.$store.state.cdn,
            shopifyDetail:null,
            counts:{
                products:0,
                custom_collections:0,
                smart_collections:0,
                blogs:0,
                articles:0,
                pages:0,
            },
            syncLoading:false,
            addValid:false,
            addDialog:false,
            sdetail:{
                shop_domain:"",
                client_id:"",
                client_secret:"",
            },
            domainRule:[
                (v) => !!v || "Shop Domain is required",
                (v) => (v && v.length >= 15) || "Minimum 15 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
            ],
            idRule:[
                (v) => !!v || "Client ID is required",
                (v) => (v && v.length >= 15) || "Minimum 30 characters required",
                (v) => (v && v.length <= 60) || "Maximum 100 characters allowed",
            ],
            secretRule:[
                (v) => !!v || "Client Secret is required",
                (v) => (v && v.length >= 15) || "Minimum 30 characters required",
                (v) => (v && v.length <= 60) || "Maximum 100 characters allowed",
            ],
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
            blogs:[],
            blogs_count:0,
        }
    },
    created() {
        this.getShopDetail();
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
        getShopDetail(){
            axios.get('/superadmin/shopify/detail')
                .then((resp)=>{
                    const allData = resp.data;
                    this.shopifyDetail = allData.shopifyDetail;
                    this.counts = allData.counts;
                    this.blogs_count = allData.blogs_count || 0;
                })
        },
        showAddDialog(){
            this.addDialog =true;
        },
        addOrUpdateDetail(){
            const adata = {
                'shop_domain':this.sdetail.shop_domain,
                'client_id':this.sdetail.client_id,
                'client_secret':this.sdetail.client_secret,
            }
            axios.post('/superadmin/shopify/add',adata)
                .then((resp)=>{
                    this.getShopDetail();
                    window.Toast.success(resp.data.message ?? "updated Successfully")
                })
        },
        getAccessToken(){
            axios.get('/superadmin/shopify/fetch-token')
                .then((resp)=>{
                    this.getShopDetail();
                    window.Toast.success(resp.data.message ?? "Token Generated Successfully")
                })
        },
        startSyncPros(){
            this.syncLoading = true;
            axios.get('/superadmin/shopify/sync-products')
                .then((resp)=>{
                    console.log('Sync Respo',resp);
                    this.loadItems();
                    window.Toast.success(`product Synced Successfully`)
                })
                .catch((err)=>{
                    console.log("Sync Errors",err)
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
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
            }
            catch (e) {
                console.error("Failed to load products", e);
            } finally {
                this.isLoading = false;
            }
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
        getAndUpdateArticles(){
            this.isLoading = true
            axios.get('/superadmin/shopify/import-and-save-articles')
                .then((resp)=>{
                    this.blogs = resp.data.blogs || [];
                    window.Toast.success('Articles imported Successfully')
                })
                .catch((err)=>{
                    console.log('error',err);
                    window.Toast.error(`Something Err ${err.message}`)
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        }
    }
}
</script>

<style scoped>

</style>
