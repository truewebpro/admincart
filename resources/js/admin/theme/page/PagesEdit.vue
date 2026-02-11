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
                                <quill-editor ref="quillRef" v-model="page.quillContent" @text-change="onEditorChange" placeholder="Policy Content"></quill-editor>
                            </div>
                        </v-card-text>
                    </v-card>
                    <v-card elevation="0" class="border-sm mt-3">
                        <v-card-title>
                            Search engine listing
                        </v-card-title>
                        <v-card-subtitle>Add a title and description to see how this Page post might appear in a search engine listing</v-card-subtitle>
                        <v-card-text>
                            <div class="font-weight-medium text-h6">Shop Name</div>
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
        </v-form>
        <v-divider class="my-4"/>
        <div class="pagesections mt-4">
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

export default {
    name:"PagesEdit",
    components: {
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
        SectionEditDialog
    },
    props:{
        page_id:[Number,String]
    },
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
            axios.get('/api/sadmin/page/edit/'+this.page_id)
                .then((resp)=>{
                    this.page = resp.data.page;
                    this.page.quillContent = resp.data.page.page_description;
                    const quill = this.$refs.quillRef.getQuill()
                    if (quill.getLength() <= 1) {
                        quill.clipboard.dangerouslyPasteHTML(this.page.quillContent);
                        this.page.quillContent = quill.root.innerHTML;
                    }
                    this.stypes = resp.data.stypes;
                    this.sections = resp.data.page.sections;
                    this.allCategories = resp.data.categories || [];
                    this.allBrands = resp.data.brands || [];
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
            axios.post('/api/sadmin/page/update',udata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getPageById();
                })
        },
        perdeletePage(){
            const ddata = {
                page_id:this.page_id,
            }
            axios.post('/api/sadmin/page/delete',ddata)
                .then((resp)=>{
                    window.Toast.warning(resp.data.message);
                    this.$router.push({name:'PagesList'});
                })
        },
        onEditorChange(delta, oldDelta, source) {
            const quill = this.$refs.quillRef.getQuill();
            this.page.quillContent = quill.root.innerHTML;
        },
        addSection(){
            if (!this.selectToAdd) return;
            const sdata = {
                page_id:this.page_id,
                stype_id:this.selectToAdd.stype_id,
                section_json:this.selectToAdd,
                // sort_order:1,
                // selected_item:this.selectToAdd.stype_id
            }
            axios.post('/api/sadmin/page/section/add/new',sdata)
                .then((resp)=>{
                    this.getPageById();
                    window.Toast.success('section added successfully')
                    this.addNewDialog = false;
                })
        },
        async updateSection(updated) {
            try {
                await axios.post(`/api/sadmin/homepage/section/update/${updated.section_id}`, {
                    section_json: updated.section_json,
                    sort_order: updated.sort_order,
                    section_status: updated.section_status
                })
                await this.getPageById();
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
                slideshow: "SlideShowPreview",
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
            axios.post(`/api/sadmin/homepage/section/delete/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.deleteSectionDialog = false;
                    this.getPageById();
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
            axios.post(`/api/sadmin/homepage/section/hideorshow/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getPageById();
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
            axios.post(`/api/sadmin/homepage/section/moveup/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getPageById();
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
            axios.post(`/api/sadmin/homepage/section/movedown/${section_id}`)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getPageById();
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
