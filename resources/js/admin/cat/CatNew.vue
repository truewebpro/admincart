<template>
    <v-container>
        <v-form ref="adcForm" v-model="acatValid" @submit.prevent="addNewCat">
            <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 1020">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link to="/categories" icon variant="tonal" density="compact">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        Create Category
                    </h2>
                </v-col>
                <v-col cols="12" md="6" class="text-end">
                    <v-btn @click="resetForm" variant="tonal" color="error" density="compact" class="text-none me-2">Discard</v-btn>
                    <v-btn variant="elevated" :disabled="!acatValid || isSaveDisabled || cisLoading" type="submit" color="grey-darken-4" density="compact" class="text-none">Save</v-btn>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" md="9">
                    <v-card class="border-sm">
                        <v-card-text>
                            <v-text-field v-model="cat.cat_name" variant="outlined" density="compact" label="Title"
                                          placeholder="Category Name / Title" :rules="ctitleRule"
                                          persistent-placeholder class="mb-3"></v-text-field>
                            <quill-editor ref="quillRef" v-model="cat.cat_desc" @text-change="onEditorChange"></quill-editor>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Category Type</v-card-title>
                        <v-card-text>
                            <v-radio-group v-model="cat.cat_type">
                                <v-radio label="Manual" value="manual"></v-radio>
                                <v-radio label="Smart" value="smart"></v-radio>
                            </v-radio-group>
                        </v-card-text>
                    </v-card>
                    <v-card v-if="cat.cat_type !== 'manual'" class="mt-4 border-sm">
                        <v-card-title>Conditions</v-card-title>
                        <v-card-text>
                            <div class="d-flex align-center">
                                <span class="text-body-1">Products must match:</span>
                                <v-radio-group inline hide-details v-model="cat.cat_rule">
                                    <v-radio label="and" value="and"></v-radio>
                                    <v-radio label="or" value="or"></v-radio>
                                </v-radio-group>
                            </div>
                            <div v-for="(rule, index) in cat.rules" :key="index" class="mb-4">
                                <v-row class="align-start">
                                    <v-col cols="12" md="4">
                                        <v-autocomplete
                                            v-model="rule.column"
                                            :items="rcolumns"
                                            item-title="name"
                                            item-value="value"
                                            label="Select Column"
                                            variant="outlined"
                                            density="compact"
                                            @update:modelValue="getRelations(rule)"
                                        />
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
                                        />
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <v-autocomplete
                                            v-if="getComponentType(rule.column) === 'autocomplete'"
                                            v-model="rule.condition"
                                            :items="getItemsFor(rule.column)"
                                            :item-title="getItemTitle(rule.column)"
                                            :item-value="getItemValue(rule.column)"
                                            :label="getLabel(rule.column)"
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
                                    <v-col cols="12" md="1" class="d-flex align-end">
                                        <v-btn size="x-small" color="red" variant="outlined" icon
                                               @click="removeRule(index)" v-if="cat.rules.length > 1">
                                            <v-icon size="medium">mdi-delete</v-icon>
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </div>
                            <!-- Add/Apply Buttons -->
                            <v-btn @click="addRule" prepend-icon="mdi-plus" variant="outlined" density="compact" class="mb-3">
                                Add another condition
                            </v-btn>
                            <v-btn color="primary" @click="fetchFilteredProductIds" variant="outlined" density="compact" class="mx-2 mb-3">
                                Apply Rule
                            </v-btn>
                        </v-card-text>
                    </v-card>
                    <v-card v-if="cat.cat_type === 'manual'" class="mt-4 border-sm">
                        <v-card-title>Products</v-card-title>
                        <v-card-text>
                            <v-row class="d-flex">
                                <v-col cols="12" md="5">
                                    <v-text-field variant="outlined" density="compact" placeholder="Search Products"
                                                  prepend-inner-icon="mdi-magnify"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="2">
                                    <v-btn variant="outlined" block @click="searchDialog = true">Browse</v-btn>
                                </v-col>
                                <v-col cols="12" md="5">
                                    <v-select v-model="cat.sort_order" variant="outlined" density="compact" :items="sortby"
                                              item-title="name"></v-select>
                                </v-col>
                            </v-row>
                            <v-divider></v-divider>
                        </v-card-text>
                        <v-data-table :items="spros" :headers="sprosHeaders" hide-default-footer>
                            <template v-slot:item.featured_image="{item}">
                                <v-img v-if="item.featured_image != null" :src="cdn+item.featured_image" max-width="60" class="my-1"></v-img>
                                <v-img v-else :src="cdn+'noimage.png'" max-width="60" class="my-1"></v-img>
                            </template>
                            <template v-slot:item.action="{ item }">
                                <v-btn color="red" density="compact" icon @click="removeSpro(item)">
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card>
                    <v-card v-if="cat.cat_type === 'smart'"  class="mt-4 border-sm">
                        <v-card-title>Products</v-card-title>
                        <v-card-text>
                            <v-select v-model="cat.sort_order" variant="outlined" density="compact" :items="sortby" item-title="name"></v-select>
                            <v-data-table hide-default-footer>

                            </v-data-table>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Search engine listing</v-card-title>
                        <v-card-subtitle>Add a title and description to see how this category might appear in a search engine listing</v-card-subtitle>
                        <v-card-text>
                            <div class="mb-4">
                                <v-text-field v-model="cat.meta_title" variant="outlined" density="compact" label="Page Title"
                                              counter="60" persistent-counter :placeholder="cat.cat_name"
                                              persistent-placeholder></v-text-field>
                            </div>
                            <div class="mb-4">
                                <v-textarea v-model="cat.meta_desc" variant="outlined" density="compact" label="Meta Description"
                                            counter="160" persistent-counter
                                            persistent-placeholder></v-textarea>
                            </div>
                            <div>
                                <v-text-field v-model="cat.cat_slug" variant="outlined" density="compact" persistent-placeholder
                                              persistent-hint :hint="domain+'collections/'" :prefix="domain+'collections/'"
                                              :placeholder="cat.cat_name"></v-text-field>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="3">
                    <v-card>
                        <v-card-title>Publishing</v-card-title>
                        <v-card-subtitle>Sales Channel</v-card-subtitle>
                        <v-card-text>
                            <v-checkbox-btn v-model="cat.cat_status" label="Online" value="Active" hide-details></v-checkbox-btn>
                            <v-checkbox-btn label="Other" hide-details></v-checkbox-btn>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border">
                        <v-card-title>Image</v-card-title>
                        <v-card-text>
                            <v-file-upload v-model="cat.cat_image" density="compact" browse-text="Add Image"
                                           icon="mdi-upload" clearable
                                           title="Add Image"
                            ></v-file-upload>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-form>
        <v-dialog max-width="450" v-model="searchDialog" persistent>
            <v-card>
                <v-text-field v-model="psearch" density="compact" variant="underlined" hide-details
                              prepend-inner-icon="mdi-magnify" clearable class="mx-2 mb-1"></v-text-field>
                <v-data-table :items="pros" :search="psearch" :headers="prosHeaders" show-select
                              density="compact" v-model="selectedPros" return-object>
                    <template v-slot:item.featured_image="{item}">
                        <v-img v-if="item.featured_image != null" :src="cdn+item.featured_image" max-width="60" class="my-1"></v-img>
                        <v-img v-else :src="cdn+'noimage.png'" max-width="60" class="my-1"></v-img>
                    </template>
                </v-data-table>
                <v-card-actions>
                    <v-btn @click="addSpros" :disabled="selectedPros.length < 1" variant="elevated" color="success" density="compact"
                           class="font-weight-bold">Add products</v-btn>
                    <v-spacer/>
                    <v-btn variant="elevated" @click="closesSdialog" color="red" density="compact" class="font-weight-bold">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script>
import axios from "axios";
import {VFileUpload} from "vuetify/labs/components";
import {QuillEditor} from "@vueup/vue-quill";

export default {
    name:"CatView",
    components:{QuillEditor, VFileUpload},
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
            // Remove HTML tags
            return this.cat.cat_desc
                .replace(/<br\s*\/?>/gi, '\n')
                .replace(/<[^>]+>/g, '');
        },
        isSaveDisabled() {
            if (this.cat.cat_type === 'smart') {
                return (
                    !this.acatValid ||
                    this.cat.rules.length === 0 ||
                    this.cat.rules.some(rule =>
                        !rule.column || !rule.relation || rule.condition === null || rule.condition === ''
                    )
                );
            }
            return !this.acatValid;
        }
    },
    watch: {
        'cat.cat_type'(val) {
            if (val === 'smart') {
                // Add default rule only if none exist
                if (this.cat.rules.length === 0) {
                    this.cat.rules.push({
                        column: 'tag',
                        relation: 'equals',
                        condition: null
                    });
                }

                // Always reset manual selections
                this.selectedPros = [];
                this.spros = [];
            } else if (val === 'manual') {
                // Clear smart conditions when switching to manual
                this.cat.rules = [];
                this.cat.productIds = [];
            }
        },
        'cat.cat_name'(newSlug){
            this.cat.cat_slug = newSlug
                .toLowerCase()
                .trim()
                .replace(/[\s\W-]+/g,'-')
                .replace(/^-+|-+$/g,'')
        }
    },
    data(){
        return{
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+"/",
            cdn:this.$store.state.cdn,
            cisLoading:false,
            acatValid:false,
            searchDialog:false,
            psearch:'',
            selectedPros:[],
            pros:[],
            prosHeaders:[
                {title:"Image",value:'featured_image',width:80},
                {title:"Tile",value:'title'},
            ],
            spros:[],
            sprosHeaders:[
                {title:"Image",value:'featured_image',width:80},
                {title:"Tile",value:'title'},
                {title:"Action",value:'action',width: 80},
            ],
            sortby:[
                {name:'Product Title A-Z',value:'title_asc'},
                {name:'Product Title Z-A',value:'title_desc'},
                {name:'Highest Price',value:'price_asc'},
                {name:'Lowest Price',value:'price_desc'},
                {name:'Newest',value:'created_asc'},
                {name:'Oldest',value:'created_desc'},
            ],
            cat:{
                cat_image:null,
                cat_name:'',
                cat_slug:'',
                cat_status:'Active',
                cat_type:'manual',
                cat_desc:'',
                cat_rule:'and',
                sort_order:'title_asc',
                meta_title:'',
                meta_desc:'',
                rules:[{ column: 'tag', relation: 'equals', condition: null,componentKey: Date.now() }],
                productIds:[],
            },
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
            ]
        }
    },
    created() {
        this.getCategory();
    },
    methods:{
        getCategory(){
            axios.get('/sadmin/cats/new')
                .then((resp)=>{
                    this.pros = resp.data.pros;
                })
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
            const col = this.rcolumns.find(c => c.value === column);
            return col?.itemTitle;
        },
        getItemValue(column) {
            const col = this.rcolumns.find(c => c.value === column);
            return col?.itemValue;
        },
        getLabel(column) {
            const col = this.rcolumns.find(c => c.value === column);
            return col?.name || 'Condition';
        },
        addRule() {
            const defaultCol = 'tag';
            const defaultRel = 'equals';
            const defaultCond = '';

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
        async addNewCat(){
            this.cisLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            if (this.cat.cat_type === 'smart') {
                await this.fetchFilteredProductIds(); // sets this.cat.productIds internally
            }
            if (this.cat.cat_type === 'manual') {
                this.cat.productIds = this.spros.map(p => p.product_id);
                this.cat.rules = []; // clear rules for manual category
            }
            const ncat = {
                cat_name:this.cat.cat_name,
                cat_slug:this.cat.cat_slug,
                cat_desc:this.cat.cat_desc,
                cat_status:'Active',
                cat_image:this.cat.cat_image,
                cat_type:this.cat.cat_type,
                cat_rule:this.cat.cat_rule,
                sort_order:this.cat.sort_order,
                meta_title:this.cat.meta_title || this.cat.cat_name,
                meta_desc:this.cat.meta_desc,
                rules:this.cat.rules,
                product_ids:this.cat.productIds,
            }
            axios.post('/sadmin/cat/new',ncat,uheaders)
                .then((resp)=>{
                    this.$router.push({name:'CatView',params:{cat_id:resp.data.cat_id}})
                    window.Toast.success('Collection added Successfully')
                    console.log(resp.data);
                })
                .catch((err)=>{
                    console.log(err.message);
                    window.Toast.error('Some Error to add Collection')
                })
                .finally(()=>{
                    this.cisLoading = false;
                })
        },
        onEditorChange(delta, oldDelta, source){
            const quill = this.$refs.quillRef.getQuill();
            this.cat.cat_desc = quill.root.innerHTML;
        },
        resetForm(){
            this.$refs.adcForm.reset();
            this.$router.push({name:'cats'});
        },
        closesSdialog(){
            this.searchDialog = false;
            this.selectedPros = [];
        },
        addSpros(){
            const newOnes = this.selectedPros.filter(
                selected => !this.spros.some(sp => sp.product_id === selected.product_id)
            );

            this.spros.push(...newOnes);
            this.selectedPros = [];
            this.searchDialog = false;
        },
        removeSpro(item) {
            this.spros = this.spros.filter(p => p.product_id !== item.product_id);
        }
    }
}

</script>

<style scoped>

</style>
