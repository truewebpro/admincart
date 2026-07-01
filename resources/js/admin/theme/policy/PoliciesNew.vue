<template>
    <v-container class="policy-new">
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn link :to="{name:'PoliciesList'}" icon variant="tonal" density="compact">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    Add Policy
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn @click="addNewPolicy">Save</v-btn>
            </v-col>
        </v-row>
        <v-row class="mt-0">
            <v-col cols="12" md="9">
                <v-card elevation="0" class="border-sm">
                    <v-card-text>
                        <div class="mb-2">
                            <v-text-field v-model="policy_title" variant="outlined" label="Policy Title" placeholder="Policy Title"
                                          density="compact" persistent-placeholder counter persistent-counter></v-text-field>
                        </div>
                        <div>
                            <RichTextEditor v-model="quillContent"/>
                        </div>
                    </v-card-text>
                </v-card>
                <v-card elevation="0" class="border-sm mt-3">
                    <v-card-title>Search engine listing</v-card-title>
                    <v-card-subtitle>Add a title and description to see how this Policy post might appear in a search engine listing</v-card-subtitle>
                    <v-card-text>
                        <div class="font-weight-medium text-h6">{{shopName}}</div>
                        <div class="text-body-2 text-grey-darken-4 mb-2">{{domain}}</div>
                        <div class="font-weight-medium text-h5 text-blue-darken-2">Policy title</div>
                        <div class="text-body-1">Text of Policy</div>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-text>
                        <div class="mb-3">
                            <v-text-field v-model="meta_title" variant="outlined" label="Meta Title" placeholder="Policy about latest topic"
                                          density="compact" persistent-placeholder counter="70" persistent-counter></v-text-field>
                        </div>
                        <div class="mb-3">
                            <v-textarea v-model="meta_desc" variant="outlined" label="Meta Description" placeholder="Policy about latest topic"
                                        density="compact" persistent-placeholder counter="160" persistent-counter></v-textarea>
                        </div>
                        <div class="mb-3">
                            <v-text-field variant="outlined" label="URL handle" prefix="policies/" density="compact"
                                          persistent-placeholder counter="100" persistent-counter
                                          :hint="domain+'policy/'" persistent-hint></v-text-field>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card elevation="0" class="border-sm">
                    <v-card-title>Visibility</v-card-title>
                    <v-card-text>
                        <v-radio-group v-model="policy_status" hide-details>
                            <v-radio label="Visible" value="Visible"></v-radio>
                            <v-radio label="Hidden" value="Hidden"></v-radio>
                        </v-radio-group>
                    </v-card-text>
                </v-card>
                <v-card class="mt-3 border" elevation="0">
                    <v-card-title>Image</v-card-title>
                    <v-card-text>
                        <v-file-upload v-model="policy_image" density="compact" browse-text="Add Image"
                                       icon="mdi-upload"
                                       title="Add Image"
                        ></v-file-upload>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import {VFileUpload} from "vuetify/labs/components";
import RichTextEditor from "@/components/RichTextEditor.vue";
export default {
    name:"policiesNew",
    components:{RichTextEditor, VFileUpload},
    data(){
        return{
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            shopName:this.$store.state.shop.shop_name || 'ShopName?',
            Policies:[],
            policy_title:'',
            quillContent:'',
            policy_status:'Hidden',
            policy_image:null,
            policy_template:['Default'],
            policy_templates:['Default','Template 1','Template 2','Template 3'],
            meta_title:'',
            meta_desc:'',
        }
    },
    methods:{
        addNewPolicy(){
            const npolicy = {
                policy_title:this.policy_title,
                desc:this.quillContent,
                policy_status:this.policy_status,
                policy_image:this.policy_image,
                policy_template:this.policy_template,
                meta_title:this.meta_title,
                meta_desc:this.meta_desc,
            }
        },
    }
}

</script>

<style scoped>

</style>
