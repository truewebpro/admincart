<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="6" md="6">
                <span class="text-h6">Inventory</span></v-col>
            <v-col cols="6" md="6" class="text-end">
                <v-btn class="text-none" size="small" color="grey-darken-4">Export Stock</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <div class="d-flex px-2 py-1">
                        <v-tabs v-model="status" color="primary" density="compact" show-arrows class="w-100">
                            <v-tab v-for="(stat, index) in prostatus" :key="index" :value="stat" class="text-none">
                                {{ stat }}
                            </v-tab>
                        </v-tabs>
                        <v-menu>
                            <template v-slot:activator="{ props: menu }">
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props: tooltip }">
                                        <v-btn v-bind="mergeProps(menu, tooltip)" icon density="comfortable">
                                            <v-icon size="small">mdi-sort</v-icon>
                                        </v-btn>
                                    </template>
                                    <span>Sort</span>
                                </v-tooltip>
                            </template>
                            <v-list density="compact">
                                <v-list-item title="Product Title" />
                                <v-list-item title="Created" />
                                <v-list-item title="Updated" />
                                <v-list-item title="Inventory" />
                                <v-list-item title="Product Type" />
                                <v-list-item title="Brand" />
                                <v-divider/>
                                <v-list-item title="A-Z" />
                                <v-list-item title="Z-A" />
                            </v-list>
                        </v-menu>
                    </div>
                    <div class="px-2 py-2">
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="psearch" class="w-100" variant="outlined" density="compact" clearable hide-details
                                              placeholder="Searching all Products"
                                              prepend-inner-icon="mdi-magnify"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-autocomplete v-model="selectedType" variant="outlined" density="compact" label="Type"
                                                :items="protypes"
                                                clearable hide-details />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-autocomplete v-model="selectedBrand" variant="outlined" density="compact" label="Brand"
                                                :items="pbrands"
                                                clearable hide-details />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-autocomplete v-model="selectedTag" variant="outlined" density="compact" label="Tag"
                                                :items="atags"
                                                clearable hide-details />
                            </v-col>
                        </v-row>
                    </div>
                    <div>
                        <v-data-table-server density="comfortable" mobileBreakpoint="sm"
                            v-model:page="page" loading-text="Loading All Stock"
                            v-model:sort-by="sortBy"
                            v-model:items-per-page="itemsPerPage"
                            :items="variants"
                            :items-length="totalItems"
                            :items-per-page="itemsPerPage"
                            :loading="isLoading" hover
                            :headers="prosHeaders"
                            @update:options="loadItems"
                        >
                            <template v-slot:item.variant_image="{item}">
                                <v-img :src="`${cdn}${item.variant_image || item.featured_image}`" lazy-src="https://dummyimage.com/150x150/efe6f2/01010a.png&text=No+Image" max-width="40"></v-img>
                            </template>
                            <template v-slot:item.title="{item}">
                                <div class="title d-flex align-center justify-space-between">
                                    <router-link class="text-decoration-none text-grey-darken-3"
                                                 :to="'/products/'+item.product_id">
                                        <div class="font-weight-medium">{{item.title}}</div>
                                        <span v-if="item.option_values != null">
                                        <v-chip v-for="(val,key) in Object(item.option_values)" variant="tonal" density="compact"
                                                color="grey-darken-5" size="small" class="font-italic font-weight-bold">{{val}}</v-chip>
                                    </span>
                                        <span v-else>{{item.option_values}}</span>
                                    </router-link>
                                </div>
                            </template>
                            <template v-slot:item.committed="{item}">
                                <span>{{item.committed}}</span>
                            </template>
                            <template v-slot:item.quantity="{item}">
                                <v-btn v-if="item.quantity > 10" variant="outlined" density="comfortable" color="primary" class="text-decoration-underline"
                                       @click="editItem(item)">{{item.quantity}}</v-btn>
                                <v-btn v-else variant="outlined" density="comfortable" color="red" class="text-decoration-underline"
                                       @click="editItem(item)">{{item.quantity}}</v-btn>
                            </template>
                            <template v-slot:item.product_status="{item}">
                                <v-chip size="small" class="bg-light-green-accent-1 text-black"  v-if="item.product_status === 'Active'">{{item.product_status}}</v-chip>
                                <v-chip size="small" v-else>{{item.product_status}}</v-chip>
                            </template>
                        </v-data-table-server>
                        <v-dialog max-width="300" v-model="editDialog">
                            <v-card>
                                <v-card-title class="text-body-2">{{editedItem.title}}</v-card-title>
                                <v-card-subtitle>SKU: {{editedItem.sku}}</v-card-subtitle>
                                <v-card-text>
                                    <v-tabs v-model="itab" selected-class="bg-success" bg-color="grey-lighten-3" grow height="32" align-tabs="center">
                                        <v-tab value="set">Set</v-tab>
                                        <v-tab value="adjust">Adjust</v-tab>
                                    </v-tabs>
                                    <v-tabs-window v-model="itab">
                                        <v-tabs-window-item value="set">
                                            <div class="my-4">
                                                <v-number-input v-model="editedItem.quantity" variant="outlined" density="compact"
                                                                label="Set Quantity" control-variant="stacked" hide-details></v-number-input>
                                            </div>
                                        </v-tabs-window-item>
                                        <v-tabs-window-item value="adjust">
                                            <div class="my-4">
                                                <v-number-input v-model="adjust" variant="outlined" density="compact"
                                                                label="Adjust Quantity" control-variant="stacked"
                                                                persistent-placeholder hide-details></v-number-input>
                                            </div>
                                        </v-tabs-window-item>
                                    </v-tabs-window>
                                    <div class="d-flex">
                                        <v-btn @click="updateStock" variant="elevated" density="comfortable" color="success">Update</v-btn>
                                        <v-spacer/>
                                        <v-btn @click="editDialog = false" variant="elevated" density="comfortable" color="red">Cancel</v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-dialog>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import debounce from 'lodash/debounce';
import axios from "axios";
import {mergeProps} from "vue";

export default {
    name:"InventoryList",
    data(){
        return{
            psearch:'',
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by:'',
            sortBy:[],
            sort_order:"desc",
            variants:[],
            itab:'set',
            isLoading: false,
            status:"All",
            selectedType: null,
            selectedBrand: null,
            selectedTag: null,
            prostatus:['All', 'Active', 'Draft', 'Archived'],
            protypes:[],
            pbrands:[],
            atags:[],
            cdn:this.$store.state.cdn,
            prosHeaders:[
                {title:'Image',key:'variant_image',sortable:false,width:60},
                {title:'Title',key:'title',maxWidth:375},
                {title:'SKU',key:'sku'},
                {title:'Unavailable',key:'backorder_qty'},
                {title:'Committed',key:'committed'},
                // {title:'Available',value:'available'},
                {title:'On Hand',key:'quantity'},
                {title:'Status',key:'product_status'},
            ],
            editedIndex:-1,
            defaultItem:{
                stock_id: '',
                quantity: '',
                location_id: '',
                variant_id: '',
                product_id: '',
                shop_id: '',
            },
            adjust: 0,
            editedItem:{
                stock_id: '',
                quantity: '',
                location_id: '',
                variant_id: '',
                product_id: '',
                shop_id: '',
            },
            editDialog:false,
            addDialog:false,
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getAllStock();
        }, 400);
    },
    watch: {
        psearch() {
            this.debouncedSearch();
        },
        status() {
            this.page = 1;
            this.getAllStock();
        },
        selectedType() {
            this.page = 1;
            this.getAllStock();
        },
        selectedBrand() {
            this.page = 1;
            this.getAllStock();
        },
        selectedTag() {
            this.page = 1;
            this.getAllStock();
        }
    },
    methods:{
        mergeProps,
        getAllStock(){
            this.isLoading = true;
            axios.get('/sadmin/inventory',{
                params: {
                    page:this.page,
                    search:this.psearch,
                    per_page: this.itemsPerPage,
                    sort_by:this.sort_by,
                    sort_order:this.sort_order,
                    type: this.selectedType,
                    brand: this.selectedBrand,
                    tag: this.selectedTag,
                    status: this.status,
                }
            })
                .then((resp)=>{
                    const respData = resp.data;
                    const allData = respData.variants;
                    this.variants = allData.data;
                    this.totalItems = allData.total;
                    this.page = allData.current_page;
                    const filtersData = respData.filters;
                    this.protypes = filtersData.types || [];
                    this.pbrands = filtersData.brands || [];
                    this.atags = filtersData.tags || [];
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        loadItems(options) {
            this.page = options.page;
            this.itemsPerPage = options.itemsPerPage;
            if (options.sortBy.length > 0) {
                this.sort_by = options.sortBy[0].key;
                this.sort_order = options.sortBy[0].order;
            } else {
                this.sort_by = 'variants.variant_id';
                this.sort_order = 'desc';
            }
            this.getAllStock();
        },
        updateStock(){
            const ustock = {
                stock_id: this.editedItem.stock_id,
                action:this.itab,
                set_qty: this.editedItem.quantity,
                adjust_qty: this.adjust,
                location_id: this.editedItem.location_id,
                variant_id: this.editedItem.variant_id,
                product_id: this.editedItem.product_id,
                shop_id: this.editedItem.shop_id,
            }
            axios.post('/sadmin/inventory/update',ustock)
                .then((respo)=>{
                    window.Toast.success(respo.data.message);
                    this.editDialog = false;
                    this.adjust = 0;
                    this.getAllStock();
                })
        },
        editItem(item){
            this.editedIndex = this.variants.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.editDialog = true;
        }
    }
}

</script>

<style scoped>
.title:hover{
    span { text-decoration: underline !important;}

    .opacity-0 {opacity: 1 !important;}
}

</style>
