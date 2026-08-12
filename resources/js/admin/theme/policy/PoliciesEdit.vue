<template>
    <v-container class="policy-edit">
        <v-form v-model="pcuvalid" @submit.prevent="updatePolicy" >
            <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link :to="{name:'PoliciesList'}" icon variant="tonal" density="compact" color="primary">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        Edit {{ spolicy.policy_name }}
                    </h2>
                </v-col>
                <v-col cols="12" md="6" class="text-end">
                    <v-btn type="submit" variant="tonal" color="success" density="comfortable"
                           class="text-none" :loading="pcuLoading" :disabled="!pcuvalid || pcuLoading">
                        Update
                    </v-btn>
                    <v-btn :href="'https://'+domain+'/policies/'+spolicy.policy_slug" target="_blank"
                           variant="tonal" color="primary" density="comfortable" class="ms-1 text-none"
                           prepend-icon="mdi-eye">View Policy</v-btn>
                </v-col>
            </v-row>
            <v-row class="mt-0">
                <v-col cols="12" md="9">
                    <v-card elevation="0" class="border-sm">
                        <v-card-text>
                            <div class="mb-2">
                                <v-text-field v-model="spolicy.policy_name" variant="outlined" label="Policy Title"
                                              placeholder="Policy Title" :rules="titleRule"
                                              density="compact" persistent-placeholder counter persistent-counter></v-text-field>
                            </div>
                            <div>
                                <RichTextEditor v-model="spolicy.quillContent"/>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="3">
                    <v-card elevation="0" class="border-sm">
                        <v-card-title>Visibility</v-card-title>
                        <v-card-text>
                            <v-radio-group v-model="spolicy.policy_status" hide-details>
                                <v-radio label="Active" value="active"></v-radio>
                                <v-radio label="Inactive" value="inactive"></v-radio>
                            </v-radio-group>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-form>
    </v-container>
</template>
<script>
import {VFileUpload} from "vuetify/labs/components";
import axios from "axios";
import RichTextEditor from "@/components/RichTextEditor.vue";
export default {
    name:"PoliciesEdit",
    props:{
        policy_id:[Number,String]
    },
    components:{RichTextEditor, VFileUpload},
    data(){
        return{
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            pcuvalid:false,
            pcuLoading:false,
            spolicy:{
                policy_name:'',
                quillContent:'',
                policy_status:'',
                policy_slug:'',
            },
            Policies:[],
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
            ]
        }
    },
    created() {
        this.getPolicyByID();
    },
    methods:{
        getPolicyByID(){
            axios.get('/sadmin/policies/edit/'+this.policy_id)
                .then((resp)=>{
                    this.spolicy = resp.data.policy;
                    this.spolicy.quillContent = resp.data.policy.policy_description;
                })
        },
        updatePolicy(){
            this.pcuLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const upolicy = {
                policy_id:this.policy_id,
                policy_name:this.spolicy.policy_name,
                policy_description:this.spolicy.quillContent,
                policy_status:this.spolicy.policy_status,
            }
            axios.post('/sadmin/policies/update',upolicy,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getPolicyByID();
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.pcuLoading = false;
                })
        }
    }
}

</script>

<style>
.policy-edit .ql-editor {
    min-height: 175px;
    max-height: 100% !important;
}
</style>
