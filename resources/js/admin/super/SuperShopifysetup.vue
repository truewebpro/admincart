<template>
    <v-container class="pa-1">
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-item>
                        <template #title>
                            <div class="text-h5 font-weight-bold">Shopify Setup</div>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
        </v-row>
        <v-tabs v-model="stab" color="primary" selectedClass="bg-lblue"
                density="compact" bgColor="grey-lighten-3" sliderColor="primary" class="my-2">
            <v-tab value="setup" class="bg-white">Setup</v-tab>
            <v-tab value="products" class="bg-white">
                <div class="d-flex align-center ga-1">
                    Products
                    <div v-if="counts.products">
                        <v-chip variant="tonal" density="compact" color="success">{{counts?.products?.count}}</v-chip>
                    </div>
                </div>
            </v-tab>
            <v-tab value="collections" class="bg-white">
                <div class="d-flex align-center ga-1">
                    Collections
                    <div v-if="counts.smart_collections">
                        <v-chip variant="tonal" density="compact" color="success">{{counts?.smart_collections?.count}}</v-chip>
                    </div>
                </div>
            </v-tab>
            <v-tab value="customers" class="bg-white">
                <div class="d-flex align-center ga-1">
                    Customers
                    <div v-if="counts.customers">
                        <v-chip variant="tonal" density="compact" color="success">{{counts?.customers?.count}}</v-chip>
                    </div>
                </div>
            </v-tab>
            <v-tab value="blogs" class="bg-white">
                <div class="d-flex align-center ga-1">
                    Blogs
                    <div v-if="counts.articles">
                        <v-chip variant="tonal" density="compact" color="success">{{counts?.articles?.count}}</v-chip>
                    </div>
                </div>
            </v-tab>
            <v-tab value="pages" class="bg-white">
                <div class="d-flex align-center ga-1">
                    Pages
                    <div v-if="pages_count">
                        <v-chip variant="tonal" density="compact" color="success">{{pages_count}}</v-chip>
                    </div>
                </div>
            </v-tab>
            <v-tab value="orders" class="bg-white">
                <div class="d-flex align-center ga-1">
                    Orders
                    <div v-if="counts?.orders">
                        <v-chip variant="tonal" density="compact" color="success">{{counts?.orders?.count}}</v-chip>
                    </div>
                </div>
            </v-tab>
        </v-tabs>
        <v-window v-model="stab">
            <v-window-item value="setup">
                <v-row>
                    <v-col cols="12" md="12" v-if="loading">
                        <v-skeleton-loader type="card"></v-skeleton-loader>
                    </v-col>
                    <v-col cols="12" md="12" v-if="shopifyDetail === null">
                        <v-empty-state
                            title="Shopify Setup"
                            text="Add Shopify app details to impot Products, Collections, Blogs etc"
                        >
                            <template #media>
                                <span class="iconify text-h1" data-icon="logos:shopify"/>
                            </template>
                            <template #actions>
                                <v-btn @click="showAddDialog"
                                       variant="tonal" prependIcon="mdi-plus" density="comfortable" color="success"
                                >
                                    Add Shopify App
                                </v-btn>
                            </template>
                        </v-empty-state>

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
                                    <v-btn @click="getAccessToken" variant="tonal" color="success" prependIcon="mdi-refresh"
                                           max-width="200">Refresh Token</v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="6">

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
            </v-window-item>
            <v-window-item value="products">
                <v-card class="mb-3" v-if="counts.products">
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <div v-if="counts.products">
                                    <h2>Total: {{counts?.products?.count}}</h2>
                                    <h3>Products Fetched: {{stotal}} / {{counts?.products?.count}} </h3>
                                    <div class="text-body-1" v-if="counts?.products?.available">Scope: Available</div>
                                    <div v-if="counts?.products?.reason">Reason: {{counts?.products?.reason}}</div>
                                    <v-btn @click="startSyncPros" :loading="syncLoading" class="mt-2 me-2"
                                           variant="tonal" color="success" density="compact" prependIcon="mdi-download">
                                        Import Products
                                    </v-btn>
                                    <v-btn @click="syncProductsSeo" :loading="syncLoading" class="mt-2"
                                           variant="tonal" color="green" density="compact" prependIcon="mdi-sync">
                                        Sync Products SEO
                                    </v-btn>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <ShopifyProducts/>
            </v-window-item>
            <v-window-item value="collections">
                <v-card class="mb-3" v-if="counts.custom_collections">
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <h4 class="font-weight-semibold">Custom</h4>
                                <div v-if="counts.custom_collections">
                                    <h2>Total: {{ccats_count}} / {{counts?.custom_collections?.count}}</h2>
                                    <div class="text-body-1" v-if="counts?.custom_collections?.available">Scope: Available</div>
                                    <div v-if="counts?.custom_collections?.reason">Reason: {{counts?.custom_collections?.reason}}</div>
                                    <v-btn @click="getAndUpdateCustomCollections" :loading="isLoading"
                                           class="mt-2" variant="tonal" color="success" density="compact" prependIcon="mdi-download">
                                        Import Custom Collections
                                    </v-btn>
                                </div>

                            </v-col>
                            <v-col cols="12" md="6">
                                <h4 class="font-weight-semibold">Smart</h4>
                                <div v-if="counts.smart_collections">
                                    <h2>Total: {{scats_count}} / {{counts?.smart_collections?.count}}</h2>
                                    <div class="text-body-1" v-if="counts?.smart_collections?.available">Scope: Available</div>
                                    <div class="text-red" v-if="counts?.smart_collections?.reason">Reason: {{counts?.smart_collections?.reason}}</div>
                                    <v-btn @click="getAndUpdateSmartCollections" :loading="isLoading"
                                           class="mt-2" variant="tonal" color="success" density="compact" prependIcon="mdi-download">
                                        Import Smart Collections
                                    </v-btn>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-window-item>
            <v-window-item value="customers">
                <v-card class="mb-3" v-if="counts.customers">
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <div v-if="counts.customers">
                                    <h2>Total: {{counts?.customers?.count}}</h2>
                                    <h3>Customers Fetched: {{scusts_count}} / {{counts?.customers?.count}} </h3>
                                    <div class="text-body-1" v-if="counts?.customers?.available">Scope: Available</div>
                                    <div v-if="counts?.customers?.reason">Reason: {{counts?.customers?.reason}}</div>
                                    <v-btn @click="startSyncCustomers" :loading="syncLoading" class="mt-2 me-2"
                                           variant="tonal" color="success" density="compact" prependIcon="mdi-download">
                                        Import Customers
                                    </v-btn>
<!--                                    <v-btn :loading="syncLoading" class="mt-2"-->
<!--                                           variant="tonal" color="green" density="compact" prependIcon="mdi-progress-download">-->
<!--                                        Import Customers in Bulk-->
<!--                                    </v-btn>-->
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <ShopifyCustomers/>
            </v-window-item>
            <v-window-item value="blogs">
<!--                <v-card class="mb-3" v-if="counts.blogs">-->
<!--                    <v-card-text>-->
<!--                        <v-row dense>-->
<!--                            <v-col cols="12" md="6">-->
<!--                                <h4 class="font-weight-semibold">Blogs Slug</h4>-->
<!--                                <div v-if="counts.blogs">-->
<!--                                    <h2>Total: {{blogs?.length || 0}} / {{counts?.blogs?.count || 0}}</h2>-->
<!--                                    <div class="text-body-1" v-if="counts?.blogs?.available">Scope: Available</div>-->
<!--                                    <div class="text-red" v-if="counts?.blogs?.reason">Reason: {{counts?.blogs?.reason}}</div>-->
<!--&lt;!&ndash;                                    <v-btn v-if="counts?.blogs?.available" class="mt-2" variant="tonal"&ndash;&gt;-->
<!--&lt;!&ndash;                                           color="success" density="compact" prependIcon="mdi-download">Import Blogs</v-btn>&ndash;&gt;-->
<!--                                </div>-->
<!--                            </v-col>-->
<!--                            <v-col cols="12" md="6">-->
<!--                                <h4 class="font-weight-semibold">Articles</h4>-->
<!--                                <div v-if="counts.articles">-->
<!--                                    <h2>Total: {{blogs_count}} / {{counts?.articles?.count || 0}}</h2>-->
<!--                                    <div class="text-body-1" v-if="counts?.articles?.available">Scope: Available</div>-->
<!--                                    <div class="text-red" v-if="counts?.articles?.reason">Reason: {{counts?.articles?.reason}}</div>-->
<!--                                    <v-btn v-if="counts?.articles?.available"-->
<!--                                           class="mt-2" variant="tonal"-->
<!--                                           @click="getAndUpdateArticles" :loading="isLoading"-->
<!--                                           color="primary" density="compact" prependIcon="mdi-download">Import Articles</v-btn>-->
<!--                                </div>-->
<!--                            </v-col>-->
<!--                        </v-row>-->
<!--                    </v-card-text>-->
<!--                </v-card>-->
                <ShopifyBlogs :shopifyDomain="shopifyDetail?.shop_domain"/>
            </v-window-item>
            <v-window-item value="pages">
                <ShopifyPages :shopifyDomain="shopifyDetail?.shop_domain"/>
            </v-window-item>
            <v-window-item value="orders">
                <v-card class="mb-3" v-if="counts.orders">
                    <v-card-text>
                        <h2>Total: {{counts?.orders?.count || 0}}</h2>
                        <h3>Orders Saved: {{sorders_count}} / {{counts?.orders?.count}} </h3>
                        <div class="text-body-1" v-if="counts?.orders?.available">Scope: Available</div>
                        <div class="text-red" v-if="counts?.orders?.reason">Reason: {{counts?.orders?.reason}}</div>
<!--                        <v-btn v-if="counts?.orders?.available" class="mt-2" variant="tonal" color="success"-->
<!--                               density="compact" prependIcon="mdi-download">Import Orders</v-btn>-->
                    </v-card-text>
                </v-card>
                <ShopifyOrders/>
            </v-window-item>
        </v-window>
    </v-container>
</template>
<script>
import dayjs from "dayjs";
import ShopifyProducts from "./shopify/ShopifyProducts.vue";
import ShopifyCustomers from "./shopify/ShopifyCustomers.vue";
import ShopifyOrders from "./shopify/ShopifyOrders.vue";
import ShopifyPages from "./shopify/ShopifyPages.vue";
import ShopifyBlogs from "./shopify/ShopifyBlogs.vue";

export default {
    name: "SuperShopifysetup",
    components: {ShopifyBlogs, ShopifyPages, ShopifyOrders, ShopifyCustomers, ShopifyProducts},
    computed: {
        dayjs() {
            return dayjs
        }
    },
    data(){
        return{
            cdn:this.$store.state.cdn,
            stab:'setup',
            loading:false,
            shop_id:this.$store.state.shop_id,
            shopifyDetail:null,
            counts:{
                products:0,
                custom_collections:0,
                smart_collections:0,
                blogs:0,
                articles:0,
                pages:0,
                customers:0,
                orders:0,
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
            stotal:0,
            page: 1,
            isLoading: false,
            blogs:[],
            pages_count:0,
            blogs_count:0,
            ccats_count:0,
            scats_count:0,
            scusts_count:0,
            sorders_count:0,
        }
    },
    created() {
        this.getShopDetail();
    },

    methods:{
        getShopDetail(){
            this.loading = true;
            axios.get('/superadmin/shopify/detail')
                .then((resp)=>{
                    const allData = resp.data;
                    this.shopifyDetail = allData.shopifyDetail;
                    this.counts = allData.counts;
                    this.pages_count = allData.pages_count || 0;
                    this.blogs_count = allData.blogs_count || 0;
                    this.ccats_count = allData.ccats_count || 0;
                    this.scats_count = allData.scats_count || 0;
                    this.scusts_count = allData.scusts_count || 0;
                    this.sorders_count = allData.sorders_count || 0;
                })
                .finally(()=>{
                    this.loading = false;
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
                .finally(()=>{
                    this.addDialog = false;
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
                    this.getShopDetail();
                    window.Toast.success(`product Synced Successfully`)
                })
                .catch((err)=>{
                    console.log("Sync Errors",err)
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
        // getAndUpdateArticles(){
        //     this.isLoading = true
        //     axios.get('/superadmin/shopify/import-and-save-articles')
        //         .then((resp)=>{
        //             this.blogs = resp.data.blogs || [];
        //             window.Toast.success('Articles imported Successfully')
        //             this.getShopDetail();
        //         })
        //         .catch((err)=>{
        //             console.log('error',err);
        //             window.Toast.error(`Something Err ${err.message}`)
        //         })
        //         .finally(()=>{
        //             this.isLoading = false;
        //         })
        // },
        getAndUpdateCustomCollections(){
            this.isLoading = true
            axios.get('/superadmin/shopify/import-and-save-ccats')
                .then((resp)=>{
                    this.ccats = resp.data.ccats || [];
                    window.Toast.success('Custom Collections imported Successfully')
                })
                .catch((err)=>{
                    console.log('error',err);
                    window.Toast.error(`Something Err ${err.message}`)
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        getAndUpdateSmartCollections(){
            this.isLoading = true
            axios.get('/superadmin/shopify/import-and-save-scats')
                .then((resp)=>{
                    this.scats = resp.data.scats || [];
                    window.Toast.success('Smart Collections imported Successfully')
                })
                .catch((err)=>{
                    console.log('error',err);
                    window.Toast.error(`Something Err ${err.message}`)
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        syncProductsSeo(){
            this.syncLoading = true;
            axios.get('/superadmin/shopify/sync-products-seo/'+this.shop_id)
                .then((resp)=>{
                    console.log('Sync Respo',resp);
                    this.getShopDetail();
                    window.Toast.success(`products SEO Synced Successfully`)
                })
                .catch((err)=>{
                    console.log("Sync Errors",err)
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
        startSyncCustomers(){
            this.syncLoading = true;
            axios.post(`/superadmin/shopify/${this.shop_id}/sync-customers`)
                .then((resp)=>{
                    console.log('Sync Resp',resp);
                    this.getShopDetail();
                    window.Toast.success(`customers Synced Successfully`)
                })
                .catch((err)=>{
                    console.log("Customer Sync Errors",err)
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        }
    }
}
</script>
