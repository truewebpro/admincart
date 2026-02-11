<template>
    <v-dialog v-model="show" max-width="1200px" persistent>
        <v-card>
            <v-card-title class="d-flex justify-space-between">
                Edit Section - {{ editingSection?.section_json.stype_slug }}
                <v-icon @click="close">mdi-close</v-icon>
            </v-card-title>
            <v-card-text>
                <component
                    :is="getEditorComponent(localSection?.section_json.stype_slug)"
                    v-model="editingSection.section_json"
                    :availableProducts="products"
                    :categories="categories"
                    :banners="banners"
                    :brands="brands"
                />
            </v-card-text>
            <v-divider />
            <div class="d-flex px-3 pb-2 ga-2">
                <v-spacer />
                <v-btn density="comfortable" color="red" text @click="close">Cancel</v-btn>
                <v-spacer />
                <v-btn density="comfortable" color="success" @click="save">Save</v-btn>
                <v-spacer />
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
import HeroBannerEditor from "@/components/sections/editors/HeroBannerEditor.vue"
import PopularRangeEditor from "@/components/sections/editors/PopularRangeEditor.vue"
import FeaturedProductsEditor from "@/components/sections/editors/FeaturedProductsEditor.vue"
import BlogSliderEditor from "@/components/sections/editors/BlogSliderEditor.vue"
import BrowseCollectionEditor from "@/components/sections/editors/BrowseCollectionEditor.vue";
import CustomTextEditor from "@/components/sections/editors/CustomTextEditor.vue";
import FaqsEditor from "@/components/sections/editors/FaqsEditor.vue";
import ImageWithTextEditor from "@/components/sections/editors/ImageWithTextEditor.vue";
import PopularBrandsEditor from "@/components/sections/editors/PopularBrandsEditor.vue";
import ReviewSliderEditor from "@/components/sections/editors/ReviewSliderEditor.vue";
import ServicesPromoEditor from "@/components/sections/editors/ServicesPromoEditor.vue";
import TextSectionEditor from "@/components/sections/editors/TextSectionEditor.vue";
import FeaturedCollectionsEditor from "@/components/sections/editors/FeaturedCollectionsEditor.vue";
import SlideShowEditor from "@/components/sections/editors/SlideShowEditor.vue";
import FeaturedOptionsEditor from "@/components/sections/editors/FeaturedOptionsEditor.vue";
import PeopleSearchEditor from "@/components/sections/editors/PeopleSearchEditor.vue";
import VideoWithTextEditor from "@/components/sections/editors/VideoWithTextEditor.vue";
import DefaultSectionEditor from "@/components/sections/editors/DefaultSectionEditor.vue";

export default {
    name: "SectionEditDialog",
    props: {
        modelValue: { type: Boolean, default: false },
        editingSection: { type: Object, default: null },
        products: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        banners: { type: Array, default: () => [] },
        brands: { type: Array, default: () => [] },
    },
    components: {
        BlogSliderEditor,
        BrowseCollectionEditor,
        CustomTextEditor,
        FaqsEditor,
        FeaturedProductsEditor,
        HeroBannerEditor,
        ImageWithTextEditor,
        PopularBrandsEditor,
        PopularRangeEditor,
        ReviewSliderEditor,
        ServicesPromoEditor,
        TextSectionEditor,
        FeaturedCollectionsEditor,
        SlideShowEditor,
        FeaturedOptionsEditor,
        PeopleSearchEditor,
        VideoWithTextEditor,
        DefaultSectionEditor,
    },
    data(){
        return {
            localSection:null,
        }
    },
    watch: {
        editingSection: {
            handler(newVal) {
                if (newVal) {
                    this.localSection = JSON.parse(JSON.stringify(newVal))
                }
            },
            deep: true,
            immediate: true
        }
    },
    computed: {
        show: {
            get() {
                return this.modelValue
            },
            set(val) {
                this.$emit("update:modelValue", val)
            }
        }
    },
    methods: {
        getEditorComponent(stype) {
            switch (stype) {
                case "hero_banner": return "HeroBannerEditor"
                case "popular_range": return "PopularRangeEditor"
                case "featured_products": return "FeaturedProductsEditor"
                case "blog_slider": return "BlogSliderEditor"
                case "browse_collection": return "BrowseCollectionEditor"
                case "custom_text": return "CustomTextEditor"
                case "faqs": return "FaqsEditor"
                case "image_with_text": return "ImageWithTextEditor"
                case "popular_brands": return "PopularBrandsEditor"
                case "services_promo": return "ServicesPromoEditor"
                case "review_slider": return "ReviewSliderEditor"
                case "text_section": return "TextSectionEditor"
                case "featured_collections": return "FeaturedCollectionsEditor"
                case "slide_show": return "SlideShowEditor"
                case "featured_options": return "FeaturedOptionsEditor"
                case "people_search": return "PeopleSearchEditor"
                case "video_with_text": return "VideoWithTextEditor"
                default: return "DefaultSectionEditor"
            }
        },
        close() {
            this.show = false
        },
        save() {
            this.$emit("save", this.localSection)
            this.show = false
        }
    }
}
</script>

