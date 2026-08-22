<template>
    <v-container class="blog-new">
        <v-form v-model="bavalid" @submit.prevent="editBlog">
            <v-row class="position-sticky top-0 bg-grey-lighten-3" style="z-index: 99">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link :to="{name:'BlogsList'}" icon variant="tonal" density="compact">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        {{sblog.blog_title}}
                    </h2>
                </v-col>
                <v-col cols="12" md="6" class="text-end">
                    <v-btn type="submit" :disabled="!bavalid || baLoading" variant="tonal" color="success" density="comfortable" class="text-none">Save</v-btn>
                </v-col>
            </v-row>
            <v-tabs v-model="btab" color="primary" selectedClass="bg-lblue"
                    density="compact" bgColor="grey-lighten-3" sliderColor="primary"
                    slider-transition="fade"
                    class="mt-4">
                <v-tab value="general" class="bg-white">
                    Blog
                </v-tab>
                <v-tab value="content" class="bg-white">Sections</v-tab>
                <v-tab value="faqs" class="bg-white">FAQ's</v-tab>
            </v-tabs>
            <v-window v-model="btab">
                <v-window-item value="general">
                    <v-row class="mt-0">
                        <v-col cols="12" md="9">
                            <v-card elevation="0" class="border-sm">
                                <v-card-text>
                                    <div class="mb-2">
                                        <v-text-field v-model="sblog.blog_title"
                                                      :rules="titleRule"
                                                      variant="outlined"
                                                      label="Blog Title"
                                                      placeholder="Blog about latest topic"
                                                      density="compact" persistent-placeholder counter persistent-counter></v-text-field>
                                    </div>
                                    <div>
                                        <RichTextEditor v-model="sblog.quillContent"/>
                                    </div>
                                </v-card-text>
                            </v-card>
                            <v-card elevation="0" class="border-sm mt-3">
                                <v-card-title>Excerpt</v-card-title>
                                <v-card-subtitle>Add a summary of the post to appear on your home page or blog.</v-card-subtitle>
                                <v-card-text>
                                    <div>
                                        <v-textarea v-model="sblog.blog_excerpt" variant="outlined" label="Blog Excerpt" placeholder="Blog short description"
                                                    density="compact" persistent-placeholder counter persistent-counter></v-textarea>
                                    </div>
                                </v-card-text>
                            </v-card>
                            <v-card elevation="0" class="border-sm mt-3">
                                <v-card-title>Search engine listing</v-card-title>
                                <v-card-subtitle>Add a title and description to see how this blog post might appear in a search engine listing</v-card-subtitle>
                                <v-card-text>
                                    <div class="font-weight-medium text-h6">{{shopName}}</div>
                                    <div class="text-body-2 text-grey-darken-4 mb-2">{{domain}}</div>
                                    <div class="font-weight-medium text-h5 text-blue-darken-2">{{sblog.meta_title || sblog.blog_title}}</div>
                                    <div class="text-body-1">{{sblog.meta_desc || sblog.blog_excerpt}}</div>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <div class="mb-3">
                                        <v-text-field v-model="sblog.meta_title" variant="outlined" label="Meta Title" :placeholder="sblog.blog_title"
                                                      density="compact" persistent-placeholder counter="70" persistent-counter></v-text-field>
                                    </div>
                                    <div class="mb-3">
                                        <v-textarea v-model="sblog.meta_desc" variant="outlined" label="Meta Description" :placeholder="sblog.blog_excerpt"
                                                    density="compact" persistent-placeholder counter="160" persistent-counter></v-textarea>
                                    </div>
                                    <div class="mb-3">
                                        <v-text-field v-model="sblog.blog_slug" variant="outlined" label="URL handle" prefix="/blogs/" density="compact"
                                                      persistent-placeholder counter="100" persistent-counter
                                                      :hint="('https://'+domain+'/blogs/'+sblog.blog_slug)" persistent-hint></v-text-field>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card elevation="0" class="border-sm">
                                <v-card-title>Status</v-card-title>
                                <v-card-text>
                                    <v-radio-group v-model="sblog.blog_status" hide-details>
                                        <v-radio label="Active" value="active"></v-radio>
                                        <v-radio label="Inactive" value="inactive"></v-radio>
                                    </v-radio-group>
                                </v-card-text>
                            </v-card>
                            <v-card class="mt-3 border" elevation="0">
                                <v-card-title>Image</v-card-title>
                                <v-card-text>
                                    <v-img v-if="sblog.blog_image != null && !blog_image"
                                           :src="cdn+sblog.blog_image" max-width="150" class="mb-2"></v-img>
                                    <v-file-upload v-model="blog_image" density="compact" browse-text="Add Image"
                                                   icon="mdi-upload"
                                                   title="Add Image"
                                    ></v-file-upload>
                                </v-card-text>
                            </v-card>
                            <v-card elevation="0" class="border-sm mt-3">
                                <v-card-title>Organization</v-card-title>
                                <v-card-text>
                                    <div class="mb-3">
                                        <v-select v-model="user_id" item-title="name" item-value="id" label="Author" variant="outlined" density="compact"
                                                  persistent-placeholder placeholder="Author"
                                                  no-data-text="Select Author"></v-select>
                                    </div>
                                    <div>
                                        <v-combobox v-model="sblog.btags" label="Tags" variant="outlined" density="compact" multiple chips closable-chips
                                                    persistent-placeholder></v-combobox>
                                    </div>

                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
                <v-window-item value="content">
                    <BlogSections
                        :sections="sections"
                        :stypes="stypes"
                        :blog_id="blog_id"
                        :allProducts="allProducts"
                        :allCategories="allCategories"
                        :allBanners="allBanners"
                        :allBrands="allBrands"
                        :alinks="alinks"
                        :cdn="cdn"
                        @refresh="getBlogByID"
                    />
                </v-window-item>
                <v-window-item value="faqs">
                    <BlogFaqs
                        :blog_id="blog_id"
                        :faqs="faqs"
                        @refresh-faqs="getBlogByID"
                    />
                </v-window-item>
            </v-window>
        </v-form>
    </v-container>
</template>
<script>
import {VFileUpload} from "vuetify/labs/components";
import axios from "axios";
import RichTextEditor from "@/components/RichTextEditor.vue";
import BlogFaqs from "@/admin/theme/blog/BlogFaqs.vue";
import BlogSections from "@/admin/theme/blog/BlogSections.vue";

export default {
    name:"BlogsEdit",
    props:{
        blog_id:[Number,String]
    },
    components:{
        BlogSections,
        BlogFaqs,
        RichTextEditor,
        VFileUpload,
    },
    data(){
        return{
            btab:'general',
            stypes:[],
            sections:[],
            allProducts: [],
            allBrands:[],
            allCategories:[],
            allBanners: [],
            faqs: [],
            bavalid:false,
            baLoading:false,
            sblog:{
                blog_title:'',
                blog_slug:'',
                quillContent:'',
                blog_excerpt:'',
                btags:[],
                blog_status:'',
                meta_title:'',
                meta_desc:'',

            },
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            shopName:this.$store.state.shop.shop_name || 'ShopName?',
            blog_image:null,
            user_id:this.$store.state.user,
            user:this.$store.state.user,
            authors:this.$store.state.shop,
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 100) || "Maximum 60 characters allowed"]
        }
    },
    watch:{
        'blog_title'(newSlug){
            this.blog_slug = newSlug
                .toLowerCase()
                .trim()
                .replace(/[\s\W-]+/g,'-')
                .replace(/^-+|-+$/g,'')
        }
    },
    computed:{
        alinks(){
            return this.$store.state.alinks;
        },
    },
    created() {
        this.getBlogByID();
        this.$store.dispatch('fetchAlinks');
    },
    methods:{
        getBlogByID(){
            axios.get('/sadmin/blogs/edit/'+this.blog_id)
                .then((resp)=>{
                    const respData = resp.data;
                    const blogData = respData.blog;
                    this.sblog = respData.blog;
                    this.sblog.quillContent = blogData.blog_description;
                    this.stypes = respData.stypes;
                    this.sections = blogData.sections;
                    this.allCategories = respData.categories;
                    this.allBrands = respData.brands;
                    this.ptags = respData.tags;
                    this.ptypes = respData.ptypes;
                    this.faqs = blogData.faqs || [];
                })
        },
        editBlog(){
            this.baLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            let blogImage;
            if (this.blog_image instanceof File) {
                blogImage = this.blog_image;
            } else if (this.sblog.blog_image) {
                blogImage =  this.sblog.blog_image;
            }
           const nblog = {
               blog_id: this.blog_id,
               blog_title:this.sblog.blog_title,
               blog_slug:this.sblog.blog_slug,
               blog_description:this.sblog.quillContent,
               blog_excerpt:this.sblog.blog_excerpt,
               blog_image:blogImage,
               btags:this.sblog.btags,
               blog_status:this.sblog.blog_status,
               meta_title:this.sblog.meta_title,
               meta_desc:this.sblog.meta_desc,
               user_id:this.user_id.id,
               shop_id:this.$store.state.shop.shop_id,
            }
            axios.post('/sadmin/blogs/update',nblog,uheaders)
                .then((resp)=>{
                    this.getBlogByID();
                    window.Toast.success('Blog Updated Successfully')
                    this.blog_image = null;
                })
                .catch((err)=>{
                    console.log(err.message);
                    window.Toast.error('Some Error to edit Blog')
                })
                .finally(()=>{
                    this.baLoading = false;
                })

        },
    }
}

</script>

<style scoped>

</style>
