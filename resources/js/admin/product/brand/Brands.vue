<template>
    <v-container>
        <v-row dense>
            <v-col cols="6" md="6">
                <span class="text-h6">Brands</span></v-col>
            <v-col cols="6" md="6" class="text-end">
                <v-btn class="text-none" variant="tonal" density="comfortable" prependIcon="mdi-plus" color="primary" @click="addDialog = true">Add Brand</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="bsearch" class="w-50 me-2" variant="outlined" density="compact" clearable hide-details
                                      placeholder="Searching all Brands"
                                      prepend-inner-icon="mdi-magnify"
                        ></v-text-field>
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
                                <v-list-item title="Brand A-Z" />
                                <v-list-item title="Brand Z-A" />
                                <v-list-item title="Created ASC" />
                                <v-list-item title="Created DSC" />
                                <v-list-item title="Updated ASC" />
                                <v-list-item title="Updated DSC" />
                            </v-list>
                        </v-menu>
                    </div>
                    <div>
                        <v-data-table-server density="comfortable" mobileBreakpoint="sm"
                            v-model:page="page"
                            v-model:items-per-page="itemsPerPage"
                            v-model:sort-by="sortBy"
                            :items="brands"
                            :headers="brandsHeaders"
                            :items-length="totalItems"
                            :loading="isLoading"
                            hover
                            @update:options="loadItems"
                        >
                            <template v-slot:item.brand_name="{item}">
                                <div class="title d-flex align-center justify-space-between">
                                    <div class="text-decoration-none text-grey-darken-3">
                                        <span>{{item.brand_name}}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:item.brand_image="{item}">
                                <v-img v-if="item.brand_image != null" :src="cdn+item.brand_image" alt="Brand" max-width="40"></v-img>
                                <v-img v-else src="https://dummyimage.com/200x200/000/fff&text=No Image" alt="Brand" max-width="40"></v-img>
                            </template>
                            <template v-slot:item.brand_status="{item}">
                                <v-chip size="small" class="bg-light-green-accent-1 text-black"  v-if="item.brand_status === 'Active'">{{item.brand_status}}</v-chip>
                                <v-chip size="small" v-else>{{item.brand_status}}</v-chip>
                            </template>
                            <template v-slot:item.actions="{item}">
                                <v-btn variant="tonal" color="primary" density="compact" link :to="{name:'BrandEdit',params:{brand_id:item.brand_id}}">Edit</v-btn>
                                <v-btn variant="tonal" color="red" density="compact" @click="deleteItem(item)" class="ms-2">Delete</v-btn>
                            </template>
                        </v-data-table-server>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-dialog max-width="600" v-model="addDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="addValid" @submit.prevent="addBrand">
                                <div class="my-2">
                                    <v-text-field
                                        v-model="defaultItem.brand_name"
                                        variant="outlined" density="compact"
                                        label="Brand Name" :rules="btitleRule">
                                    </v-text-field>
                                </div>
                                <div class="mb-2">
                                    <v-textarea v-model="defaultItem.brand_desc" variant="outlined" density="compact"
                                                label="Brand Desc" rows="3"></v-textarea>
                                </div>
                                <div class="mb-2 d-flex">
                                    <div class="w-50">
                                        <v-file-upload v-model="defaultItem.brand_image" density="compact" title="Brand Image"
                                                       clearable icon="mdi-plus" show-size></v-file-upload>
                                    </div>
                                    <div class="w-50 ms-2">
                                        <v-select v-model="defaultItem.brand_status" :items="brandstatus" variant="outlined" density="compact"
                                                  label="Status"></v-select>
                                    </div>
                                </div>
                                <div class="my-2">
                                    <v-text-field v-model="defaultItem.meta_title" variant="outlined" density="compact"
                                                  label="Meta Title"></v-text-field>
                                </div>
                                <div class="mb-2">
                                    <v-textarea v-model="defaultItem.meta_desc" variant="outlined" density="compact"
                                                label="Meta Desc" rows="3"></v-textarea>
                                </div>
                                <div class="d-flex">
                                    <v-btn type="submit" :loading="addLoading" :disabled="!addValid || addLoading" variant="elevated" density="comfortable" color="success">Add</v-btn>
                                    <v-spacer/>
                                    <v-btn @click="addDialog = false" variant="elevated" density="comfortable" color="red">Cancel</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="300" v-model="deleteDialog">
                    <v-card>
                        <v-card-text class="text-center">
                            <v-form @submit.prevent="deleteBrand">
                                <h3 v-if="editedItem.products_count > 0">Products associated count is <span class="text-red">{{editedItem.products_count}}</span></h3>
                                <h4 v-if="editedItem.products_count > 0">Can't be deleted,<br/> Please change the brand of products associated</h4>
                                <h3 v-else>Are you sure to delete Brand <br/> {{editedItem.brand_name}}</h3>
                                <div class="d-flex ga-3 mt-3">
                                    <v-spacer/>
                                    <v-btn type="submit" :disabled="editedItem.products_count > 0" variant="elevated" density="comfortable" color="success">Yes</v-btn>
                                    <v-btn @click="deleteDialog = false" variant="elevated" density="comfortable" color="red">No</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import debounce from 'lodash/debounce';
import axios from "axios";
import {mergeProps} from "vue";
import {VFileUpload} from "vuetify/labs/components";

export default {
    name:"Brands",
    components:{VFileUpload},
    data(){
        return{
            bsearch:'',
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by: '',
            sort_order: 'desc',
            sortBy: [],
            brands:[],
            isLoading: false,
            upValid: false,
            delValid: false,
            addValid: false,
            addLoading: false,
            delLoading: false,
            status:"All",
            bstatuses:[],
            brandstatus:['Active','Inactive'],
            pbrands:[],
            cdn:this.$store.state.cdn,
            shopname:this.$store.state.shop.shop_name || "Shop_name",
            brandsHeaders:[
                {title:'Image',key:'brand_image',sortable:false,width:60},
                {title:'Name',key:'brand_name',maxWidth:375},
                {title:'Status',value:'brand_status'},
                {title:'Product Count',key:'products_count'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                brand_name: '',
                brand_desc: '',
                brand_status: 'Active',
                brand_image: null,
                meta_title: '',
                meta_desc: '',
            },
            editedItem:{
                brand_id: '',
                brand_name: '',
                brand_desc: '',
                brand_status: '',
                brand_image: null,
                previewImage:'',
                meta_title: '',
                meta_desc: '',
            },
            editDialog:false,
            addDialog:false,
            deleteDialog:false,
            btitleRule:[
                (v) => !!v || "Brand is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicateBrandName(v) || 'Brand name already exists',
            ],
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getAllBrands();
        }, 400);
    },
    watch:{
        'editedItem.brand_image'(newVal){
            this.updatePreviewImage(newVal)
        },
        bsearch() {
            this.debouncedSearch();
        },
        status() {
            this.page = 1;
            this.getAllBrands();
        }
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
                this.sort_by = 'brand_id';
                this.sort_order = 'desc';
            }
            this.getAllBrands();
        },
        async getAllBrands() {
            this.isLoading = true;
            try {
                const resp = await axios.get('/sadmin/brands', {
                    params: {
                        page: this.page,
                        per_page: this.itemsPerPage,
                        search: this.bsearch,
                        sort_by: this.sort_by,
                        sort_order: this.sort_order,
                        status: this.status,
                    }
                });
                const allData = resp.data.brands;
                this.brands = allData.data;
                this.totalItems = allData.total;
                this.page = allData.current_page;
            } catch (e) {
                console.error("Failed to load brands", e);
            } finally {
                this.isLoading = false;
            }
        },
        updatePreviewImage(fileList) {
            const file = Array.isArray(fileList) ? fileList[0] : fileList;
            if (file instanceof File) {
                this.editedItem.brand_image = file;
                this.editedItem.previewImage = URL.createObjectURL(file);
            } else if (typeof file === 'string' && file !== '') {
                this.editedItem.previewImage = this.cdn + file;
                this.editedItem.brand_image = file;
            } else {
                this.editedItem.brand_image = null;
                this.editedItem.previewImage = `https://dummyimage.com/1200x630/000/fff&text=${this.shopname}`;
            }
        },
        deleteItem(item){
            this.editedIndex = this.brands.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.deleteDialog = true;
        },
        deleteBrand(){
            this.delLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const dbrand = {
                brand_id:this.editedItem.brand_id,
            }
            axios.post('/sadmin/brand/delete',dbrand,uheaders)
                .then((resp)=>{
                    this.deleteDialog = false;
                   this.getAllBrands();
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    this.delLoading = false;
                    window.Toast.success('Brand deleted Successfully');
                })
        },
        addBrand(){
            this.addLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const nbrand = {
                brand_name:this.defaultItem.brand_name,
                brand_desc:this.defaultItem.brand_desc,
                brand_status:this.defaultItem.brand_status,
                brand_image:this.defaultItem.brand_image,
                meta_title:this.defaultItem.meta_title,
                meta_desc:this.defaultItem.meta_desc,
            }
            axios.post('/sadmin/brand/update',nbrand,uheaders)
                .then((resp)=>{
                    this.addDialog = false;
                    this.getAllBrands();
                    this.defaultItem = {};
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    this.addLoading = false;
                    window.Toast.success('Brand Added Successfully');
                })
        },
        checkDuplicateBrandName(name) {
            const nameLower = name.trim().toLowerCase();
            return this.brands.some((brand, index) => {
                const isSameName = brand.brand_name.trim().toLowerCase() === nameLower;
                if (this.editedIndex !== -1 && index === this.editedIndex) return false;
                return isSameName;
            });
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
