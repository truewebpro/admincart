<template>
    <v-container class="pages-edit">
        <v-form v-model="pavalid" @submit.prevent="updatePage">
            <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link :to="{name:'PagesList'}" icon variant="tonal" density="compact">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        Edit {{ page.page_title }}
                    </h2>
                </v-col>
                <v-col cols="12" md="6" class="text-end">
                    <v-btn type="submit" variant="elevated" color="grey-darken-4" density="compact"
                           class="text-none" :loading="paLoading" :disabled="!pavalid || paLoading">
                        Update
                    </v-btn>
                    <v-btn :href="'https://'+domain+'/pages/'+page.page_slug" target="_blank"
                           variant="outlined" color="grey-darken-4" density="compact" class="mx-1 text-none"
                           prepend-icon="mdi-eye">View Page</v-btn>
                    <v-menu>
                        <template v-slot:activator="{props}">
                            <v-btn v-bind="props" variant="outlined" class="text-none me-2" append-icon="mdi-chevron-down"
                                   density="compact">More Actions</v-btn>
                        </template>
                        <v-list nav density="compact">
                            <v-list-item base-color="error" @click="perdeletePage">
                                <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Page</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                </v-col>
            </v-row>
            <v-tabs v-model="ptab" density="compact" color="primary" selectedClass="bg-grey-lighten-3"
                    bgColor="white" sliderColor="red"
                    class="my-2">
                <v-tab value="general">Page</v-tab>
                <v-tab value="content">Sections</v-tab>
                <v-tab value="faqs">FAQ's</v-tab>
            </v-tabs>
            <v-window v-model="ptab">
                <v-window-item value="general">
                    <v-row class="mt-0">
                        <v-col cols="12" md="9">
                            <v-card elevation="0" class="border-sm">
                                <v-card-text>
                                    <div class="mb-2">
                                        <v-text-field v-model="page.page_title" variant="outlined" label="Policy Title"
                                                      placeholder="Page Title" :rules="titleRule"
                                                      density="compact" persistent-placeholder counter persistent-counter></v-text-field>
                                    </div>
                                    <div>
                                        <RichTextEditor v-model="page.quillContent"/>
                                    </div>
                                </v-card-text>
                            </v-card>
                            <v-card elevation="0" class="border-sm mt-3">
                                <v-card-title>
                                    Search engine listing
                                </v-card-title>
                                <v-card-subtitle>Add a title and description to see how this Page post might appear in a search engine listing</v-card-subtitle>
                                <v-card-text>
                                    <div class="font-weight-medium text-h6">{{this.$store.getters.currentShopName}}</div>
                                    <div class="text-body-2 text-grey-darken-4 mb-2">{{domain}}</div>
                                    <div class="font-weight-medium text-h5 text-blue-darken-2">{{page.meta_title || page.page_title}}</div>
                                    <div class="text-body-1">{{page.meta_description}}</div>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <div class="mb-3">
                                        <v-text-field v-model="page.meta_title" variant="outlined" label="Meta Title" :placeholder="page.page_title"
                                                      density="compact" persistent-placeholder counter="70" persistent-counter></v-text-field>
                                    </div>
                                    <div class="mb-3">
                                        <v-textarea v-model="page.meta_description" variant="outlined" label="Meta Description" :placeholder="page.page_title"
                                                    density="compact" persistent-placeholder counter="160" persistent-counter></v-textarea>
                                    </div>
                                    <div class="mb-3">
                                        <v-text-field v-model="page.page_slug" variant="outlined" label="URL handle" prefix="/pages/" density="compact"
                                                      persistent-placeholder counter="100" persistent-counter
                                                      :hint="('https://'+domain+'/pages/'+page.page_slug)" persistent-hint></v-text-field>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card elevation="0" class="border-sm">
                                <v-card-title>Visibility</v-card-title>
                                <v-card-text>
                                    <v-radio-group v-model="page.page_status" hide-details>
                                        <v-radio label="Active" value="active"></v-radio>
                                        <v-radio label="Inactive" value="inactive"></v-radio>
                                    </v-radio-group>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
                <v-window-item value="content">
                    <PageSections
                        :sections="sections"
                        :stypes="stypes"
                        :page_id="page_id"
                        :allProducts="allProducts"
                        :allCategories="allCategories"
                        :allBanners="allBanners"
                        :allBrands="allBrands"
                        :alinks="alinks"
                        :cdn="cdn"
                        @refresh="getPageById"
                    />
                </v-window-item>
                <v-window-item value="faqs">
                    <PageFaqs
                        :page_id="page_id"
                        :faqs="faqs"
                        @refresh-faqs="getPageById"
                    />
                </v-window-item>
            </v-window>
        </v-form>
    </v-container>
</template>
<script>
import axios from "axios";
import PageFaqs from "@/admin/theme/page/PageFaqs.vue";
import PageSections from "@/admin/theme/page/PageSections.vue";
import RichTextEditor from "@/components/RichTextEditor.vue";

export default {
    name:"PagesEdit",
    components: {
        RichTextEditor,
        PageSections,
        PageFaqs,
    },
    props:{
        page_id:[Number,String]
    },
    computed:{
        alinks(){
            return this.$store.state.alinks;
        },
    },
    async mounted(){
        this.$store.dispatch('fetchAlinks');
    },
    data(){
        return{
            ptab:'general',
            stypes:[],
            sections:[],
            allProducts: [],
            allBrands:[],
            allCategories:[],
            allBanners: [],
            faqs:[],
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            pavalid:false,
            paLoading:false,
            page:{
                page_title:'',
                quillContent:'',
                page_status:'',
                page_slug:'',
                meta_title:'',
                meta_description:'',
                og_image:'',
            },
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
            ]
        }
    },
    created() {
        this.getPageById();
    },
    methods:{
        getPageById(){
            axios.get('/sadmin/page/edit/'+this.page_id)
                .then((resp)=>{
                    this.page = resp.data.page;
                    this.page.quillContent = resp.data.page.page_description;
                    this.stypes = resp.data.stypes;
                    this.sections = resp.data.page.sections;
                    this.allCategories = resp.data.categories || [];
                    this.allBrands = resp.data.brands || [];
                    this.faqs = resp.data.page.faqs || [];
                })
        },
        updatePage(){
            const udata = {
                page_id:this.page_id,
                page_title:this.page.page_title,
                page_description:this.page.quillContent,
                page_status:this.page.page_status,
                meta_title:this.page.meta_title,
                meta_description:this.page.meta_description,
                page_slug:this.page.page_slug,
            }
            axios.post('/sadmin/page/update',udata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getPageById();
                })
        },
        perdeletePage(){
            const ddata = {
                page_id:this.page_id,
            }
            axios.post('/sadmin/page/delete',ddata)
                .then((resp)=>{
                    window.Toast.warning(resp.data.message);
                    this.$router.push({name:'PagesList'});
                })
        },
    }
}

</script>

<style scoped>
.homesections {
    .ebuttons {
        opacity:0
    }
    &:hover .ebuttons {opacity: 1}
}
.hide.homesections > .v-card {opacity: 0.4}
.ebuttons {
    margin: 8px auto;
}
</style>
