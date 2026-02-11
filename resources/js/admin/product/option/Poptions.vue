<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6">
                <span class="text-h6">Product Options</span></v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn class="text-none" size="small" color="grey-darken-4" @click="addDialog = true">Add Option</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="posearch" class="mx-1" variant="outlined" density="compact" clearable hide-details
                                      placeholder="Searching all Options"
                                      prepend-inner-icon="mdi-magnify"
                        ></v-text-field>
                    </div>
                    <div>
                        <v-data-table :items="filteredPoptions" :headers="poptionsHeaders" density="comfortable"
                                      hover :search="posearch" :custom-filter="customFilter" :loading="isLoading">
                            <template v-slot:item.option_name="{item}">
                                <div class="title d-flex align-center justify-space-between">
                                    <div class="text-decoration-none text-grey-darken-3">
                                        <span>{{item.option_name}}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:item.actions="{item}">
                                <v-btn variant="outlined" color="info" density="compact" @click="editItem(item)">Edit</v-btn>
                                <v-btn class="ms-1" v-if="item.usedcount < 1" variant="outlined" color="red" density="compact">Delete</v-btn>
                            </template>
                        </v-data-table>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-dialog max-width="400" v-model="editDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="upValid" @submit.prevent="editOption">
                                <div class="my-2">
                                    <v-text-field v-model="editedItem.option_name" variant="outlined" density="compact"
                                                  label="Option Name" :rules="ptitleRule"></v-text-field>
                                </div>
                                <div>
                                    <v-btn type="submit" :disabled="!upValid" variant="elevated" density="comfortable" color="success">Update Option</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="400" v-model="addDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="addValid" @submit.prevent="addOption">
                                <div class="my-2">
                                    <v-text-field v-model="defaultItem.option_name" variant="outlined" density="compact"
                                                  label="Option name" :rules="ptitleRule"></v-text-field>
                                </div>
                                <div>
                                    <v-btn type="submit" :disabled="!addValid" variant="elevated" density="comfortable" color="success">Add Option</v-btn>
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

export default {
    name:"Ptypes",
    data(){
        return{
            posearch:'',
            poptions:[],
            isLoading: false,
            upValid: false,
            addValid: false,
            status:"All",
            poptionstatus:[],
            poptionsHeaders:[
                {title:'ID',value:'option_id',width:60},
                {title:'Name',value:'option_name',maxWidth:375},
                {title:'Product Count',value:'usedcount',maxWidth:375},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                option_name: '',
            },
            editedItem:{
                option_id: '',
                option_name: '',
            },
            editDialog:false,
            addDialog:false,
            ptitleRule:[
                (v) => !!v || "Option name is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicateOptionName(v) || 'Option name already exists',
            ],
        }
    },
    mounted() {
        this.getAllPoptions();
    },
    created() {
        // this.getAllPoptions();
    },
    computed: {
        filteredPoptions() {
            // if (this.status === "All") return this.poptions;
            if (this.status === "Archived") return this.poptions.filter(p => p.option_status === "Archived");
            // return this.poptions.filter(p => p.option_status === this.status);
            return this.poptions.filter((p) => {
                const matchStatus = this.status === "All" || p.option_status === this.status;
                return matchStatus;
            });
        }
    },
    methods:{
        mergeProps,
        customFilter(value, search, item) {
            if (!search) return true;
            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');
            return searchTerms.every(term => title.includes(term));
        },
        getAllPoptions(){
            this.isLoading = true;
            axios.get('/sadmin/poptions')
                .then((resp)=>{
                    this.poptions = resp.data.poptions;
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        editOption(){
            const uoption = {
                option_id:this.editedItem.option_id,
                option_name:this.editedItem.option_name,
            }
            axios.post('/sadmin/poption/update',uoption)
                .then((resp)=>{
                    this.editDialog = false;
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getAllPoptions();
                        })
                    window.Toast.success(resp.data.message)
                })
                .catch((error)=>{
                    window.Toast.error(error.message)
                })
        },
        editItem(item){
            this.editedIndex = this.poptions.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.editDialog = true;
        },
        addOption(){
            const noption = {
                option_name:this.defaultItem.option_name,
            }
            axios.post('/sadmin/poption/update',noption)
                .then((resp)=>{
                    this.addDialog = false;
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getAllPoptions();
                        })
                    window.Toast.success(resp.data.message);
                    this.defaultItem.option_name = '';
                })
        },
        checkDuplicateOptionName(name) {
            const nameLower = name.trim().toLowerCase();

            return this.poptions.some((poption, index) => {
                const isSameName = poption.option_name.trim().toLowerCase() === nameLower;
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
