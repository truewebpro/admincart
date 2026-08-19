<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6">
                <span class="text-h6">Product Types</span></v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn class="text-none" variant="tonal" density="comfortable" prependIcon="mdi-plus" color="success" @click="addDialog = true">Add Type</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="psearch" class="w-50 me-2" variant="outlined" density="compact" clearable hide-details
                                      placeholder="Searching all Products"
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
                            <v-list density="compact" base-color="dark">
                                <v-list-item @click="setSort('product_type_name')" title="Title" />
                                <v-list-item @click="setSort('updated_at')" title="Updated" />
                                <v-divider/>
                                <v-list-item @click="setDirection('asc')" title="A-Z" />
                                <v-list-item @click="setDirection('desc')" title="Z-A" />
                            </v-list>
                        </v-menu>
                    </div>
                    <v-data-table-server density="comfortable"  mobileBreakpoint="sm"
                        v-model:page="page"
                        v-model:items-per-page="itemsPerPage"
                        v-model:sort-by="sortBy"
                        :items="ptypes"
                        :headers="ptypesHeaders"
                        :items-length="totalItems"
                        :loading="isLoading"
                        hover
                        @update:options="loadItems"
                    >
                        <template v-slot:item.product_type_name="{item}">
                            <div class="title d-flex align-center justify-space-between">
                                <div class="text-decoration-none text-grey-darken-3">
                                    <span>{{item.product_type_name}}</span>
                                </div>
                            </div>
                        </template>
                        <template v-slot:item.product_type_status="{item}">
                            <v-chip size="small" class="bg-light-green-accent-1 text-black"  v-if="item.product_type_status === 'Active'">{{item.product_type_status}}</v-chip>
                            <v-chip size="small" v-else>{{item.product_type_status}}</v-chip>
                        </template>
                        <template v-slot:item.actions="{item}">
                            <v-btn variant="tonal" color="primary" density="compact" @click="editItem(item)">Edit</v-btn>
                            <v-btn variant="tonal" color="red" density="compact"
                                   @click="deleteItem(item)" class="ms-2">Delete</v-btn>
                        </template>
                    </v-data-table-server>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-dialog max-width="400" v-model="editDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="upValid" @submit.prevent="editPtype">
                                <div class="my-2">
                                    <v-text-field v-model="editedItem.product_type_name" variant="outlined" density="compact"
                                                  label="Product Type" :rules="ptitleRule"></v-text-field>
                                </div>
                                <div class="mb-2">
                                    <v-select v-model="editedItem.product_type_status" :items="pstatus" variant="outlined" density="compact"
                                              label="Status"></v-select>
                                </div>
                                <div>
                                    <v-btn type="submit" :disabled="!upValid || upLoading " variant="elevated" density="comfortable" color="success">Update</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="400" v-model="deleteDialog">
                    <v-card>
                        <v-card-title class="d-flex">
                            <span>Delete Product Type</span>
                            <v-spacer/>
                            <v-icon @click="deleteDialog = false">mdi-close</v-icon>
                        </v-card-title>
                        <v-card-text class="text-center">
                            <h2>{{editedItem.product_type_name}}</h2>
                            <div v-if="editedItem.products_count > 0">products associated count is {{editedItem.products_count}}</div>
                            <div v-if="editedItem.products_count > 0">Can't be deleted,<br/> Please change the product type of products associated</div>
                           <div v-else>Are you sure to delete type {{editedItem.product_type_name}}</div>
                            <div class="d-flex mt-3 justify-space-around">
                                <v-btn :disabled="editedItem.products_count > 0" @click.prevent="deletePtype" variant="elevated" color="green" density="compact">Yes</v-btn>
                                <v-btn @click="deleteDialog = false" variant="elevated" color="red" density="compact">Cancel</v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="400" v-model="addDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="adValid" @submit.prevent="addPtype">
                                <div class="my-2">
                                    <v-text-field v-model="defaultItem.product_type_name" variant="outlined" density="compact"
                                                  label="Product Type" :rules="ptitleRule"></v-text-field>
                                </div>
                                <div class="mb-2">
                                    <v-select v-model="defaultItem.product_type_status" :items="pstatus" variant="outlined" density="compact"
                                              label="Status"></v-select>
                                </div>
                                <div>
                                    <v-btn type="submit" :disabled="!adValid || adLoading " variant="elevated" density="comfortable" color="success">Add</v-btn>
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
import axios from "axios";
import {mergeProps} from "vue";
import debounce from "lodash/debounce";

export default {
    name:"Ptypes",
    data(){
        return{
            psearch:'',
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by: '',
            sort_order: 'desc',
            sortBy: [],
            ptypes:[],
            deleteDialog: false,
            isLoading: false,
            upValid: false,
            upLoading: false,
            adValid: false,
            adLoading: false,
            status:"All",
            pstatuses:[],
            pstatus:['Active','Inactive'],
            ptypesHeaders:[
                {title:'ID',value:'product_type_id',width:60},
                {title:'Name',key:'product_type_name',maxWidth:375},
                {title:'Status',key:'product_type_status'},
                {title:'Product Count',key:'products_count'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                product_type_name: '',
                product_type_status: 'Active',
            },
            editedItem:{
                product_type_id: '',
                product_type_name: '',
                product_type_status: '',
                products_count: 0,
            },
            editDialog:false,
            addDialog:false,
            ptitleRule:[
                (v) => !!v || "Product Type is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicatePtypeName(v) || 'Product Type name already exists',
            ],
            sortKey: 'updated_at', // default sort
            sortDirection: 'desc',
        }
    },
    watch:{
        psearch() {
            this.debouncedSearch();
        },
        status() {
            this.page = 1;
            this.getAllPtypes();
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getAllPtypes();
        }, 400);
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
                this.sort_by = 'product_type_id';
                this.sort_order = 'desc';
            }
            this.getAllPtypes();
        },
        setSort(key) {
            this.sortBy = [{
                key: key,
                order: this.sort_order || 'asc'
            }];
            this.sort_by = key;
            this.page = 1;
            this.getAllPtypes();
        },
        setDirection(direction) {
            this.sort_order = direction;
            this.sortBy = [{
                key: this.sort_by || 'product_type_name',
                order: direction
            }];
            this.page = 1;
            this.getAllPtypes();
        },
        async getAllPtypes(){
            this.isLoading = true;
            try {
                const resp = await axios.get('/sadmin/ptypes',{
                   params:{
                       page: this.page,
                       per_page: this.itemsPerPage,
                       search: this.psearch,
                       sort_by: this.sort_by,
                       sort_order: this.sort_order,
                       status: this.status,
                   }
               })
                const allData = resp.data.ptypes;
                this.ptypes = allData.data;
                this.totalItems = allData.total;
                this.page = allData.current_page;
            } catch (e) {
                console.error("Failed to load productTypes", e);
            } finally {
                this.isLoading = false;
            }
        },
        editPtype(){
            this.upLoading = true;
            const uptype = {
                product_type_id:this.editedItem.product_type_id,
                product_type_name:this.editedItem.product_type_name,
                product_type_status:this.editedItem.product_type_status,
            }
            axios.post('/sadmin/ptype/update',uptype)
                .then((resp)=>{
                    this.editDialog = false;
                    this.getAllPtypes();
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    window.Toast.success('Type Updated Successfully');
                    this.upLoading = false;
                })
        },
        editItem(item){
           this.editedIndex = this.ptypes.indexOf(item);
           this.editedItem = Object.assign({},item)
            this.editDialog = true;
        },
        deleteItem(item){
            this.editedIndex = this.ptypes.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.deleteDialog = true;
        },
        addPtype(){
            this.adLoading = true;
            const nptype = {
                product_type_name:this.defaultItem.product_type_name,
                product_type_status:this.defaultItem.product_type_status,
            }
            axios.post('/sadmin/ptype/update',nptype)
                .then((resp)=>{
                    this.addDialog = false;
                    this.getAllPtypes();
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    window.Toast.success('Type Added Successfully');
                    this.defaultItem.product_type_name = '';
                    this.adLoading = false;
                })
        },
        deletePtype(){
            const deldata = {
                product_type_id: this.editedItem.product_type_id,
            }
            axios.post('/sadmin/ptype/delete',deldata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                   this.getAllPtypes();
                    this.deleteDialog = false;
                })
                .catch((err)=>{
                    window.Toast.error('Something Went Wrong')
                })
        },
        checkDuplicatePtypeName(name) {
            if (!name) return false;
            const nameLower = name.trim().toLowerCase();
            return this.ptypes.some((ptype, index) => {
                const isSameName = ptype.product_type_name.trim().toLowerCase() === nameLower;
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
