<template>
    <div>
        <v-container class="page-new">
            <v-form v-model="pavalid" @submit.prevent="addNewPage">
                <v-row class="position-sticky top-0 bg-grey-lighten-3" style="z-index: 99">
                    <v-col cols="12" md="6">
                        <h2 class="text-h6">
                            <v-btn link :to="{name:'PagesList'}" icon variant="tonal" density="compact">
                                <v-icon>mdi-arrow-left</v-icon>
                            </v-btn>
                            Add Page
                        </h2>
                    </v-col>
                    <v-col cols="12" md="6" class="text-end">
                        <v-btn :disabled="!pavalid" type="submit" color="success" density="compact">Save</v-btn>
                    </v-col>
                </v-row>
                <v-row class="mt-0">
                    <v-col cols="12" md="9">
                        <v-card elevation="0" class="border-sm">
                            <v-card-text>
                                <div class="mb-2">
                                    <v-text-field v-model="page_title" :rules="titleRule"
                                                  variant="outlined" label="Page Title" placeholder="Page Title"
                                                  density="compact" persistent-placeholder counter persistent-counter></v-text-field>
                                </div>
                                <div>
                                    <RichTextEditor v-model="quillContent"/>
                                </div>
                            </v-card-text>
                        </v-card>
                        <v-card elevation="0" class="border-sm mt-3">
                            <v-card-title>Search engine listing</v-card-title>
                            <v-card-subtitle>Add a title and description to see how this Page post might appear in a search engine listing</v-card-subtitle>
                            <v-card-text>
                                <div class="font-weight-medium text-h6">{{this.$store.getters.currentShopName}}</div>
                                <div class="text-body-2 text-grey-darken-4 mb-2">{{domain}}</div>
                                <div class="font-weight-medium text-h5 text-blue-darken-2">Page title</div>
                                <div class="text-body-1">Text of Page</div>
                            </v-card-text>
                            <v-divider></v-divider>
                            <v-card-text>
                                <div class="mb-3">
                                    <v-text-field v-model="meta_title" variant="outlined" label="Meta Title" placeholder="Page about latest topic"
                                                  density="compact" persistent-placeholder counter="70" persistent-counter></v-text-field>
                                </div>
                                <div class="mb-3">
                                    <v-textarea v-model="meta_description" variant="outlined" label="Meta Description" placeholder="Page about latest topic"
                                                density="compact" persistent-placeholder counter="160" persistent-counter></v-textarea>
                                </div>
                                <div class="mb-3">
                                    <v-text-field variant="outlined" label="URL handle" prefix="pages/" density="compact"
                                                  persistent-placeholder counter="100" persistent-counter
                                                  :hint="('https://'+domain+'/pages/'+ page_slug)" persistent-hint></v-text-field>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-card elevation="0" class="border-sm">
                            <v-card-title>Visibility</v-card-title>
                            <v-card-text>
                                <v-radio-group v-model="page_status" hide-details>
                                    <v-radio label="Active" value="active"></v-radio>
                                    <v-radio label="Inactive" value="inactive"></v-radio>
                                </v-radio-group>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-form>
        </v-container>
    </div>
</template>
<script>
import axios from "axios";
import RichTextEditor from "@/components/RichTextEditor.vue";
export default {
    name:"pagesNew",
    components: {RichTextEditor},
    data(){
        return{
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            pavalid:false,
            page_title:'',
            quillContent:'',
            page_status:'active',
            meta_title:'',
            meta_description:'',
            page_slug:'',
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
            ]
        }
    },
    watch:{
        'page_title'(newSlug){
            this.page_slug = newSlug
                .toLowerCase()
                .trim()
                .replace(/[\s\W-]+/g,'-')
                .replace(/^-+|-+$/g,'')
        }
    },
    methods:{
        addNewPage(){
            const adata = {
                page_title:this.page_title,
                page_description:this.quillContent,
                page_status:this.page_status,
                meta_title:this.meta_title,
                meta_description:this.meta_description,
                page_slug:this.page_slug,
            }
            axios.post('/sadmin/page/add/new',adata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.$router.push({name:'PagesList'})
                })
        },
    }
}

</script>

<style scoped>

</style>
