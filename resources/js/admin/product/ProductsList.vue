<template>
    <v-container fluid>
        <v-row>
            <v-col cols="3" md="6">
                <span class="text-h6">Products</span>
            </v-col>
            <v-col cols="9" md="6" class="text-end">
                <v-btn @click="uploadDialog = true" class="text-none" size="small" color="grey-darken-4 me-2">Import</v-btn>
                <v-btn @click="exportProducts" class="text-none" size="small" color="grey-darken-4 me-2">Export</v-btn>
                <v-btn link to="/products/new" class="text-none" size="small" color="grey-darken-4">Add Product</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <v-row dense class="px-2 py-1">
                        <v-col cols="10" md="10">
                            <v-tabs v-model="status" color="primary" density="compact" show-arrows>
                                <v-tab v-for="(stat, index) in prostatus" :key="index" :value="stat" class="text-none">
                                    {{ stat }}
                                </v-tab>
                            </v-tabs>
                        </v-col>
                        <v-col cols="2" md="2" class="text-end">
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
                                <v-list nav density="compact" base-color="dark">
                                    <v-list-item @click="setSort('title')" title="Product Title"></v-list-item>
                                    <v-list-item @click="setSort('created_at')" title="Created" />
                                    <v-list-item @click="setSort('updated_at')" title="Updated" />
                                    <v-list-item @click="setSort('astock_sum_quantity')" title="Inventory" />
                                    <v-list-item @click="setSort('ptype.product_type_name')" title="Product Type" />
                                    <v-list-item @click="setSort('brand.brand_name')" title="Brand" />
                                    <v-divider/>
                                    <v-list-item base-color="primary" @click="setDirection('asc')" title="A-Z" />
                                    <v-list-item base-color="primary" @click="setDirection('desc')" title="Z-A" />
                                </v-list>
                            </v-menu>
                        </v-col>
                    </v-row>
                    <v-row dense class="py-2 px-2">
                        <v-col cols="12" md="6">
                            <v-text-field v-model="psearch" class="w-100" variant="outlined" density="compact" clearable hide-details
                                          placeholder="Searching all Products"
                                          prepend-inner-icon="mdi-magnify"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-autocomplete v-model="selectedType" class="mx-1" variant="outlined" density="compact" label="Type"
                                            :items="protypes"
                                            clearable hide-details />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-autocomplete v-model="selectedBrand" class="mx-1" variant="outlined" density="compact" label="Brand"
                                            :items="pbrands"
                                            clearable hide-details />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-autocomplete v-model="selectedTag" class="mx-1" variant="outlined" density="compact" label="Tag"
                                            :items="atags"
                                            clearable hide-details />
                        </v-col>
                    </v-row>
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
                                <v-list nav density="compact">
                                    <v-list-item @click="archiveProducts">
                                        <v-list-item-title><v-icon class="me-2">mdi-archive-outline</v-icon>Archive Products</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item base-color="error" @click="deleteProducts">
                                        <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Products</v-list-item-title>
                                    </v-list-item>
                                    <v-divider/>
                                    <v-list-item @click="addBulkTagDialog = true">
                                        <v-list-item-title><v-icon class="me-2">mdi-tag-plus-outline</v-icon>Add Tags</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item base-color="error" @click="removeBulkTagDialog = true">
                                        <v-list-item-title><v-icon class="me-2">mdi-tag-remove-outline</v-icon>Remove Tags</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </v-col>
                    </v-row>
                    <div>
                        <v-data-table :items="filteredPros" :headers="prosHeaders" density="comfortable" v-model="selectedPros"
                                      hover show-select :search="psearch" :custom-filter="customFilter" return-object
                                      :loading="isLoading" items-per-page="50" mobileBreakpoint="sm">
                            <template v-slot:item.title="{item}">
                                <div class="title d-flex align-center justify-space-between">
                                    <router-link :to="{ name: 'ProductEdit', params: { product_id: item.product_id } }"
                                                 class="font-weight-medium text-grey-darken-4 linkd">
                                        {{item.title}}
                                    </router-link>
                                    <a :href="'https://'+domain+'/products/'+item.handle" target="_blank">
                                        <v-icon class="opacity-0" title="View Online Store">mdi-eye-outline</v-icon>
                                    </a>
                                </div>
                            </template>
                            <template v-slot:item.featured_image="{item}">
                                <v-img v-if="item.featured_image != null" :src="cdn+item.featured_image" lazy-src="https://dummyimage.com/150x150/efe6f2/01010a.png&text=No+Image" max-width="40"></v-img>
                                <v-img v-else :src="cdn+'noimage.png'" lazy-src="https://dummyimage.com/150x150/efe6f2/01010a.png&text=No+Image" max-width="40"></v-img>
                            </template>
                            <template v-slot:item.product_status="{item}">
                                <v-chip size="small" class="bg-light-green-accent-1 text-black"  v-if="item.product_status === 'Active'">{{item.product_status}}</v-chip>
                                <v-chip size="small" v-else>{{item.product_status}}</v-chip>
                            </template>
                            <template v-slot:item.astock_sum_quantity="{item}">
                                <div v-if="item.astock_sum_quantity > 0">{{ item.astock_sum_quantity }} in stock
                                    <span v-if="item.variants[0].options != null">for {{item.variants.length}} variants</span>
                                </div>
                                <div v-if="item.astock_sum_quantity < 1" class="text-red">{{ item.astock_sum_quantity }} in stock
                                    <span v-if="item.variants[0].options != null">for {{item.variants.length}} variants</span>
                                </div>
                            </template>
                        </v-data-table>
                    </div>
                </v-card>
            </v-col>
            <v-dialog max-width="500" v-model="addBulkTagDialog">
                <v-card>
                    <v-card-title class="d-flex">
                        Add tags to {{selectedPros.length}} Product
                        <v-spacer/>
                        <v-icon size="small" @click="closeTag">mdi-close</v-icon>
                    </v-card-title>
                    <v-card-text class="overflow-y-auto">
                        <div v-if="seltags.length > 0">
                            <h4>To add {{seltags.length}} Tag</h4>
                            <v-chip
                                v-for="(seltag, index) in seltags"
                                :key="seltag"
                                closable
                                color="primary" density="compact"
                                class="ma-1"
                                @click:close="removeTag(seltag)"
                            >
                                {{ seltag }}
                            </v-chip>
                        </div>
                        <div v-else>

                        </div>
                        <h4>Available</h4>
                        <div v-for="(atag,index) in filteredAptags" :key="index">
                            <v-checkbox-btn v-model="seltags" :value="atag.tag_name" :label="atag.tag_name" density="compact"></v-checkbox-btn>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn density="compact" color="grey-darken-4" variant="outlined" @click="closeTag">Cancel</v-btn>
                        <v-btn density="compact" @click="addBulkTags" :disabled="seltags.length < 1"
                               color="primary" variant="elevated">Save</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <v-dialog max-width="500" v-model="removeBulkTagDialog">
                <v-card>
                    <v-card-title class="d-flex">
                        Remove tags to {{selectedPros.length}} Product
                        <v-spacer/>
                        <v-icon size="small" @click="closeTag">mdi-close</v-icon>
                    </v-card-title>
                    <v-card-text class="overflow-y-auto">
                        <div v-if="seltags.length !== 0">
                            <h4>To remove {{seltags.length}} Tag</h4>
                            <v-chip
                                v-for="(seltag, index) in seltags"
                                :key="seltag"
                                closable
                                color="primary" density="compact"
                                class="ma-1"
                                @click:close="removeTag(seltag)"
                            >
                                {{ seltag }}
                            </v-chip>
                        </div>
                        <div v-else>
                            <p>No tags to remove</p>
                        </div>
                        <h4>Available</h4>
                        <div v-for="(atag,index) in filteredAptags" :key="index">
                            <v-checkbox-btn v-model="seltags" :value="atag.tag_name" :label="atag.tag_name"
                                            density="compact"></v-checkbox-btn>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn density="compact" color="grey-darken-4" variant="outlined" @click="closeTag">Cancel</v-btn>
                        <v-btn density="compact" @click="removeBulkTags" :disabled="seltags.length < 1"
                               color="primary" variant="elevated">Save</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <v-dialog max-width="400" v-model="uploadDialog">
                <v-card>
                    <v-card-title>Upload Products</v-card-title>
                    <v-form v-model="uploadValid">
                        <v-card-text>
                            <v-file-input :rules="iproRule" class="mb-3" show-size
                                          v-model="ipros" variant="outlined" density="compact"
                                          label="Import Products (CSV/XLSX)"
                                          prepend-icon="mdi-file-delimited-outline" hint="Should be CSV or XLSX format" persistent-hint
                                          accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"/>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer/>
                            <v-btn :disabled="!uploadValid" density="comfortable" color="success" @click="uploadProducts">Upload</v-btn>
                            <v-btn density="compact" @click="closeUpload" color="red">Cancel</v-btn>
                        </v-card-actions>
                    </v-form>

                </v-card>
            </v-dialog>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";
import {mergeProps} from "vue";

export default {
    name:"ProductsList",
    data(){
        return{
            psearch:'',
            selectedPros:[],
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
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            prosHeaders:[
                {title:'Image',value:'featured_image',width:60},
                {title:'Title',value:'title',maxWidth:375},
                {title:'Status',value:'product_status'},
                {title:'Inventory',value:'astock_sum_quantity'},
                {title:'Type',key:'ptype.product_type_name'},
                {title:'Vendor',key:'brand.brand_name'},
            ],
            addBulkTagDialog:false,
            removeBulkTagDialog:false,
            seltags:[],
            sortKey: 'updated_at', // default sort
            sortDirection: 'desc',
            ipros:null,
            uploadValid:false,
            uploadDialog:false,
            iproRule:[
                (v) => !!v || "File is required to upload",
            ]
        }
    },
    mounted() {
        this.getAllPros();
    },
    computed: {
        filteredPros() {
            let filtered = this.pros.filter((p) => {
                const matchStatus = this.status === "All" || p.product_status === this.status;
                const matchType = !this.selectedType || p.ptype?.product_type_name === this.selectedType;
                const matchBrand = !this.selectedBrand || p.brand?.brand_name === this.selectedBrand;
                const matchTag = !this.selectedTag || (p.tags && p.tags.includes(this.selectedTag));
                return matchStatus && matchType && matchBrand && matchTag;
            });

            // Sort the filtered list
            if (this.sortKey) {
                filtered = filtered.sort((a, b) => {
                    const aVal = this.getSortValue(a, this.sortKey).toString();
                    const bVal = this.getSortValue(b, this.sortKey).toString();

                    if (typeof aVal === 'number' && typeof bVal === 'number') {
                        return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
                    }

                    return this.sortDirection === 'asc'
                        ? aVal.toString().localeCompare(bVal.toString(), undefined, { sensitivity: 'base' })
                        : bVal.toString().localeCompare(aVal.toString(), undefined, { sensitivity: 'base' });
                });
            }

            return filtered;
        },
        aptags(){
            return this.$store.state.tags;
        },
        filteredAptags() {
            return this.aptags.filter(tag => !this.seltags.includes(tag.tag_name));
        },
    },
    methods:{
        mergeProps,
        setSort(key) {
            if (this.sortKey === key) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDirection = 'asc';
            }
        },
        setDirection(dir) {
            this.sortDirection = dir;
        },
        getSortValue(item, key) {
            return key.split('.').reduce((obj, prop) => obj?.[prop], item) ?? '';
        },
        removeTag(tagToRemove) {
            this.seltags = this.seltags.filter(tag => tag !== tagToRemove);
        },
        closeTag(){
            this.addBulkTagDialog = false;
            this.removeBulkTagDialog = false;
            this.seltags = [];
            this.selectedPros = [];
        },
        customFilter(value, search, item) {
            if (!search) return true;

            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');

            // Return true only if all search terms are found somewhere in the title
            return searchTerms.every(term => title.includes(term));
        },
        getAllPros(){
            this.isLoading = true;
            axios.get('/sadmin/pros')
                .then((resp)=>{
                    this.pros = resp.data.products;
                    // Inject "Archived" if deleted_at is not null
                    this.pros = this.pros.map((item) => {
                        if (item.deleted_at !== null) {
                            return {...item, product_status: "Archived"};
                        }
                        return item;
                    });

                    let statuses = [...new Set(this.pros.map(item => item.product_status))];

                    statuses = statuses.filter(status => status !== "Archived");

                    this.prostatus = ["All", ...statuses, "Archived"];
                    this.protypes = [...new Set(this.pros.map(p => p.ptype.product_type_name))];
                    this.pbrands = [...new Set(this.pros.map(p => p.brand.brand_name))];
                    const allTags = resp.data.products.flatMap(p => Array.isArray(p.tags) ? p.tags : []);
                    this.atags = [...new Set(allTags)];
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        archiveProducts(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const productaIds = this.selectedPros.map(p => p.product_id);
            const productIds = this.selectedPros.filter(p => p.deleted_at === null).map(p => p.product_id);
            const sdeletes = {
                product_ids: productIds,
                mtype:"softdelete"
            }
            axios.post('/sadmin/products/bulk-delete',sdeletes,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.selectedPros = [];
                    this.getAllPros();
                })
                .catch((error)=>{
                    window.Toast.error(error.response.data.message);
                })
        },
        deleteProducts(){
            const confirmed = window.confirm("Are you sure you want to permanently delete the selected products?");
            if (!confirmed) return;

            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const productIds = this.selectedPros.map(p => p.product_id);
            const perdeletes = {
                product_ids: productIds,
                mtype:"perdelete"
            }
            axios.post('/sadmin/products/bulk-delete',perdeletes,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.selectedPros = [];
                    this.getAllPros();
                })
                .catch((error)=>{
                    const msg = error?.response?.data?.message || "Delete failed.";
                    window.Toast.error(msg);
                })
        },
        addBulkTags(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const productIds = this.selectedPros.map(p => p.product_id);
            const ptags = this.selectedPros.map(p => p.tags);
            const atags = {
                product_ids:productIds,
                tagstoadd:this.seltags,
                mtype:"add",
            }
            axios.post('/sadmin/products/bulk-tag-add',atags,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.closeTag();
                    this.getAllPros();
                })
        },
        removeBulkTags(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const productIds = this.selectedPros.map(p => p.product_id);
            const ptags = this.selectedPros.map(p => p.tags);
            const rtags = {
                product_ids:productIds,
                tagstoremove:this.seltags,
                mtype:"remove",
            }
            axios.post('/sadmin/products/bulk-tag-add',rtags,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.closeTag();
                    this.getAllPros();
                })
            console.log('rtags',rtags);
        },
        exportProducts(){
            const token = localStorage.getItem('token');
            const url = `/sadmin/products/export?token=${token}`;
            window.open(url, '_blank');
        },
        uploadProducts() {
            const file = this.ipros;

            const ufile = new FormData();
            ufile.append('file', file);
            ufile.append('shop_id', this.$store.state.shop.shop_id);

            axios.post('/sadmin/products/import', ufile, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(resp => {
                console.log('Imported:', resp.data);
                this.uploadDialog = false;
                this.getAllPros();
            });
        },
        closeUpload(){
            this.uploadDialog= false;
            this.ipros = null;
        }
    }
}

</script>

<style scoped>
.linkd {text-decoration: none;}
.linkd:hover {text-decoration: underline;}
.title:hover{
    span { text-decoration: underline !important;}

    .opacity-0 {opacity: 1 !important;}
}

</style>
