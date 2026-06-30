<template>
    <v-container class="blog-new">
        <v-form v-model="bavalid" @submit.prevent="editBlog">
            <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link :to="{name:'BlogsList'}" icon variant="tonal" density="compact">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-icon size="small" class="mx-1">mdi-pencil</v-icon>
                        {{sblog.blog_title}}
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
        </v-form>
        <v-divider class="my-4"/>
        <div class="prosections mt-4">
            <div class="homepage d-flex flex-column ga-4">
                <div v-for="(section,sindex) in sections" :key="sindex" :class="section.section_status+ ' ' +'homesections section_'+section.section_json.stype_slug">
                    <component
                        :is="getPreviewComponent(section.section_json.stype_slug)"
                        :section="section"
                        :cdn="cdn"
                    />
                    <div class="d-flex justify-center ga-2 text-center ebuttons">
                        <v-btn color="green" density="compact" @click="openEditor(section)">Edit</v-btn>
                        <v-btn @click="hideOrShowSection(section)" density="compact" class="text-none" icon>
                            <v-icon v-if="section.section_status === 'show'" size="small">mdi-eye-outline</v-icon>
                            <v-icon v-if="section.section_status === 'hide'" size="small">mdi-eye-off-outline</v-icon>
                        </v-btn>
                        <v-btn @click="moveUp(section)" density="compact" class="text-none" icon
                               v-if="sindex">
                            <v-icon size="small">mdi-arrow-up-bold</v-icon>
                        </v-btn>
                        <v-btn @click="moveDown(section)" density="compact" class="text-none" icon
                               v-if="sindex !== sections.length-1">
                            <v-icon size="small">mdi-arrow-down-bold</v-icon>
                        </v-btn>
                        <v-btn @click="openDeleteDialog(section)" density="compact" color="red" class="text-none" icon>
                            <v-icon size="small">mdi-delete-outline</v-icon>
                        </v-btn>
                    </div>
                </div>
            </div>
            <SectionEditDialog
                v-model="dialogVisible"
                :editingSection="selectedSection"
                :products="allProducts"
                :categories="allCategories"
                :banners="allBanners"
                :brands="allBrands"
                @save="updateSection"
            />
            <v-dialog v-model="deleteSectionDialog" max-width="400" transition="dialog-bottom-transition">
                <v-card>
                    <v-card-text class="text-center">
                        Are you sure to delete <br/>
                        <span class="font-weight-bold mt-3">{{selectedSection.section_json.stype_name}}</span>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn color="success" @click="confirmDelete">Ok</v-btn>
                        <v-btn color="error" @click="cancelDeleteDialog">Cancel</v-btn>
                        <v-spacer/>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <div class="d-flex justify-center my-3">
                <v-btn @click="addNewDialog = true" color="primary" prepend-icon="mdi-plus">Add New Section</v-btn>
            </div>
            <v-dialog max-width="400" v-model="addNewDialog">
                <v-card>
                    <v-card-title>Select Section</v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="addSection">
                            <v-autocomplete v-model="selectToAdd" return-object :items="stypes" item-title="stype_name"
                                            density="compact" variant="underlined"
                            ></v-autocomplete>
                            <div class="d-flex">
                                <v-spacer/>
                                <v-btn color="green" density="compact" type="submit">Confirm Add</v-btn>
                                <v-spacer/>
                                <v-btn color="red" density="compact" @click="addNewDialog = false">cancel</v-btn>
                            </div>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-dialog>
        </div>
    </v-container>
</template>
<script>
import {VFileUpload} from "vuetify/labs/components";
import axios from "axios";
import HeroBannerPreview from "@/components/previews/HeroBannerPreview.vue";
import PopularRangePreview from "@/components/previews/PopularRangePreview.vue";
import SectionEditDialog from "@/components/sections/editors/SectionEditDialog.vue";
import BrowseCollectionPreview from "@/components/previews/BrowseCollectionPreview.vue";
import CustomTextPreview from "@/components/previews/CustomTextPreview.vue";
import FaqsPreview from "@/components/previews/FaqsPreview.vue";
import FeaturedProductsPreview from "@/components/previews/FeaturedProductsPreview.vue";
import ImageWithTextPreview from "@/components/previews/ImageWithTextPreview.vue";
import PopularBrandsPreview from "@/components/previews/PopularBrandsPreview.vue";
import ServicesPromoPreview from "@/components/previews/ServicesPromoPreview.vue";
import TextSectionPreview from "@/components/previews/TextSectionPreview.vue";
import FeaturedCollectionsPreview from "@/components/previews/FeaturedCollectionsPreview.vue";
import SlideShowPreview from "@/components/previews/SlideShowPreview.vue";
import FeaturedOptionsPreview from "@/components/previews/FeaturedOptionsPreview.vue";
import PeopleSearchPreview from "@/components/previews/PeopleSearchPreview.vue";
import VideoWithTextPreview from "@/components/previews/VideoWithTextPreview.vue";
import RichTextEditor from "@/components/RichTextEditor.vue";

export default {
    name:"BlogsEdit",
    props:{
        blog_id:[Number,String]
    },
    components:{
        RichTextEditor, VFileUpload,
        BrowseCollectionPreview,
        CustomTextPreview,
        FaqsPreview,
        FeaturedProductsPreview,
        ImageWithTextPreview,
        HeroBannerPreview,
        PopularBrandsPreview,
        PopularRangePreview,
        ServicesPromoPreview,
        TextSectionPreview,
        FeaturedCollectionsPreview,
        SlideShowPreview,
        FeaturedOptionsPreview,
        PeopleSearchPreview,
        VideoWithTextPreview,
        SectionEditDialog},
    data(){
        return{
            addNewDialog:false,
            selectToAdd:null,
            stypes:[],
            sections:[],
            delLoading:false,
            dialogVisible:false,
            selectedSection: null,
            editingSection: null,
            deleteSectionDialog:false,
            allProducts: [],
            allBrands:[],
            allCategories:[],
            allBanners: [],
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
    created() {
        this.getBlogByID();
    },
    methods:{
        getBlogByID(){
            axios.get('/sadmin/blogs/edit/'+this.blog_id)
                .then((resp)=>{
                    this.sblog = resp.data.blog;
                    this.sblog.quillContent = resp.data.blog.blog_description;
                    const quill = this.$refs.quillRef.getQuill()
                    if (quill.getLength() <= 1) {
                        quill.clipboard.dangerouslyPasteHTML(this.sblog.quillContent);
                        const html = quill.root.innerHTML;
                        this.quillContent = html;
                    }
                    this.stypes = resp.data.stypes;
                    this.sections = resp.data.blog.sections;
                    this.allCategories = resp.data.categories;
                    this.allBrands = resp.data.brands;
                    this.ptags = resp.data.tags;
                    this.ptypes = resp.data.ptypes;
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
            console.log('nblog',nblog);
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
        onEditorChange(delta, oldDelta, source) {
            const quill = this.$refs.quillRef.getQuill();
            this.sblog.quillContent = quill.root.innerHTML;
        },
        addSection(){
            if (!this.selectToAdd) return;
            const sdata = {
                blog_id:this.blog_id,
                stype_id:this.selectToAdd.stype_id,
                section_json:this.selectToAdd,
                // sort_order:1,
                // selected_item:this.selectToAdd.stype_id
            }
            axios.post('/sadmin/blog/section/add/new',sdata)
                .then((resp)=>{
                    this.getBlogByID();
                    window.Toast.success('section added successfully')
                    this.addNewDialog = false;
                })
        },
        async updateSection(updated) {
            try {
                await axios.post(`/sadmin/homepage/section/update/${updated.section_id}`, {
                    section_json: updated.section_json,
                    sort_order: updated.sort_order,
                    section_status: updated.section_status
                })
                await this.getBlogByID();
                window.Toast.success('Section Updated Successfully');
            } catch (e) {
                window.Toast.error("Failed to save section", e);
            }
        },
        getPreviewComponent(slug) {
            const map = {
                blog_slider: "BlogSliderPreview",
                browse_collection: "BrowseCollectionPreview",
                custom_text: "CustomTextPreview",
                faqs: "FaqsPreview",
                featured_products: "FeaturedProductsPreview",
                hero_banner: "HeroBannerPreview",
                image_with_text: "ImageWithTextPreview",
                popular_brands: "PopularBrandsPreview",
                popular_range: "PopularRangePreview",
                review_slider: "ReviewSliderPreview",
                services_promo: "ServicesPromoPreview",
                text_section: "TextSectionPreview",
                featured_collections: "FeaturedCollectionsPreview",
                slide_show: "SlideShowPreview",
                featured_options: "FeaturedOptionsPreview",
                people_search: "PeopleSearchPreview",
                video_with_text: "VideoWithTextPreview",
            }
            return map[slug] || "div"
        },
        openEditor(section) {
            this.selectedSection = JSON.parse(JSON.stringify(section));
            this.dialogVisible = true;
        },
        openDeleteDialog(section){
            this.selectedSection = JSON.parse(JSON.stringify(section));
            this.deleteSectionDialog = true;
        },
        cancelDeleteDialog(){
            this.deleteSectionDialog = false;
        },
        confirmDelete(){
            const section_id = this.selectedSection.section_id;
            axios.post(`/sadmin/homepage/section/delete/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.deleteSectionDialog = false;
                    this.getBlogByID();
                })
                .catch((err)=>{
                    window.Toast.error(err.message)
                })
                .finally(()=>{
                    this.delLoading = false;
                })

        },
        hideOrShowSection(section){
            this.selectedSection = JSON.parse(JSON.stringify(section));
            const section_id = this.selectedSection.section_id;
            axios.post(`/sadmin/homepage/section/hideorshow/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getBlogByID();
                })
                .catch((err)=>{
                    window.Toast.error(err.message)
                })
                .finally(()=>{
                    this.delLoading = false;
                })
        },
        moveUp(section){
            this.selectedSection = JSON.parse(JSON.stringify(section));
            const section_id = this.selectedSection.section_id;
            axios.post(`/sadmin/homepage/section/moveup/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getBlogByID();
                })
                .catch((err)=>{
                    window.Toast.error(err.message)
                })
                .finally(()=>{
                    this.delLoading = false;
                })
        },
        moveDown(section){
            this.selectedSection = JSON.parse(JSON.stringify(section));
            const section_id = this.selectedSection.section_id;
            axios.post(`/sadmin/homepage/section/movedown/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getBlogByID();
                })
                .catch((err)=>{
                    window.Toast.error(err.message)
                })
                .finally(()=>{
                    this.delLoading = false;
                })
        },
    }
}

</script>

<style scoped>

</style>
