<template>
    <v-container>
        <v-row dense>
            <v-col cols="6" md="6">
                <span class="text-h6">Tags</span></v-col>
            <v-col cols="6" md="6" class="text-end">
                <v-btn class="text-none" size="small" color="grey-darken-4" @click="addDialog = true">Add Tag</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="tsearch" class="w-50 me-2" variant="outlined" density="compact" clearable hide-details
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
                            <v-list density="compact">
                                <v-list-item title="Tag A-Z" />
                                <v-list-item title="Tag Z-A" />
                                <v-list-item title="Created ASC" />
                                <v-list-item title="Created DSC" />
                                <v-list-item title="Updated ASC" />
                                <v-list-item title="Updated DSC" />
                            </v-list>
                        </v-menu>
                    </div>
                    <div>
                        <v-data-table-server
                            v-model:page="page"
                            v-model:items-per-page="itemsPerPage"
                            v-model:sort-by="sortBy"
                            :items="tags"
                            :headers="tagsHeaders"
                            :loading="isLoading"
                            hover
                            :items-length="totalItems"
                            @update:options="loadItems"
                            density="comfortable"
                            mobileBreakpoint="sm"
                        >
                            <template v-slot:item.tag_name="{item}">
                                <div class="title d-flex align-center justify-space-between">
                                    <div class="text-decoration-none text-grey-darken-3">
                                        <span>{{item.tag_name}}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:item.tag_status="{item}">
                                <v-chip size="small" class="bg-light-green-accent-1 text-black"  v-if="item.tag_status === 'Active'">{{item.tag_status}}</v-chip>
                                <v-chip size="small" v-else>{{item.tag_status}}</v-chip>
                            </template>
                            <template v-slot:item.actions="{item}">
                                <v-btn variant="outlined" color="info" density="compact" @click="editItem(item)">Edit</v-btn>
                                <v-btn variant="outlined" color="red" density="compact" @click="deleteItem(item)" class="ms-2">Delete</v-btn>
                            </template>
                        </v-data-table-server>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-dialog max-width="400" v-model="editDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="upValid" @submit.prevent="editTag">
                                <div class="my-2">
                                    <v-text-field v-model="editedItem.tag_name" variant="outlined" density="compact"
                                                  label="Tag name" :rules="ttitleRule"></v-text-field>
                                </div>
                                <div class="mb-2">
                                    <v-select v-model="editedItem.tag_status" :items="tagstatus" variant="outlined" density="compact"
                                              label="Status"></v-select>
                                </div>
                                <div class="d-flex ga-4">
                                    <v-spacer/>
                                    <v-btn type="submit" :disabled="!upValid" variant="elevated" density="comfortable" color="success">Update</v-btn>
                                    <v-btn @click="editDialog = false" variant="elevated" density="comfortable" color="red">Cancel</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="400" v-model="addDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="adValid" @submit.prevent="addTag">
                                <div class="my-2">
                                    <v-text-field v-model="defaultItem.tag_name" variant="outlined" density="compact"
                                                  label="Tag name" :rules="ttitleRule"></v-text-field>
                                </div>
                                <div class="mb-2">
                                    <v-select v-model="defaultItem.tag_status" :items="tagstatus" variant="outlined" density="compact"
                                              label="Status"></v-select>
                                </div>
                                <div>
                                    <v-btn type="submit" :disabled="!adValid" variant="elevated" density="comfortable" color="success">Add</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="400" v-model="deleteDialog">
                    <v-card>
                        <v-card-text class="text-center">
                            <v-form @submit.prevent="deleteTag">
                                <div class="my-2">
                                    <h3 v-if="editedItem.pcount > 0">Products associated count is <span class="text-red">{{editedItem.pcount}}</span></h3>
                                    <h4 v-if="editedItem.pcount > 0">Can't be deleted,<br/> Please change the tag of products associated</h4>
                                    <h3 v-else>Are you sure to delete tag <br/> {{editedItem.tag_name}}</h3>
                                </div>
                                <div class="d-flex ga-3">
                                    <v-spacer/>
                                    <v-btn type="submit" :disabled="editedItem.pcount > 0" variant="elevated" density="comfortable" color="success">Yes</v-btn>
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
import axios from "axios";
import {mergeProps} from "vue";
import debounce from "lodash/debounce";

export default {
    name:"Tags",
    data(){
        return{
            tsearch:'',
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by: '',
            sort_order: 'desc',
            sortBy: [],
            tags:[],
            isLoading: false,
            adValid: false,
            upValid: false,
            status:"All",
            tstatuses:[],
            tagstatus:['Active','Inactive'],
            atags:[],
            tagsHeaders:[
                {title:'ID',value:'tag_id',width:60},
                {title:'Name',key:'tag_name',maxWidth:375},
                {title:'Status',value:'tag_status'},
                {title:'Product Count',key:'pcount'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                tag_name: '',
                tag_status: 'Active',
            },
            editedItem:{
                tag_id: '',
                tag_name: '',
                tag_status: '',
            },
            deleteDialog:false,
            editDialog:false,
            addDialog:false,
            ttitleRule:[
                (v) => !!v || "Tag Name is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicateTagName(v) || 'Tag name already exists',
            ],
        }
    },
    watch:{
        tsearch() {
            this.debouncedSearch();
        },
        status() {
            this.page = 1;
            this.getAllTags();
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getAllTags();
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
                this.sort_by = 'tag_id';
                this.sort_order = 'desc';
            }
            this.getAllTags();
        },
        setSort(key) {
            this.sortBy = [{
                key: key,
                order: this.sort_order || 'asc'
            }];
            this.sort_by = key;
            this.page = 1;
            this.getAllTags();
        },
        setDirection(direction) {
            this.sort_order = direction;
            this.sortBy = [{
                key: this.sort_by || 'tag_name',
                order: direction
            }];
            this.page = 1;
            this.getAllTags();
        },
        async getAllTags(){
            this.isLoading = true;
            try {
                const resp = await axios.get('/sadmin/tags',{
                    params:{
                        page: this.page,
                        per_page: this.itemsPerPage,
                        search: this.tsearch,
                        sort_by: this.sort_by,
                        sort_order: this.sort_order,
                        status: this.status,
                    }
                })
                const allData = resp.data.tags;
                this.tags = allData.data;
                this.totalItems = allData.total;
                this.page = allData.current_page;
            } catch (e) {
                console.error("Failed to load tags", e);
            } finally {
                this.isLoading = false;
            }
        },
        editTag(){
            const utag = {
                tag_id:this.editedItem.tag_id,
                tag_name:this.editedItem.tag_name,
                tag_status:this.editedItem.tag_status,
            }
            axios.post('/sadmin/tag/update',utag)
                .then((resp)=>{
                    this.editDialog = false;
                    this.getAllTags();
                    window.Toast.success('Tag updated')
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
        },
        editItem(item){
            this.editedIndex = this.tags.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.editDialog = true;
        },
        deleteItem(item){
            this.editedIndex = this.tags.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.deleteDialog = true;
        },
        deleteTag(){
            const dtag = {
                tag_id:this.editedItem.tag_id,
            }
            axios.post('/sadmin/tag/delete',dtag)
                .then((resp)=>{
                    this.deleteDialog = false;
                    this.getAllTags();
                    window.Toast.success('Tag deleted')
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
        },
        addTag(){
            const ntag = {
                tag_name:this.defaultItem.tag_name,
                tag_status:this.defaultItem.tag_status,
            }
            axios.post('/sadmin/tag/update',ntag)
                .then((resp)=>{
                    this.addDialog = false;
                    this.getAllTags();
                    window.Toast.success('Tag added')
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
        },
        checkDuplicateTagName(name) {
            const nameLower = name.trim().toLowerCase();
            return this.tags.some((tag, index) => {
                const isSameName = tag.tag_name.trim().toLowerCase() === nameLower;
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
