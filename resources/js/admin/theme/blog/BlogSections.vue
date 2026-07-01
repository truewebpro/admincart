<template>
    <div class="blogsections">
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
            :alinks="alinks"
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
import PeopleSearchPreview from "@/components/previews/PeopleSearchPreview.vue";
import FeaturedOptionsPreview from "@/components/previews/FeaturedOptionsPreview.vue";
import VideoWithTextPreview from "@/components/previews/VideoWithTextPreview.vue";
import FeaturedLinksPreview from "@/components/previews/FeaturedLinksPreview.vue";
import {VDateInput} from "vuetify/labs/VDateInput";

export default {
    name: "BlogSections",
    props: {
        sections: Array,
        stypes: Array,
        blog_id: [String, Number],
        allProducts: Array,
        allCategories: Array,
        allBanners: Array,
        allBrands: Array,
        alinks: Array,
        cdn: String,
    },
    components: {
        VDateInput,
        HeroBannerPreview,
        PopularRangePreview,
        BrowseCollectionPreview,
        CustomTextPreview,
        FaqsPreview,
        FeaturedProductsPreview,
        ImageWithTextPreview,
        PopularBrandsPreview,
        ServicesPromoPreview,
        TextSectionPreview,
        FeaturedCollectionsPreview,
        FeaturedLinksPreview,
        SlideShowPreview,
        PeopleSearchPreview,
        FeaturedOptionsPreview,
        VideoWithTextPreview,
        SectionEditDialog
    },
    data(){
        return{
            addNewDialog:false,
            selectToAdd:null,
            dialogVisible:false,
            selectedSection:null,
            editingSection: null,
            deleteSectionDialog:false,
            delLoading:false,
        }
    },
    methods:{
        addSection(){
            if (!this.selectToAdd) return;
            const sdata = {
                blog_id:this.blog_id,
                stype_id:this.selectToAdd.stype_id,
                section_json:this.selectToAdd
            }
            axios.post('/sadmin/blog/section/add/new',sdata)
                .then((resp)=>{
                    this.$emit('refresh');
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
                await this.$emit('refresh');
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
                featured_links: "FeaturedLinksPreview",
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
                    this.$emit('refresh');
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
                    this.$emit('refresh');
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
                    this.$emit('refresh');
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
                    this.$emit('refresh');
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
.blogsections {
    .ebuttons {
        opacity:0
    }
    &:hover .ebuttons {opacity: 1}
}
.hide.blogsections > .v-card {opacity: 0.4}
.ebuttons {
    margin: 8px auto;
}
</style>
