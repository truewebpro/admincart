<template>
    <v-container class="pa-1">
      <v-row dense>
        <v-col cols="12" md="6">
            <h2 class="text-h6">Search Tags</h2>
        </v-col>
        <v-col cols="12" md="6">
        </v-col>
        <v-col cols="12" md="8">
            <v-card>
                <v-data-table :items="stags" :headers="stagHeader" :hide-default-footer="stags.length < 20">
                    <template v-slot:item.actions="{item}">
                        <v-btn @click="editItem(item)" density="compact" color="info" variant="text" icon="mdi-pencil" class="me-1"/>
                        <v-btn @click="deleteItem(item)" density="compact" color="red" variant="text" icon="mdi-delete" class="ms-1"/>
                    </template>
                </v-data-table>
            </v-card>
        </v-col>
        <v-col cols="12" md="4">
            <v-card>
                <v-card-title>Add New Tag</v-card-title>
              <v-card-text>
                  <v-form v-model="asvalid">
                      <v-text-field v-model="defaultItem.title" variant="outlined" density="compact" label="Tag Name"
                                    :rules="titleRule" class="mb-2"></v-text-field>
<!--                      <v-text-field v-model="defaultItem.link" variant="outlined" density="compact" label="Link"-->
<!--                                    :hint="domain+'/'+defaultItem.link" persistent-hint persistent-placeholder class="mb-2"></v-text-field>-->
                      <v-autocomplete v-model="defaultItem.link" density="compact"  label="Link"
                                      :items="alinks" item-title="name" item-value="link"
                                      variant="outlined" clearable class="mb-2"
                                      :hint="domain+'/'+defaultItem.link" persistent-hint persistent-placeholder>
                          <template v-slot:item="{ props, item }">
                              <v-list-item density="compact"
                                  v-bind="props"
                                  :subtitle="item.raw.ltype"
                                  :title="item.raw.name"
                              ></v-list-item>
                          </template>
                      </v-autocomplete>
                      <div class="d-flex ga-1">
                          <v-checkbox-btn v-model="defaultItem.status" label="Active" value="active" hide-details></v-checkbox-btn>
                          <v-checkbox-btn v-model="defaultItem.status" label="Inactive" value="inactive" hide-details></v-checkbox-btn>
                      </div>
                      <v-btn :disabled="!asvalid" @click="addSearchTag" color="success" density="comfortable" class="mt-3">Submit</v-btn>
                  </v-form>
              </v-card-text>
            </v-card>
        </v-col>
    </v-row>
        <v-dialog v-model="editDialog" max-width="400">
            <v-card>
                <v-card-text>
                    <v-form v-model="usvalid">
                        <v-text-field v-model="editedItem.title" variant="outlined" density="compact" label="Tag Name" :rules="titleRule"></v-text-field>
<!--                        <v-text-field v-model="editedItem.link" variant="outlined" density="compact" label="Link"-->
<!--                                      :hint="domain+'/'+editedItem.link" persistent-hint></v-text-field>-->
                        <v-autocomplete v-model="editedItem.link" density="compact"  label="Link"
                                        :items="alinks" item-title="name" item-value="link"
                                        variant="outlined" clearable class="mb-2"
                                        :hint="domain+'/'+editedItem.link" persistent-hint persistent-placeholder>
                            <template v-slot:item="{ props, item }">
                                <v-list-item density="compact"
                                             v-bind="props"
                                             :subtitle="item.raw.ltype"
                                             :title="item.raw.name"
                                ></v-list-item>
                            </template>
                        </v-autocomplete>
                        <div class="d-flex ga-1">
                            <v-checkbox-btn v-model="editedItem.status" label="Active" value="active" hide-details></v-checkbox-btn>
                            <v-checkbox-btn v-model="editedItem.status" label="Inactive" value="inactive" hide-details></v-checkbox-btn>
                        </div>
                        <div class="d-flex ga-3 mt-3">
                            <v-spacer/>
                            <v-btn :disabled="!usvalid" @click="updateSearchTag" color="success" density="comfortable">Update</v-btn>
                            <v-btn @click="editDialog = false" color="red" density="comfortable">Cancel</v-btn>
                        </div>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-dialog>
        <v-dialog v-model="deleteDialog" max-width="300">
            <v-card>
                <v-card-text class="text-center">
                    <h2>Are you sure delete?</h2>
                    <h3>{{ editedItem.title }}</h3>
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn @click="deleteSearchTag" color="success" density="comfortable" class="mt-3">Yes</v-btn>
                    <v-btn @click="deleteDialog = false" color="red" density="comfortable" class="mt-3">No</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>

</template>
<script>
import axios from "axios";

export default {
    name:"ShopSearchTags",
    data(){
        return{
            alinks:[],
            domain:'https://'+this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            asvalid:false,
            usvalid:false,
            editDialog:false,
            deleteDialog:false,
            stags:[],
            stagHeader:[
                {title:'Title',key:'title'},
                {title:'Link',value:'link'},
                {title:'Status',value:'status'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                title:'',
                link:'collections',
                status:'active'
            },
            editedItem:{
                stag_id:'',
                title:'',
                link:'',
                status:'active'
            },
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 3) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicateTagName(v) || 'Tag name already exists',
            ]
        }
    },
    created() {
        this.getSearchTags();
        this.getAllLinks()
    },
    methods:{
        getAllLinks(){
            axios.get('/sadmin/search/alinks')
                .then((resp)=>{
                    this.alinks =  resp.data.alinks;
                });
        },
        getSearchTags(){
            axios.get('/sadmin/searchtags')
                .then((resp)=>{
                    this.stags = resp.data.searchtags;
                })
        },
        addSearchTag(){
            const adata = {
                title:this.defaultItem.title,
                link:this.defaultItem.link,
                status:this.defaultItem.status,
            }
            axios.post('/sadmin/searchtag/update',adata)
                .then((resp)=>{
                    this.getSearchTags();
                    window.Toast.success("Tag Added Successfully");
                    this.defaultItem.title = '';
                    this.defaultItem.link = '';
                })
        },
        updateSearchTag(){
            const udata = {
                stag_id:this.editedItem.stag_id,
                title:this.editedItem.title,
                link:this.editedItem.link,
                status:this.editedItem.status,
            }
            axios.post('/sadmin/searchtag/update',udata)
                .then((resp)=>{
                    this.getSearchTags();
                    window.Toast.success("Tag Updated Successfully");
                    this.editDialog = false;
                })
        },
        deleteSearchTag(){
            const ddata = {
                mname:'delete',
                stag_id:this.editedItem.stag_id,
            }
            axios.post('/sadmin/searchtag/update',ddata)
                .then((resp)=>{
                    this.getSearchTags();
                    window.Toast.success(resp.data.message);
                    this.deleteDialog = false;
                })
        },
        editItem(item){
            this.editedIndex = this.stags.indexOf(item);
            this.editedItem = Object.assign({},item);
            this.editDialog = true;
        },
        deleteItem(item){
            this.editedIndex = this.stags.indexOf(item);
            this.editedItem = Object.assign({},item);
            this.deleteDialog = true;
        },
        checkDuplicateTagName(name) {
            const nameLower = name.trim().toLowerCase();
            return this.stags.some((stag, index) => {
                const isSameName = stag.title.trim().toLowerCase() === nameLower;
                if (this.editedIndex !== -1 && index === this.editedIndex) return false;
                return isSameName;
            });
        }
    }
}

</script>

<style scoped>

</style>
