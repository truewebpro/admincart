<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6">
                <h2 class="text-h6">Shop Announcements</h2>
            </v-col>
            <v-col cols="12" md="6">
            </v-col>
            <v-col cols="12" md="12">
                <v-card>
                    <v-data-table :items="announcements" :headers="announcementHeader" :hide-default-footer="announcements.length < 20">
                        <template v-slot:item.actions="{item}">
                            <v-btn @click="editItem(item)" density="compact" color="info" variant="text" icon="mdi-pencil" class="me-1"/>
                            <v-btn @click="deleteItem(item)" density="compact" color="red" variant="text" icon="mdi-delete" class="ms-1"/>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
            <v-col cols="12" md="12" v-if="announcements.length < 2">
                <v-sheet class="font-weight-medium text-uppercase py-1" :style="{background:defaultItem.setting.background,color:defaultItem.setting.color}">
                    <marquee>{{defaultItem.title}}</marquee>
                </v-sheet>
                <v-card>
                    <v-card-title>Add New Announcement</v-card-title>
                    <v-card-text>
                        <v-form v-model="asvalid">
                            <v-text-field v-model="defaultItem.title" variant="outlined" density="compact" label="Title"
                                          :rules="titleRule" class="mb-2"></v-text-field>
                            <div class="d-flex ga-3">
                                <div>
                                    <h2 class="text-h6">Background Color</h2>
                                    <v-color-picker v-model="defaultItem.setting.background" mode="hexa" hide-canvas></v-color-picker>
                                </div>
                                <div>
                                    <h2 class="text-h6">Title Color</h2>
                                    <v-color-picker v-model="defaultItem.setting.color" mode="hexa" hide-canvas></v-color-picker>
                                </div>
                            </div>
                            <div class="d-flex ga-1">
                                <v-checkbox-btn v-model="defaultItem.status" label="Active" value="active" hide-details></v-checkbox-btn>
                                <v-checkbox-btn v-model="defaultItem.status" label="Inactive" value="inactive" hide-details></v-checkbox-btn>
                            </div>
                            <v-btn :disabled="!asvalid" @click="addAnnouncement" color="success" density="comfortable" class="mt-3">Submit</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="editDialog" max-width="700">
            <v-card>
                <v-card-text>
                    <v-form v-model="usvalid">
                        <v-text-field v-model="editedItem.title" variant="outlined" density="compact" label="Tag Name" :rules="titleRule"></v-text-field>
                        <div class="d-flex ga-3">
                            <div>
                                <h2 class="text-h6">Background Color</h2>
                                <v-color-picker v-model="editedItem.setting.background" mode="hexa" hide-canvas></v-color-picker>
                            </div>
                            <div>
                                <h2 class="text-h6">Title Color</h2>
                                <v-color-picker v-model="editedItem.setting.color" mode="hexa" hide-canvas></v-color-picker>
                            </div>
                        </div>
                        <div class="d-flex ga-1">
                            <v-checkbox-btn v-model="editedItem.status" label="Active" value="active" hide-details></v-checkbox-btn>
                            <v-checkbox-btn v-model="editedItem.status" label="Inactive" value="inactive" hide-details></v-checkbox-btn>
                        </div>
                        <div class="d-flex mt-3">
                            <v-spacer/>
                            <v-btn :disabled="!usvalid" @click="updateAnnouncement" color="success" density="comfortable">Update</v-btn>
                            <v-btn @click="editDialog = false" color="red" density="comfortable" class="ms-2">Cancel</v-btn>
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
                    <v-btn @click="deleteAnnouncement" color="success" density="comfortable" class="mt-3">Yes</v-btn>
                    <v-btn @click="deleteDialog = false" color="red" density="comfortable" class="mt-3">No</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>

</template>
<script>
import axios from "axios";

export default {
    name:"ShopAnnouncement",
    data(){
        return{
            domain:'https://'+this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            asvalid:false,
            usvalid:false,
            editDialog:false,
            deleteDialog:false,
            announcements:[],
            announcementHeader:[
                {title:'Title',key:'title'},
                {title:'Setting',value:'setting'},
                {title:'Status',value:'status'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                title:'',
                setting:{
                    color:'#ffffff',
                    background:"#f03e3e"
                },
                status:'inactive'
            },
            editedItem:{
                announcement_id:'',
                title:'',
                setting:{
                    color:'#ffffff',
                    background:"#f03e3e"
                },
                status:'active'
            },
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 20) || "Minimum 20 characters required",
                (v) => (v && v.length <= 80) || "Maximum 80 characters allowed",
            ]
        }
    },
    created() {
        this.getAllAnnouncements();
    },
    methods:{
        getAllAnnouncements(){
            axios.get('/sadmin/announcements')
                .then((resp)=>{
                    this.announcements = resp.data.announcements;
                })
        },
        addAnnouncement(){
            const adata = {
                title:this.defaultItem.title,
                setting:this.defaultItem.setting,
                status:this.defaultItem.status,
            }
            axios.post('/sadmin/announcement/update',adata)
                .then((resp)=>{
                    this.getAllAnnouncements();
                    window.Toast.success("Announcement Added Successfully");
                    this.defaultItem.title = '';
                })
        },
        updateAnnouncement(){
            const udata = {
                announcement_id:this.editedItem.announcement_id,
                title:this.editedItem.title,
                setting:this.editedItem.setting,
                status:this.editedItem.status,
            }
            axios.post('/api/sadmin/announcement/update',udata)
                .then((resp)=>{
                    this.getAllAnnouncements();
                    window.Toast.success("Announcement Updated Successfully");
                    this.editDialog = false;
                })
        },
        deleteAnnouncement(){
            const ddata = {
                mname:'delete',
                announcement_id:this.editedItem.announcement_id,
            }
            axios.post('/api/sadmin/announcement/update',ddata)
                .then((resp)=>{
                    this.getAllAnnouncements();
                    window.Toast.success(resp.data.message);
                    this.deleteDialog = false;
                })
        },
        editItem(item){
            this.editedIndex = this.announcements.indexOf(item);
            this.editedItem = Object.assign({},item);
            this.editDialog = true;
        },
        deleteItem(item){
            this.editedIndex = this.announcements.indexOf(item);
            this.editedItem = Object.assign({},item);
            this.deleteDialog = true;
        },
    }
}

</script>

<style scoped>

</style>
