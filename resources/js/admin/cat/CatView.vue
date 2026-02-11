<template>
    <v-container>
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 1020">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn link to="/categories" icon variant="tonal" density="compact">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                {{ cat.cat_name }}
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn :href="domain+'collections/'+cat.cat_slug" target="_blank" variant="outlined" density="compact" class="text-none" prepend-icon="mdi-eye-outline">View</v-btn>
                <v-btn v-if="cat.cat_type !== 'smart'" @click="updatemCat" variant="elevated" color="green" density="compact"
                       class="text-none mx-2">Update</v-btn>
                <v-btn v-else :disabled="isSaveDisabled" @click="updatesCat" variant="elevated" color="green" density="compact" class="text-none mx-2">Update</v-btn>
                <v-btn @click="delDialog = true" variant="outlined" color="red" density="compact" class="text-none">Delete</v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="9">
                <v-card>
                    <v-card-text>
                        <v-text-field v-model="cat.cat_name" variant="outlined" density="compact" label="Title"></v-text-field>
                        <h2 class="text-h6">Main Description</h2>
                        <quill-editor ref="quillRef" v-model="quillContent"  @text-change="onEditorChange">
                        </quill-editor>
                        <h2 class="text-h6 mt-3">Short Description</h2>
                        <quill-editor ref="shortRef" v-model="cat.short_desc" @text-change="onEditorChange"></quill-editor>
                    </v-card-text>
                </v-card>
                <v-card v-if="cat.cat_type !== 'manual'" class="mt-4 border">
                    <v-card-title>Conditions</v-card-title>
                    <v-card-text>
                        <div class="d-flex align-center">
                            <span class="text-body-1">Products must match:</span>
                            <v-radio-group inline hide-details v-model="cat.cat_rule">
                                <v-radio label="and" value="and"></v-radio>
                                <v-radio label="or" value="or"></v-radio>
                            </v-radio-group>
                        </div>
                        <div v-for="(rule,index) in cat.rules" :key="index">
                            <v-row class="align-start">
                                <v-col cols="12" md="4">
                                    <v-autocomplete
                                        v-model="rule.column"
                                        :items="rcolumns"
                                        item-title="name"
                                        label="Select Column"
                                        variant="outlined"
                                        density="compact"
                                        @update:modelValue="getRelations(rule)"
                                    ></v-autocomplete>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-autocomplete
                                        v-model="rule.relation"
                                        :items="getRelationsFor(rule.column)"
                                        item-title="name"
                                        item-value="value"
                                        label="Relation"
                                        variant="outlined"
                                        density="compact"
                                    ></v-autocomplete>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-autocomplete
                                        v-if="getComponentType(rule.column) === 'autocomplete'"
                                        v-model.number="rule.condition"
                                        :items="getItemsFor(rule.column)"
                                        :item-title="getItemTitle(rule.column)"
                                        :item-value="getItemValue(rule.column)"
                                        :label="getLabel(rule.column)"
                                        :return-object="false"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                    />

                                    <!-- Text field (Title) -->
                                    <v-text-field
                                        v-else-if="getComponentType(rule.column) === 'text'"
                                        v-model="rule.condition"
                                        :label="getLabel(rule.column)"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                    />

                                    <!-- Number input (Price, Stock) -->
                                    <v-text-field
                                        v-else-if="getComponentType(rule.column) === 'number'"
                                        v-model.number="rule.condition"
                                        type="number"
                                        :label="getLabel(rule.column)"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                    />
                                </v-col>
                                <v-col cols="12" md="1">
                                    <v-btn v-if="cat.rules.length > 1" icon="mdi-delete" color="red" variant="outlined"
                                           size="small" @click="removeRule(index)"></v-btn>
                                </v-col>
                            </v-row>
                        </div>
<!--                        <v-btn @click="fetchFilteredProductIds" variant="tonal" color="primary" density="compact">-->
<!--                            Reapply Rules-->
<!--                        </v-btn>-->
                        <v-btn @click="addNewRule" :disabled="!isLastRuleComplete"  variant="outlined" density="compact" class="text-none" prepend-icon="mdi-plus">
                            Add another condition
                        </v-btn>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border">
                    <v-card-title>Products</v-card-title>
                    <v-card-text v-if="cat.cat_type === 'manual'">
                        <v-row>
                            <v-col cols="12" md="5">
                                <v-text-field variant="outlined" density="compact" placeholder="Search Products"
                                              prepend-inner-icon="mdi-magnify"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn density="default" variant="outlined" block @click="searchDialog = true">Browse</v-btn>
                            </v-col>
                            <v-col cols="12" md="5">
                                <v-select v-model="cat.sort_order" variant="outlined" density="compact" :items="sortby"
                                          item-title="name"></v-select>
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-data-table v-if="cat.cat_type === 'manual'" :items="mpros" :headers="prosHeaders" hide-default-footer>
                        <template v-slot:item.sno="{item,index}">
                            <span class="font-weight-bold">{{index+1}}</span>
                        </template>
                        <template v-slot:item.featured_image="{item}">
                            <v-img v-if="item.featured_image != null" :src="cdn+item.featured_image"></v-img>
                            <v-img v-else :src="cdn+'noimage.png'" max-width="60" class="my-1"></v-img>
                        </template>
                        <template v-slot:item.product_status="{item}">
                            <v-chip size="small" variant="tonal" class="font-weight-medium bg-light-green-accent-1 text-black"
                                    color="black" v-if="item.product_status === 'Active'">{{item.product_status}}</v-chip>
                        </template>
                        <template v-slot:item.remove="{item}">
                            <v-btn icon="mdi-close" variant="text" size="small" color="red"
                                   @click="removeManualProduct(item.product_id)"></v-btn>
                        </template>
                    </v-data-table>
                    <v-card-text v-if="cat.cat_type === 'smart'">
                        <v-select v-model="cat.sort_order" variant="outlined" density="compact" :items="sortby" item-title="name"></v-select>
                    </v-card-text>
                    <v-data-table v-if="cat.cat_type === 'smart'" :items="spros" :headers="prosHeaders">
                        <template v-slot:item.sno="{item,index}">
                            <span class="font-weight-bold">{{index+1}}</span>
                        </template>
                        <template v-slot:item.featured_image="{item}">
                            <v-img v-if="item.featured_image != null" :src="cdn+item.featured_image"></v-img>
                            <v-img v-else :src="cdn+'noimage.png'" max-width="60" class="my-1"></v-img>
                        </template>
                        <template v-slot:item.product_status="{item}">
                            <v-chip size="small" variant="tonal" class="font-weight-medium bg-light-green-accent-1 text-black"
                                    color="black" v-if="item.product_status === 'Active'">{{item.product_status}}</v-chip>
                        </template>
                    </v-data-table>
                </v-card>
                <v-card class="mt-4 border">
                    <v-card-title>Search engine listing</v-card-title>
                    <v-card-subtitle>Add a title and description to see how this category might appear in a search engine listing</v-card-subtitle>
                    <v-card-text>
                        <div class="mb-4">
                            <v-text-field v-model="cat.meta_title" variant="outlined" density="compact" label="Page Title"
                                          counter="60" persistent-counter></v-text-field>
                        </div>
                        <div class="mb-4">
                            <v-textarea v-model="cat.meta_desc" variant="outlined" density="compact" label="Meta Description"
                                        counter="160" persistent-counter></v-textarea>
                        </div>
                        <div>
                            <v-text-field v-model="cat.cat_slug" variant="outlined" density="compact" persistent-placeholder
                                          persistent-hint :prefix="domain+'collections/'"></v-text-field>
                        </div>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border">
                    <v-card-title class="d-flex justify-space-between align-center">
                        Related Cats
                        <v-btn @click="addrcatDialog = true" color="success" density="compact"
                               variant="flat" class="text-none">Add Child Category</v-btn>
                    </v-card-title>
                    <v-data-table :items="rcats" :headers="rcatsHeader" :hide-default-footer="rcats.length < 9">
                        <template v-slot:item.related_image="{item}">
                            <v-img v-if="item.related_image" :src="cdn+item.related_image" width="150" min-height="75" height="75"
                                   cover class="my-1"></v-img>
                            <v-img v-else :src="cdn+'noimage.png'" width="150" min-height="75" height="75" cover class="my-1"></v-img>
                        </template>
                        <template v-slot:item.actions="{item}">
                            <v-btn @click="editItem(item)" color="info" density="compact">Edit</v-btn>
                            <v-btn @click="deleteRelatedCat(item)" color="red" density="compact" class="ms-1">Delete</v-btn>
                        </template>
                    </v-data-table>
                    <v-card-text></v-card-text>
                    <v-dialog v-model="addrcatDialog" max-width="450">
                        <v-card>
                            <v-form v-model="addrcatValid" @submit.prevent="addRelatedCat">
                                <v-card-text>
                                    <v-text-field v-model="defaultRcat.related_cat_title"
                                                  :rules="ctitleRule"
                                                  density="comfortable"
                                                  label="Title" variant="underlined"/>
                                    <v-autocomplete v-model="defaultRcat.cat_child_id"
                                                    :rules="selectChildCatRule"
                                                    label="Select Category"
                                                    density="comfortable" :items="allCategories" return-object
                                                    item-title="cat_name" variant="underlined"></v-autocomplete>
                                    <v-file-upload v-model="defaultRcat.related_image" density="compact" browse-text="Add Image"
                                                   icon="mdi-image" class="mt-3"
                                                   title="Image 300x150" subtitle="300x150" clearable show-size
                                    ></v-file-upload>
                                    <div class="d-flex ga-3">
                                        <v-spacer/>
                                        <v-btn :disabled="!addrcatValid" type="submit" color="success" density="comfortable">Add</v-btn>
                                        <v-btn @click="addrcatDialog = false" density="comfortable" variant="outlined">Cancel</v-btn>
                                    </div>
                                </v-card-text>
                            </v-form>
                        </v-card>
                    </v-dialog>
                    <v-dialog v-model="editrcatDialog" max-width="450">
                        <v-card>
                            <v-form v-model="editrcatValid" @submit.prevent="updateRelatedCat">
                                <v-card-text>
                                    <v-text-field v-model="editedRcat.related_cat_title"
                                                  :rules="ctitleRule"
                                                  density="comfortable"
                                                  label="Title" variant="underlined"/>
                                    <v-autocomplete v-model="editedRcat.cat_child_id"
                                                    :rules="selectChildCatRule"
                                                    label="Select Category" item-value="cat_id"
                                                    density="comfortable" :items="allCategories"
                                                    item-title="cat_name" variant="underlined"></v-autocomplete>
                                    <v-file-upload v-model="newrImage" density="compact" browse-text="Add Image"
                                                   icon="mdi-image" class="mt-3"
                                                   title="Image 300x150" subtitle="300x150" clearable show-size
                                    ></v-file-upload>
                                    <div class="d-flex ga-3">
                                        <v-spacer/>
                                        <v-btn :disabled="!editrcatValid" type="submit" color="success" density="comfortable">Update</v-btn>
                                        <v-btn @click="editrcatDialog = false" density="comfortable" variant="outlined">Cancel</v-btn>
                                    </div>
                                </v-card-text>
                            </v-form>
                        </v-card>
                    </v-dialog>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card>
                    <v-card-title>Publishing</v-card-title>
                    <v-card-subtitle>Sales Channel</v-card-subtitle>
                    <v-card-text>
                        <v-checkbox-btn v-model="cat.cat_status" value="Active" label="Online" hide-details></v-checkbox-btn>
                        <v-checkbox-btn label="Other" hide-details></v-checkbox-btn>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border">
                    <v-card-title>Image</v-card-title>
                    <v-card-text>
                        <v-img v-if="cat.cat_image != null" :src="cdn+cat.cat_image" max-height="200"></v-img>
                        <v-img v-else :src="cdn+'noimage.png'" max-height="200"></v-img>
                        <v-file-upload v-model="newImage" density="compact" browse-text="Add Image"
                            icon="mdi-upload" class="mt-3"
                            title="Upload Image" clearable show-size
                        ></v-file-upload>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog max-width="600" v-model="searchDialog" persistent>
            <v-card>
                <v-text-field v-model="psearch" density="compact" variant="underlined" hide-details
                              prepend-inner-icon="mdi-magnify" clearable class="mx-2 mb-1"></v-text-field>
                <v-data-table :items="availablePros" :search="psearch" :headers="prosHeaders" show-select
                              density="compact" v-model="selectedmPros" return-object>
                    <template v-slot:item.sno="{item,index}">
                        <span class="font-weight-bold">{{index+1}}</span>
                    </template>
                    <template v-slot:item.featured_image="{item}">
                        <v-img v-if="item.featured_image != null" :src="cdn+item.featured_image" max-width="60" class="my-1"></v-img>
                        <v-img v-else :src="cdn+'noimage.png'" max-width="60" class="my-1"></v-img>
                    </template>
                    <template v-slot:item.product_status="{item}">
                        <v-chip size="small" variant="tonal" class="font-weight-medium bg-light-green-accent-1 text-black"
                                color="black" v-if="item.product_status === 'Active'">{{item.product_status}}</v-chip>
                    </template>
                </v-data-table>
                <v-card-actions>
                    <v-btn @click="addMpros" :disabled="selectedmPros.length < 1" variant="elevated" color="success" density="compact"
                           class="font-weight-bold">Add Selected</v-btn>
                    <v-spacer/>
                    <v-btn variant="elevated" @click="closesSdialog" color="red" density="compact" class="font-weight-bold">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog max-width="350" v-model="delDialog">
            <v-card :text="'Collection '+cat.cat_name"
                    title="Are you sure to delete?">
                <template v-slot:actions>
                    <v-btn variant="elevated" color="success" size="small" @click="deleteCat">Yes</v-btn>
                    <v-btn variant="elevated" color="red" size="small" @click="delDialog = false">No</v-btn>
                </template>
            </v-card>
        </v-dialog>
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
import axios from "axios";
import {VFileUpload} from "vuetify/labs/components";
import {QuillEditor} from "@vueup/vue-quill";
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
    name:"CatView",
    components:{QuillEditor, VFileUpload,
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
       cat_id:[Number,String]
    },
    mounted() {
        // if (this.cat.cat_type === 'smart' && this.cat.rules.length > 0) {
        //     this.fetchFilteredProductIds();
        // }
    },
    watch: {
        'cat.rules': {
            deep: true,
            immediate:true,
            handler(newRules) {
                if (this.cat.cat_type === 'smart') {
                    this.fetchFilteredProductIds();
                }
            }
        }
    },
    computed:{
        pbrands(){
            return this.$store.state.brands;
        },
        ptags(){
            return this.$store.state.tags;
        },
        ptypes(){
            return this.$store.state.productTypes;
        },
        plainTextDesc() {
            return this.cat.cat_desc
                .replace(/<br\s*\/?>/gi, '\n')
                .replace(/<[^>]+>/g, '');
        },
        mpros() {
            return (this.cat.productIds ?? []).map(pid =>
                this.pros.find(p => p.product_id === pid)
            ).filter(Boolean);
        },
        availablePros() {
            const existing = this.cat.productIds ?? [];
            return this.pros.filter(p => !existing.includes(p.product_id));
        },
        spros() {
            return (this.cat.productIds ?? []).map(pid =>
                this.cat.epros?.find(p => p.product_id === pid)
            ).filter(Boolean);
        },
        isLastRuleComplete() {
            const last = this.cat.rules[this.cat.rules.length - 1];
            return last && last.column && last.relation && last.condition !== null;
        },
        isSaveDisabled() {
            if (this.cat.cat_type === 'smart') {
                if (!this.cat.rules || this.cat.rules.length === 0) return true;
                return this.cat.rules.some(rule =>
                    !rule.column || !rule.relation || rule.condition === null || rule.condition === ''
                );
            }
            if (this.cat.cat_type === 'manual') {
                return !this.cat.productIds || this.cat.productIds.length === 0;
            }
            return true;
        }
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
            dataLoading:false,
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+"/",
            cdn:this.$store.state.cdn,
            quillContent:'',
            searchDialog:false,
            delDialog:false,
            selectedPros:[],
            selectedmPros:[],
            psearch:'',
            selectCond:null,
            selectRel:'',
            sortby:[
                {name:'Product Title A-Z',value:'title_asc'},
                {name:'Product Title Z-A',value:'title_desc'},
                {name:'Highest Price',value:'price_asc'},
                {name:'Lowest Price',value:'price_desc'},
                {name:'Newest',value:'created_asc'},
                {name:'Oldest',value:'created_desc'},
            ],
            cat:{
                cat_name:'',
                cat_slug:'',
                cat_desc:'',
                short_desc:'',
                cat_rule:'',
                sort_order:'',
                meta_title:'',
                meta_desc:'',
                rules:[],
                epros:[],
                productIds:[],
            },
            newImage:null,
            pros:[],
            prosHeaders:[
                {title:"#",value:'sno',width:50},
                {title:"Image",value:'featured_image',width:80},
                {title:"Tile",value:'title'},
                {title:"Status",value:'product_status',width: 120},
                {title:"",value:'remove',width: 72},
            ],
            rcolumns: [
                {
                    name: 'Tag',
                    value: 'tag',
                    type: 'autocomplete',
                    itemTitle: 'tag_name',
                    itemValue: 'tag_name',
                    source: 'ptags',
                    relations: [
                        { name: 'is Equal to', value: 'equals' },
                    ]
                },
                {
                    name: 'Type',
                    value: 'type',
                    type: 'autocomplete',
                    itemTitle: 'product_type_name',
                    itemValue: 'product_type_id',
                    source: 'ptypes',
                    relations: [
                        { name: 'is Equal to', value: 'equals' },
                        { name: 'is Not Equal to', value: 'not_equals' },
                    ]
                },
                {
                    name: 'Brand',
                    value: 'vendor',
                    type: 'autocomplete',
                    itemTitle: 'brand_name',
                    itemValue: 'brand_id',
                    source: 'pbrands',
                    relations: [
                        { name: 'is Equal to', value: 'equals' },
                        { name: 'is Not Equal to', value: 'not_equals' },
                    ]
                },
                {
                    name: 'Title',
                    value: 'title',
                    type: 'text',
                    relations: [
                        { name: 'Contains', value: 'contains' },
                        { name: 'Not Contains', value: 'not_contains' },
                    ]
                },
                // {
                //     name: 'Price',
                //     value: 'price',
                //     type: 'number',
                //     relations: [
                //         { name: 'is Equal to', value: 'equals' },
                //         { name: 'is Greater Than', value: 'greater_than' },
                //         { name: 'is Less Than', value: 'less_than' },
                //     ]
                // },
                // {
                //     name: 'Stock',
                //     value: 'stock',
                //     type: 'number',
                //     relations: [
                //         { name: 'is Equal to', value: 'equals' },
                //         { name: 'is Greater Than', value: 'greater_than' },
                //         { name: 'is Less Than', value: 'less_than' },
                //     ]
                // },
            ],
            ctitleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"
            ],
            rcats:[],
            rcatsHeader:[
                {title:'Title',value:'related_cat_title'},
                {title:'Image',value:'related_image'},
                {title:'Actions',value:'actions'},
            ],
            selectChildCatRule:[
                (v) => !!v || "Related Cat is required",
            ],
            addrcatDialog:false,
            editrcatDialog:false,
            addrcatValid:false,
            editrcatValid:false,
            editedrIndex:-1,
            defaultRcat:{
                cat_parent_id :'',
                related_cat_title:'',
                related_image:null,
                cat_child_id:'',
                shop_id:'',
            },
            editedRcat:{
                related_cat_id :'',
                cat_parent_id :'',
                related_cat_title:'',
                related_image:'',
                cat_child_id:'',
                shop_id:'',
            },
            newrImage:null,
        }
    },
    created() {
        this.getCategory();
    },
    methods:{
        getCategory(){
            this.dataLoading = true;
            axios.get('/sadmin/categories/'+this.cat_id)
                .then((resp)=>{
                    const catData = resp.data.cat;
                    this.quillContent = catData.cat_desc;
                    const quill = this.$refs.quillRef.getQuill()
                    if (quill.getLength() <= 1) {
                        quill.clipboard.dangerouslyPasteHTML(this.quillContent);
                        const html = quill.root.innerHTML;
                        this.quillContent = html;
                    }
                    this.cat.short_desc = catData.short_desc;
                    const squill = this.$refs.shortRef.getQuill();
                    if(squill.getLength() <= 1){
                        squill.clipboard.dangerouslyPasteHTML(this.cat.short_desc);
                        this.cat.short_desc = squill.root.innerHTML;
                    }
                    // Set only important fields first — immediate UI response
                    this.cat = {
                        ...this.cat,
                        cat_name: catData.cat_name,
                        cat_slug: catData.cat_slug,
                        cat_desc: catData.cat_desc,
                        short_desc: catData.short_desc,
                        cat_rule: catData.cat_rule || 'and',
                        sort_order: catData.sort_order,
                        meta_title: catData.meta_title,
                        meta_desc: catData.meta_desc,
                        cat_type: catData.cat_type,
                        cat_status: catData.cat_status,
                        productIds: catData.productIds ?? [],
                        rules: (catData.rules ?? []).map(rule => {
                            if (['type', 'vendor', 'price', 'stock'].includes(rule.column)) {
                                rule.condition = Number(rule.condition);
                            }
                            return rule;
                        }),
                    };
                    // Defer heavy data so UI can render form fast
                    this.$nextTick(() => {
                        this.pros = resp.data.pros;
                        this.cat.epros = catData.epros || [];
                    });
                    this.sections = catData.sections;
                    this.stypes = resp.data.stypes;
                    this.allCategories = resp.data.categories;
                    this.allBrands = resp.data.brands;
                    this.rcats = catData.rcats;
                })
                .catch((err)=>{
                    console.log(err.message);
                })
                .finally(()=>{
                    this.dataLoading = false;
                })
        },
        addRelatedCat(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
           const ardata = {
               cat_parent_id :this.cat_id,
               related_cat_title:this.defaultRcat.related_cat_title || this.defaultRcat.cat_child_id.cat_name,
               related_image:this.defaultRcat.related_image,
               cat_child_id:this.defaultRcat.cat_child_id.cat_id,
           }
           axios.post('/sadmin/categories/related/add',ardata,uheaders)
               .then((resp)=>{
                   window.Toast.success(resp.data.message);
                   this.addrcatDialog = false;
                   this.defaultRcat.related_cat_title = '';
                   this.getCategory();
               })
        },
        updateRelatedCat(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            let nimage = this.newrImage;
            if(nimage){
                this.editedRcat.related_image = nimage
            } else {
                nimage = this.editedRcat.related_image;
            }
            const urdata = {
                related_cat_id :this.editedRcat.related_cat_id,
                cat_parent_id :this.cat_id,
                related_cat_title:this.editedRcat.related_cat_title,
                related_image:nimage,
                cat_child_id:this.editedRcat.cat_child_id,
            }
            axios.post('/sadmin/categories/related/add',urdata,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.editrcatDialog = false;
                    this.getCategory();
                })
        },
        deleteRelatedCat(item){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const drdata = {
                related_cat_id :item.related_cat_id,
                memname:'delete',
            }
            axios.post('/sadmin/categories/related/add',drdata,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getCategory();
                })
        },

        editItem(item){
            this.editedrIndex = this.rcats.indexOf(item);
            this.editedRcat = Object.assign({},item);
            this.editrcatDialog = true;
        },
        getRelations(rule) {
            const col = this.rcolumns.find(c => c.value === rule.column);
            if (!col) return;

            rule.relation = col.relations[0]?.value || null;

            // Set default condition based on type
            switch (col.type) {
                case 'autocomplete':
                    rule.condition = this[col.source]?.[0]?.[col.itemValue] || null;
                    break;
                case 'text':
                    rule.condition = 'Product';
                    break;
                case 'number':
                    rule.condition = 1;
                    break;
                default:
                    rule.condition = null;
            }
        },
        getRelationsFor(column) {
            const col = this.rcolumns.find(c => c.value === column);
            return col?.relations || [];
        },
        getComponentType(column) {
            const col = this.rcolumns.find(c => c.value === column);
            return col?.type || 'text';
        },
        getItemsFor(column) {
            const col = this.rcolumns.find(c => c.value === column);
            return col && col.source ? this[col.source] : [];
        },
        getItemTitle(column) {
            return {
                type: 'product_type_name',
                vendor: 'brand_name',
                tag: 'tag_name'
            }[column] || 'name';
            // const col = this.rcolumns.find(c => c.value === column);
            // return col?.itemTitle;
        },
        getItemValue(column) {
            return {
                type: 'product_type_id',
                vendor: 'brand_id',
                tag: 'tag_name' // or tag_id if available
            }[column] || 'id';
            // const col = this.rcolumns.find(c => c.value === column);
            // return col?.itemValue;
        },
        getLabel(column) {
            const col = this.rcolumns.find(c => c.value === column);
            return col?.name || 'Condition';
        },
        addNewRule() {
            const defaultCol = 'tag';
            const defaultRel = 'equals';
            const defaultCond = null;

            this.cat.rules.push({
                column: defaultCol,
                relation: defaultRel,
                condition: defaultCond
            });
        },
        removeRule(index) {
            this.cat.rules.splice(index, 1);
        },
        async fetchFilteredProductIds(){
            try {
                const resp = await axios.post('/sadmin/products/filter-ids', {
                    rules: this.cat.rules,
                    cat_rule: this.cat.cat_rule,
                })
                this.cat.productIds = resp.data.productIds;
            } catch (e) {
                console.error('Filter failed:', e.response?.data || e.message);
            }
        },
        async updatesCat(){
            if (this.cat.cat_type === 'smart') {
                await this.fetchFilteredProductIds();
            }
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const uscat = {
                cat_name:this.cat.cat_name,
                cat_slug:this.cat.cat_slug,
                cat_desc:this.quillContent,
                short_desc:this.cat.short_desc,
                cat_status:'Active',
                cat_image:this.newImage,
                cat_type:this.cat.cat_type,
                cat_rule:this.cat.cat_rule,
                sort_order:this.cat.sort_order,
                meta_title:this.cat.meta_title,
                meta_desc:this.cat.meta_desc,
                rules:this.cat.rules,
                product_ids:this.cat.productIds,
            }
            axios.post('/sadmin/cat/supdate/'+this.cat_id,uscat,uheaders)
                .then((resp)=>{
                    console.log(resp.data)
                    this.getCategory();
                    window.Toast.success('Smart Category Updated')
                })
                .catch((err)=>{
                    console.log(err)
                    window.Toast.error('Cat not Updated')
                })
                .finally(()=>{
                    this.newImage = null;
                })
        },
        addMpros() {
            const newOnes = this.selectedmPros.filter(
                selected => !this.cat.productIds.includes(selected.product_id)
            );
            this.cat.productIds.push(...newOnes.map(p => p.product_id));
            const existingIds = this.cat.epros.map(p => p.product_id);
            const freshProducts = newOnes.filter(p => !existingIds.includes(p.product_id));
            this.cat.epros.push(...freshProducts);
            this.selectedmPros = [];
            this.searchDialog = false;
        },
        onEditorChange(delta, oldDelta, source) {
            const quill = this.$refs.quillRef.getQuill();
            const squill = this.$refs.shortRef.getQuill();
            this.quillContent = quill.root.innerHTML;
            this.cat.short_desc = squill.root.innerHTML;
        },
        removeManualProduct(pid) {
            this.cat.productIds = this.cat.productIds.filter(id => id !== pid);
            this.cat.epros = this.cat.epros.filter(p => p.product_id !== pid);
        },
        closesSdialog(){
            this.searchDialog = false;
            this.selectedmPros = [];
        },
        updatemCat(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const umcat = {
                cat_name:this.cat.cat_name,
                cat_slug:this.cat.cat_slug,
                cat_desc:this.cat.cat_desc,
                short_desc:this.cat.short_desc,
                cat_status:'Active',
                cat_image:this.newImage,
                cat_type:this.cat.cat_type,
                cat_rule:this.cat.cat_rule,
                sort_order:this.cat.sort_order,
                meta_title:this.cat.meta_title,
                meta_desc:this.cat.meta_desc,
                rules:this.cat.rules,
                product_ids:this.cat.productIds,
            }
            axios.post('/sadmin/cat/mupdate/'+this.cat_id,umcat,uheaders)
                .then((resp)=>{
                    console.log(resp.data)
                })
                .catch((err)=>{
                    console.log(err)
                    window.Toast.error('Issue not Udpated')
                })
                .finally(()=>{
                    this.searchDialog = false;
                    this.getCategory();
                    this.newImage = null;
                    window.Toast.success('Category Updated')
                })
        },
        deleteCat(){
            axios.get('/sadmin/cat/delete/'+this.cat_id)
                .then((resp)=>{
                    this.delDialog = false;
                    window.Toast.success(resp.data.message);
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.$router.push({name:'cats'})
                })
        },
        addSection(){
            if (!this.selectToAdd) return;
            const sdata = {
                cat_id:this.cat_id,
                stype_id:this.selectToAdd.stype_id,
                section_json:this.selectToAdd,
                // sort_order:1,
                // selected_item:this.selectToAdd.stype_id
            }
            axios.post('/sadmin/cat/section/add/new',sdata)
                .then((resp)=>{
                    this.getCategory();
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
                await this.getCategory();
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
                    this.getCategory();
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
                    this.getCategory();
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
                    this.getCategory();
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
                    this.getCategory();
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
