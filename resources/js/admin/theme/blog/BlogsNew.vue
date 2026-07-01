<template>
    <v-container class="blog-new">
        <v-form v-model="bavalid" @submit.prevent="addNewBlog">
            <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link :to="{name:'BlogsList'}" icon variant="tonal" density="compact">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        Add Blog Post
                    </h2>
                </v-col>
                <v-col cols="12" md="6" class="text-end">
                    <v-btn type="submit" :disabled="!bavalid || baLoading" color="grey-darken-3" density="compact" class="text-none">Save</v-btn>
                </v-col>
            </v-row>
            <v-row class="mt-0">
                <v-col cols="12" md="9">
                    <v-card elevation="0" class="border-sm">
                        <v-card-text>
                            <div class="mb-2">
                                <v-text-field v-model="blog_title"
                                              :rules="titleRule"
                                              variant="outlined"
                                              label="Blog Title"
                                              placeholder="Blog about latest topic"
                                              density="compact" persistent-placeholder counter persistent-counter></v-text-field>
                            </div>
                            <div>
                                <RichTextEditor v-model="quillContent"/>
                            </div>
                        </v-card-text>
                    </v-card>
                    <v-card elevation="0" class="border-sm mt-3">
                        <v-card-title>Excerpt</v-card-title>
                        <v-card-subtitle>Add a summary of the post to appear on your home page or blog.</v-card-subtitle>
                        <v-card-text>
                            <div>
                                <v-textarea v-model="blog_excerpt" variant="outlined" label="Blog Excerpt" placeholder="Blog short description"
                                            density="compact" persistent-placeholder counter persistent-counter></v-textarea>
                            </div>
                        </v-card-text>
                    </v-card>
                    <v-card elevation="0" class="border-sm mt-3">
                        <v-card-title>Search engine listing</v-card-title>
                        <v-card-subtitle>Add a title and description to see how this blog post might appear in a search engine listing</v-card-subtitle>
                        <v-card-text>
                            <div class="font-weight-medium text-h6">{{this.$store.getters.currentShopName}}</div>
                            <div class="text-body-2 text-grey-darken-4 mb-2">{{domain}}</div>
                            <div class="font-weight-medium text-h5 text-blue-darken-2">{{meta_title || blog_title}}</div>
                            <div class="text-body-1">{{meta_desc || blog_excerpt}}</div>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-text>
                            <div class="mb-3">
                                <v-text-field v-model="meta_title" variant="outlined" label="Meta Title" :placeholder="blog_title"
                                              density="compact" persistent-placeholder counter="70" persistent-counter></v-text-field>
                            </div>
                            <div class="mb-3">
                                <v-textarea v-model="meta_desc" variant="outlined" label="Meta Description" :placeholder="blog_excerpt"
                                            density="compact" persistent-placeholder counter="160" persistent-counter></v-textarea>
                            </div>
                            <div class="mb-3">
                                <v-text-field v-model="blog_slug" variant="outlined" label="URL handle" prefix="/blogs/" density="compact"
                                              persistent-placeholder counter="100" persistent-counter
                                              :hint="('https://'+domain+'/blogs/'+blog_slug)" persistent-hint></v-text-field>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="3">
                    <v-card elevation="0" class="border-sm">
                        <v-card-title>Status</v-card-title>
                        <v-card-text>
                            <v-radio-group v-model="blog_status" hide-details>
                                <v-radio label="Active" value="active"></v-radio>
                                <v-radio label="Inactive" value="inactive"></v-radio>
                            </v-radio-group>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-3 border" elevation="0">
                        <v-card-title>Image</v-card-title>
                        <v-card-text>
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
                                <v-combobox v-model="btags" label="Tags" variant="outlined" density="compact" multiple chips closable-chips
                                            persistent-placeholder></v-combobox>
                            </div>

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
    name:"BlogsNew",
    components:{RichTextEditor, VFileUpload},
    data(){
        return{
            bavalid:false,
            baLoading:false,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            blogs:[],
            blog_title:'',
            blog_slug:'',
            quillContent:'',
            blog_excerpt:'',
            blog_image:null,
            btags:[],
            blog_status:'active',
            meta_title:'',
            meta_desc:'',
            user_id:this.$store.state.user,
            user:this.$store.state.user,
            authors:this.$store.state.shop,
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"]
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
    methods:{
        addNewBlog(){
            this.baLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
           const nblog = {
               blog_title:this.blog_title,
               blog_slug:this.blog_slug,
               blog_description:this.quillContent,
               blog_excerpt:this.blog_excerpt,
               blog_image:this.blog_image,
               btags:this.btags,
               blog_status:this.blog_status,
               meta_title:this.meta_title,
               meta_desc:this.meta_desc,
               user_id:this.user_id.id,
               shop_id:this.$store.state.shop.shop_id,
            }
            axios.post('/sadmin/blogs/add/new',nblog,uheaders)
                .then((resp)=>{
                    window.Toast.success('Blog Added Successfully')
                    this.$router.push({name:'BlogsList'});
                })
                .catch((err)=>{
                    console.log(err.message);
                    window.Toast.error('Some Error to add Blog')
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
