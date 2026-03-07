<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6">
                <span class="text-h6">Product Types</span></v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn class="text-none" size="small" color="grey-darken-4" @click="addDialog = true">Add Type</v-btn>
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
                    <v-data-table :items="filteredPtypes" :headers="ptypesHeaders" density="comfortable" items-per-page="20"
                                  hover :search="psearch" :custom-filter="customFilter" :loading="isLoading">
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
                            <v-btn variant="outlined" color="info" density="compact" @click="editItem(item)">Edit</v-btn>
                            <v-btn variant="outlined" color="red" density="compact"
                                   @click="deleteItem(item)" class="ms-2">Delete</v-btn>
                        </template>
                    </v-data-table>
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
                            <div v-if="editedItem.pcount > 0">products associated count is {{editedItem.pcount}}</div>
                            <div v-if="editedItem.pcount > 0">Can't be deleted,<br/> Please change the product type of products associated</div>
                           <div v-else>Are you sure to delete type {{editedItem.product_type_name}}</div>
                            <div class="d-flex mt-3 justify-space-around">
                                <v-btn :disabled="editedItem.pcount > 0" @click.prevent="deletePtype" variant="elevated" color="green" density="compact">Yes</v-btn>
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
import Toast from "vue-toastification";

export default {
    name:"Ptypes",
    data(){
        return{
            psearch:'',
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
                {title:'Name',value:'product_type_name',maxWidth:375},
                {title:'Status',value:'product_type_status'},
                {title:'Product Count',key:'pcount'},
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
                pcount: 0,
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
    mounted() {
        this.getAllPtypes();
        this.$store.dispatch('fetchPtypes');
    },
    computed: {
        filteredPtypes() {
            if (this.status === "Archived") return this.ptypes.filter(p => p.product_type_status === "Archived");

            let filtered =  this.ptypes.filter((p) => {
                const matchStatus = this.status === "All" || p.product_type_status === this.status;
                return matchStatus;
            });

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
        }
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
        customFilter(value, search, item) {
            if (!search) return true;
            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');
            return searchTerms.every(term => title.includes(term));
        },
        async getAllPtypes(){
            this.isLoading = true;
            try {
                await this.$store.dispatch('fetchPtypes');
                this.ptypes = this.$store.state.productTypes;
                let statuses = [...new Set(this.ptypes.map(item => item.product_type_status))];
                statuses = statuses.filter(status => status !== "Archived");
                this.pstatuses = ["All", ...statuses, "Archived"];
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
                    this.$store.commit('UPDATE_PRODUCT_TYPE',resp.data.ptype)
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getAllPtypes();
                        })
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
                    this.$store.commit('ADD_PRODUCT_TYPE',resp.data.ptype)
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getAllPtypes();
                        })
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
                    this.$store.commit('DELETE_PRODUCT_TYPE',deldata.product_type_id)
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getAllPtypes();
                        })
                    this.deleteDialog = false;
                })
                .catch((err)=>{
                    window.Toast.error('Something Went Wrong')
                })
        },
        checkDuplicatePtypeName(name) {
            const nameLower = name.trim().toLowerCase();

            return this.ptypes.some((ptype, index) => {
                const isSameName = ptype.product_type_name.trim().toLowerCase() === nameLower;
                // In case of edit, allow if it's the same index
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
