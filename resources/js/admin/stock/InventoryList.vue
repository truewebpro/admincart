<template>
    <v-container fluid>
        <v-row>
            <v-col cols="12" md="6">
                <span class="text-h6">Inventory</span></v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn class="text-none" size="small" color="grey-darken-4">Export Stock</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <v-toolbar density="default" height="44" color="white">
                        <v-tabs v-model="status" color="primary" density="compact" show-arrows class="w-100">
                            <v-tab v-for="(stat, index) in prostatus" :key="index" :value="stat" class="text-none">
                                {{ stat }}
                            </v-tab>
                        </v-tabs>
                        <v-slide-group density="compact" class="w-100" show-arrows>
                            <v-spacer></v-spacer>
                            <v-btn variant="text" class="text-none ma-1" density="compact">
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                            <v-btn variant="outlined" class="text-none ma-1" density="compact">
                                <v-icon>mdi-magnify</v-icon>
                                <v-icon>mdi-sort-variant</v-icon>
                            </v-btn>
                            <v-btn variant="outlined" class="text-none ma-1" density="compact" min-width="28" max-width="28">
                                <v-icon>mdi-sort</v-icon>
                            </v-btn>
                        </v-slide-group>
                    </v-toolbar>

                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="psearch" class="w-50" variant="outlined" density="compact" clearable hide-details
                                      placeholder="Searching all Products"
                                      prepend-inner-icon="mdi-magnify"
                        ></v-text-field>
                        <v-autocomplete v-model="selectedType" class="mx-1" variant="outlined" density="compact" label="Type"
                                        :items="protypes"
                                        clearable hide-details />
                        <v-autocomplete v-model="selectedBrand" class="mx-1" variant="outlined" density="compact" label="Brand"
                                        :items="pbrands"
                                        clearable hide-details />
                        <v-autocomplete v-model="selectedTag" class="mx-1" variant="outlined" density="compact" label="Tag"
                                        :items="atags"
                                        clearable hide-details />
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
                    <div>
                        <v-data-table :items="filteredPros" :headers="prosHeaders" density="comfortable"
                                      hover show-select select-strategy="single" item-value="variant_id" :search="psearch" :custom-filter="customFilter"
                                      :loading="isLoading" items-per-page="50" mobileBreakpoint="sm">
                            <template v-slot:item.variant_image="{item}">
                                <v-img :src="cdn+item.variant_image" lazy-src="https://dummyimage.com/150x150/efe6f2/01010a.png&text=No+Image" max-width="40"></v-img>
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
                            <!--                        <template v-slot:item.available="{item}">-->
                            <!--                            <v-btn v-if="item.available > 10" variant="text" density="comfortable" color="success">-->
                            <!--                                {{item.available}}</v-btn>-->
                            <!--                            <v-btn v-else variant="text" density="comfortable" color="red">-->
                            <!--                                {{item.available}}</v-btn>-->
                            <!--                        </template>-->
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
                        </v-data-table>
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
import axios from "axios";
import {mergeProps} from "vue";

export default {
    name:"InventoryList",
    data(){
        return{
            psearch:'',
            itab:'set',
            pros:[],
            isLoading: false,
            status:"All",
            selectedType: null,
            selectedBrand: null,
            selectedTag: null,
            prostatus:[],
            protypes:[],
            pbrands:[],
            atags:[],
            cdn:this.$store.state.cdn,
            prosHeaders:[
                {title:'Image',value:'variant_image',width:60},
                {title:'Title',value:'title',maxWidth:375},
                {title:'SKU',value:'sku'},
                {title:'Unavailable',key:'backorder_qty'},
                {title:'Committed',key:'committed'},
                // {title:'Available',value:'available'},
                {title:'On Hand',key:'quantity'},
                {title:'Status',value:'product_status'},
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
    mounted() {
        this.getAllPros();
    },
    computed: {
        filteredPros() {
            // if (this.status === "All") return this.pros;
            if (this.status === "Archived") return this.pros.filter(p => p.product_status === "Archived");
            // return this.pros.filter(p => p.product_status === this.status);
            return this.pros.filter((p) => {
                const matchStatus = this.status === "All" || p.product_status === this.status;
                const matchType = !this.selectedType || p.product_type_name === this.selectedType;
                const matchBrand = !this.selectedBrand || p.brand_name === this.selectedBrand;
                const matchTag = !this.selectedTag || (p.tags && p.tags.includes(this.selectedTag));
                return matchStatus && matchType && matchBrand && matchTag;
            });
        }
    },
    methods:{
        mergeProps,
        customFilter(value, search, item) {
            if (!search) return true;

            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');

            // Return true only if all search terms are found somewhere in the title
            return searchTerms.every(term => title.includes(term));
        },
        getAllPros(){
            this.isLoading = true;
            axios.get('/sadmin/inventory')
                .then((resp)=>{
                    console.log(resp.data);
                    this.pros = resp.data.variants;

                    let statuses = [...new Set(this.pros.map(item => item.product_status))];

                    // Remove "Archive" if already present
                    statuses = statuses.filter(status => status !== "Archived");

                    // Add "All" at the beginning and "Archive" at the end
                    this.prostatus = ["All", ...statuses, "Archived"];
                    this.protypes = [...new Set(this.pros.map(p => p.product_type_name))];
                    this.pbrands = [...new Set(this.pros.map(p => p.brand_name))];

                    const allTags = this.pros.flatMap(p => Array.isArray(p.tags) ? p.tags : []);
                    // Remove duplicates
                    this.atags = [...new Set(allTags)];
                })
                .finally(()=>{
                    this.isLoading = false;
                })
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
                    console.log(respo.data);
                    this.editDialog = false;
                    this.adjust = 0;
                    this.getAllPros();
                })
            console.log('ustock',ustock);
        },
        editItem(item){
            this.editedIndex = this.pros.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.editDialog = true;
        },
    }
}

</script>

<style scoped>
.title:hover{
    span { text-decoration: underline !important;}

    .opacity-0 {opacity: 1 !important;}
}

</style>
