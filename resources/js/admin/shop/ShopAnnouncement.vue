<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-item>
                        <template #title>Search Announcement</template>
                        <template #subtitle>
                            <v-chip :color="announcement?.status === 'active' ? 'green' : 'red'" density="compact">
                            {{ announcement?.status }}
                            </v-chip>
                        </template>
                        <template #append>
                            <div v-if="announcement !== null">
                                <v-btn @click="editItem" density="compact" color="info" icon="mdi-pencil" class="me-1"/>
                                <v-btn @click="deleteItem"  density="compact" color="red" icon="mdi-delete" class="ms-1"/>
                            </div>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
            <v-col cols="12" md="12" v-if="announcement === null || announcement?.announcement_id === null">
                <v-card>
                    <v-card-title>Add New Announcement</v-card-title>
                    <v-card-text>
                        <v-form v-model="asvalid">
                            <v-text-field v-model="defaultItem.title" variant="outlined" density="compact" label="Title"
                                          :rules="titleRule" class="mb-2"></v-text-field>
                            <div class="d-flex ga-1">
                                <div class="w-50">
                                    <v-color-input label="Background Color" v-model="defaultItem.setting.background" pip-location="prepend-inner" variant="outlined" density="compact" mode="hexa" hide-canvas></v-color-input>
                                </div>
                                <div class="w-50">
                                    <v-color-input label="Title Color" v-model="defaultItem.setting.color" pip-location="prepend-inner" variant="outlined" density="compact" mode="hexa" hide-canvas></v-color-input>
                                </div>
                            </div>
                            <div class="d-flex ga-1">
                                <v-checkbox-btn v-model="defaultItem.status" label="Active" value="active" hide-details></v-checkbox-btn>
                                <v-checkbox-btn v-model="defaultItem.status" label="Inactive" value="inactive" hide-details></v-checkbox-btn>
                            </div>
                            <v-btn :disabled="!asvalid" @click="addAnnouncement" color="success" density="comfortable"
                                   class="mt-3" prependIcon="mdi-plus">
                                Save
                            </v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="12" v-else>
                <v-sheet class="font-weight-medium text-uppercase py-1" :style="{background:announcement.setting.background,color:announcement.setting.color}">
                    <marquee>{{announcement.title}}</marquee>
                </v-sheet>
            </v-col>
        </v-row>
        <v-dialog v-model="editDialog" max-width="700">
            <v-card>
                <v-card-text>
                    <v-form v-model="usvalid">
                        <v-text-field v-model="announcement.title" variant="outlined" density="compact" label="Tag Name" :rules="titleRule"></v-text-field>
                        <div class="d-flex ga-1">
                            <div class="w-50">
                                <v-color-input label="Background Color" color-pip pip-location="prepend-inner" variant="outlined" density="compact" v-model="announcement.setting.background" mode="hexa" hide-canvas></v-color-input>
                            </div>
                            <div class="w-50">
                                <v-color-input label="Title Color" color-pip pip-variant="flat" pip-location="prepend-inner" variant="outlined" density="compact" v-model="announcement.setting.color" mode="hexa" hide-canvas></v-color-input>
                            </div>
                        </div>
                        <div class="d-flex ga-1">
                            <v-checkbox-btn v-model="announcement.status" label="Active" value="active" hide-details></v-checkbox-btn>
                            <v-checkbox-btn v-model="announcement.status" label="Inactive" value="inactive" hide-details></v-checkbox-btn>
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
                    <h3>{{ announcement.title }}</h3>
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
import { VColorInput } from 'vuetify/labs/VColorInput';
import axios from "axios";

export default {
    name:"ShopAnnouncement",
    components:{VColorInput},
    data(){
        return{
            domain:'https://'+this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            asvalid:false,
            usvalid:false,
            editDialog:false,
            deleteDialog:false,
            announcements:[],
            announcement:{
                announcement_id:null,
                title:'',
                setting:{
                    color:'#ffffff',
                    background:"#f03e3e"
                },
                status:'active'
            },
            announcementHeader:[
                {title:'Title',key:'title'},
                {title:'Setting',value:'setting'},
                {title:'Status',value:'status'},
                {title:'Actions',value:'actions'},
            ],
            defaultItem:{
                title:'',
                setting:{
                    color:'#ffffff',
                    background:"#f03e3e"
                },
                status:'inactive'
            },
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 20) || "Minimum 20 characters required",
                (v) => (v && v.length <= 80) || "Maximum 80 characters allowed",
            ]
        }
    },
    mounted() {
        this.getAllAnnouncements()
    },
    methods:{
        getAllAnnouncements(){
            axios.get('/sadmin/announcements')
                .then((resp)=>{
                    const respData = resp.data;
                    this.announcement = respData.announcement || null;
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
                announcement_id:this.announcement.announcement_id,
                title:this.announcement.title,
                setting:this.announcement.setting,
                status:this.announcement.status,
            }
            axios.post('/sadmin/announcement/update',udata)
                .then((resp)=>{
                    this.getAllAnnouncements();
                    window.Toast.success("Announcement Updated Successfully");
                    this.editDialog = false;
                })
        },
        deleteAnnouncement(){
            const ddata = {
                mname:'delete',
                announcement_id:this.announcement.announcement_id,
            }
            axios.post('/sadmin/announcement/update',ddata)
                .then((resp)=>{
                    this.getAllAnnouncements();
                    window.Toast.success(resp.data.message);
                    this.deleteDialog = false;
                })
        },
        editItem(){
            this.editDialog = true;
        },
        deleteItem(){
            this.deleteDialog = true;
        },
    }
}

</script>

<style scoped>

</style>
