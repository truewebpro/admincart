<template>
    <div class="newpro v-container">
        <v-form ref="adForm" v-model="avalid" @submit.prevent="addNewPro">
            <v-row class="position-sticky top-0 bg-grey-lighten-3" style="z-index: 99">
                <v-col cols="12" md="6">
                    <h2 class="text-h6">
                        <v-btn link to="/products" icon variant="tonal" density="compact">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        Add Product new1
                    </h2>
                </v-col>
                <v-col cols="12" md="6" class="text-end">
                    <v-btn variant="outlined" @click="resetForm" class="text-none me-2" density="compact" color="grey-darken-4">Discard</v-btn>
                    <v-btn color="success" type="submit" :disabled="!avalid || isLoading" :loading="isLoading"
                           density="compact" class="text-none" >Save</v-btn>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" md="9">
                    <v-card>
                        <v-card-text>
                            <v-text-field v-model="npro.prodname" :rules="nameRule" variant="outlined" label="Title"
                                          density="compact" counter="80" class="mb-3" persistent-placeholder></v-text-field>
                            <v-textarea v-model="npro.short_description" variant="outlined" label="Short Description"
                                        persistent-placeholder counter="500" persistent-counter
                                        class="mb-2" density="compact"></v-textarea>
                            <SemiRichTextEditor v-model="npro.quillContent"/>
                            <div class="mt-3">
                                <v-card flat class="d-flex align-center border justify-center" height="90" outlined @click="triggerFileInput">
                                    <v-icon v-if="!featuredImage" size="40" color="grey darken-1">mdi-image-area</v-icon>
                                    <v-img v-else :src="featuredImage" contain height="100%"></v-img>
                                    <v-file-input ref="fileInput" v-model="productImage" accept="image/*" hide-input @change="handleFileUpload" style="display: none"></v-file-input>
                                </v-card>
                            </div>
                        </v-card-text>
                    </v-card>
                    <v-card v-if="!Object.keys(variants).length > 0" class="mt-4">
                        <v-card-title>Pricing</v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="price" variant="outlined" label="Price" density="compact"
                                                  prefix="£" persistent-placeholder placeholder="0.00"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="compareprice" variant="outlined" label="Compare Price" density="compact"
                                                  prefix="£" persistent-placeholder placeholder="0.00" append-inner-icon="mdi-progress-question"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="costprice" variant="outlined" label="Cost Price" density="compact"
                                                  prefix="£" persistent-placeholder placeholder="0.00"></v-text-field>
                                </v-col>
                                <v-col cols="12" class="pt-0">
                                    <v-checkbox-btn v-model="istax" label="Charge tax on this product" :value="1" hide-details></v-checkbox-btn>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                    <v-card v-if="!Object.keys(variants).length > 0" class="mt-4">
                        <v-card-title>Inventory</v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="sku" variant="outlined" label="SKU" density="compact"
                                                  persistent-placeholder placeholder="SKU001"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="stockQuantity" variant="outlined" label="Stock" density="compact"
                                                  persistent-placeholder placeholder="0"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="barcode" variant="outlined" label="Barcode" density="compact"
                                                  prefix="UPC" persistent-placeholder placeholder="876543219011"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="4" class="pb-0">
                                    <v-text-field v-model="weight" variant="outlined" label="Weight" density="compact"
                                                  persistent-placeholder placeholder="0.00">
                                        <template v-slot:append>
                                            <v-select v-model="npro.weight_unit" variant="outlined" density="compact" :items="weight_units" hide-details></v-select>
                                        </template>
                                    </v-text-field>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Variants</v-card-title>
                        <v-card-text v-if="Object.keys(variants).length >= 0">
                            <v-container v-for="(variant, key) in variants" :key="key" class="px-0">
                                <v-row class="align-center">
                                    <v-col cols="12" md="10" class="d-flex align-center flex-wrap">
                                        <span class="font-weight-bold text-h6 mr-2">{{ key }}:</span>
<!--                                        <v-chip v-for="(opvalue, index) in variant" :key="index" variant="outlined"-->
<!--                                                @click:close="removeValue(key, opvalue)" closable density="compact"-->
<!--                                                color="primary" class="me-1 mb-1 text-capitalize">-->
<!--                                            {{ opvalue }}-->
<!--                                        </v-chip>-->
                                        <v-chip v-for="value in variants[key]" variant="outlined" class="me-1 mb-1 text-capitalize"
                                            :key="key + '::' + value" density="compact" color="primary"
                                            closable @click:close="removeValue(key, value)">
                                            {{ value }}
                                        </v-chip>
                                    </v-col>
                                    <v-col cols="12" md="2" class="ps-0 d-flex justify-end">
                                        <v-btn icon color="primary" density="compact" variant="text">
                                            <v-icon @click="editVariant(key)">mdi-pencil</v-icon>
                                        </v-btn>
                                        <v-btn icon color="red" density="compact" variant="text">
                                            <v-icon @click="removeOption(key)">mdi-delete</v-icon>
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </v-container>
                            <v-container class="px-0 pt-1">
                                <v-btn v-if="!showVariantForm && Object.keys(variants).length < 1" color="bg-grey-darken-4"
                                       @click="startNewOption" variant="outlined" density="compact"
                                       class="mb-3" prepend-icon="mdi-plus">Add Options
                                </v-btn>
                                <v-btn v-if="Object.keys(variants).length > 0 && Object.keys(variants).length < 3 && !showVariantForm"
                                       color="bg-grey-darken-4" @click="startNewOption" variant="outlined" density="compact"
                                       class="mt-3" prepend-icon="mdi-plus">
                                    Add Another Option
                                </v-btn>
                                <v-row v-if="showVariantForm" class="align-center">
                                    <v-col cols="12" md="12">
                                        <v-select v-model="selectedOption" :items="filteredOptions" item-title="option_name" item-value="option_name"
                                                  label="Option Name" density="compact" variant="outlined"
                                                  hide-details></v-select>
                                        <div v-for="(val,index) in optionValueInputs" :key="index" class="d-flex align-center my-2">
                                            <v-text-field
                                                v-model="optionValueInputs[index]"
                                                :label="selectedOption+ ' value'"
                                                density="compact"
                                                variant="outlined"
                                                class="flex-grow-1 me-2"
                                                :error="inputHasError(index)"
                                                :error-messages="inputError(index) ? [inputError(index)] : []"
                                            >
                                                <template v-slot:append-inner>
                                                    <v-btn icon color="dark" variant="text" density="compact"
                                                           @click="removeOptionField(index)"
                                                           v-if="optionValueInputs.length > 1">
                                                        <v-icon>mdi-trash-can-outline</v-icon>
                                                    </v-btn>
                                                </template>
                                            </v-text-field>
                                        </div>
                                        <div class="d-flex">
                                            <v-btn prepend-icon="mdi-plus-circle-outline" color="grey-darken-5" variant="outlined" class="me-2 text-none" size="small"
                                                   @click="addOptionField" :disabled="!canAddAnother">
                                               Add another value
                                            </v-btn>
                                            <v-spacer/>
                                            <v-btn size="small" color="primary" class="ms-2"
                                                   @click="saveVariant"
                                                   :disabled="!canSaveVariant">
                                                {{isEditingVariant ? 'Update' : 'Save' }}
                                            </v-btn>
                                            <v-btn color="red" @click="cancelVariant" class="ms-2" size="small">
                                                {{isEditingVariant ? 'Close' : 'Remove'}}
                                            </v-btn>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-card-text>
                        <div v-if="Object.keys(variants).length > 0">
                            <v-data-table :items="vitems" :headers="variantHeaders" :key="tableKey" class="elevation-1"
                                          density="compact" items-per-page="50" :hide-default-footer="vitems.length < 49">
                                <template v-slot:item.variantImage="{ item, index }">
                                    <v-card class="d-flex align-center justify-center my-1" height="50" width="50" outlined @click="triggerVariantFileInput(index)">
                                        <v-icon v-if="!item.variantImage" size="50" color="grey darken-1">mdi-image-area</v-icon>
                                        <v-img v-else :src="item.preview" contain height="100%" max-width="50px"></v-img>
                                        <input type="file" :ref="'variantImage' + index" accept="image/*" @change="handleVariantFileUpload($event, index)" style="display: none" />
                                    </v-card>
                                </template>
                                <template v-slot:item.optname="{ item }">
                                    <span class="optvals d-flex flex-wrap">
                                        <v-chip v-for="(val, key) in item.optvalue" :key="key" color="primary" class="text-truncate me-1 mb-1" variant="outlined" density="compact">
                                            {{ val }}
                                        </v-chip>
                                    </span>
                                </template>
                                <template v-slot:item.price="{ item }">
                                    <v-text-field width="90px" v-model="item.price" prefix="£" hide-details density="compact" variant="outlined"></v-text-field>
                                </template>
                                <template v-slot:item.stock="{ item }">
                                    <v-text-field width="90px" v-model.number="item.stock" hide-details density="compact" variant="outlined"></v-text-field>
                                </template>
                                <template v-slot:item.sku="{ item }">
                                    <v-text-field width="150px" v-model="item.sku" hide-details density="compact" variant="outlined"></v-text-field>
                                </template>
                            </v-data-table>
                        </div>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Search engine listing</v-card-title>
                        <v-card-subtitle>Add a title and description to see how this category might appear in a search engine listing</v-card-subtitle>
                        <v-card-text>
                            <div class="mb-4">
                                <v-text-field v-model="npro.meta_title" variant="outlined" density="compact" label="Page Title"
                                              counter="60" :placeholder="npro.prodname"
                                              persistent-counter persistent-placeholder></v-text-field>
                            </div>
                            <div class="mb-4">
                                <v-textarea v-model="npro.meta_desc" variant="outlined" density="compact" label="Meta Description"
                                            counter="160" :placeholder="npro.prodname"
                                            persistent-counter persistent-placeholder></v-textarea>
                            </div>
                            <div class="d-none">
                                <v-text-field v-model="npro.handle" variant="outlined" density="compact" persistent-placeholder
                                              persistent-hint :prefix="domain+'products/'"></v-text-field>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="3">
                    <v-card class="border-sm">
                        <v-card-title>Status</v-card-title>
                        <v-card-text>
                            <v-select v-model="npro.product_status" variant="outlined" density="compact" label="Status"
                                      :items="prostatus" persistent-placeholder></v-select>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Publishing</v-card-title>
                        <v-card-subtitle>Sales Channels</v-card-subtitle>
                        <v-card-text>
                            <v-checkbox-btn v-model="npro.publish_status" label="Online" value="online" hide-details></v-checkbox-btn>
                            <v-checkbox-btn v-model="npro.publish_status" label="App" value="app" hide-details></v-checkbox-btn>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Product Organization</v-card-title>
                        <v-card-text>
                            <v-autocomplete ref="ptypeInputRef" v-model="npro.product_type_id" variant="outlined" density="compact" label="Type"
                                        :items="ptypes" item-title="product_type_name" item-value="product_type_id" :no-data-text="typedPtype || 'No types found'"
                                        persistent-placeholder :return-object="false" :rules="ptypeRule" class="mb-3"
                                        clearable @keydown="onPtypeInput">
                                <template #no-data>
                                    <v-btn variant="text" class="text-none" size="small" @click="addNewProductType" :disabled="!typedPtype || !typedPtype.trim()" >
                                        Add "{{ typedPtype }}"
                                    </v-btn>
                                </template>
                            </v-autocomplete>
                            <v-autocomplete ref="brandInputRef" v-model="npro.brand_id" variant="outlined" density="compact" label="Brand"
                                        :items="pbrands" item-title="brand_name" item-value="brand_id" :no-data-text="typedBrand || 'No Brand found' "
                                        persistent-placeholder :return-object="false" :rules="brandRule" class="mb-3"
                                        clearable @keydown="onBrandInput">
                                <template #no-data>
                                    <v-btn variant="text" class="text-none" size="small" @click="addNewBrand" :disabled="!typedBrand || !typedBrand.trim()" >
                                        Add "{{ typedBrand }}"
                                    </v-btn>
                                </template>
                            </v-autocomplete>
                            <v-autocomplete ref="tagInputRef" v-model="npro.tags" variant="outlined" density="compact" label="Tags" :items="ptags"
                                        item-title="tag_name" item-value="tag_name" :return-object="false" :no-data-text="typedTag || 'No Tag found'"
                                        persistent-placeholder multiple chips closable-chips
                                        @keydown="onTagInput">
                                <template #no-data>
                                    <v-btn variant="text" class="text-none" size="small" @click="addNewTag" :disabled="!typedTag || !typedTag.trim()" >
                                        Add "{{ typedTag }}"
                                    </v-btn>
                                </template>
                            </v-autocomplete>
                        </v-card-text>
                    </v-card>
                    <v-card class="mt-4 border-sm">
                        <v-card-title>Template</v-card-title>
                        <v-card-text>
                            <v-select v-model="npro.product_template" variant="outlined" density="compact" label="Template"
                                      :items="protemplates" persistent-placeholder></v-select>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-form>
    </div>
</template>
<script>
import axios from "axios";
import {toRaw} from "vue";
import SemiRichTextEditor from "@/components/SemiRichTextEditor.vue";
export default{
    name:"ProductsNew",
    components: {SemiRichTextEditor},
    data(){
        return{
            selectedOption: "",
            optionValueInputs:[''],
            variants: {},
            isEditingVariant: false,
            previousOptionKey: null,
            showVariantForm: false,
            isLoading:false,
            avalid:false,
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+'/',
            optname:{},
            optvalue:{},
            vitems:[],
            tableKey: 0,
            featuredImage:null,
            productImage:null,
            price:0,
            istax:1,
            compareprice:0,
            costprice:0,
            sku:'',
            stockQuantity:0,
            barcode:'',
            weight:0.500,
            weightUnit:"kg",
            optionValues: "",
            weight_units:['kg','g'],
            prostatus:['Active','Draft'],
            protemplates:['Default','Template 1','Template 2'],
            location:{},
            npro:{
                prodname:"",
                short_description:"",
                quillContent:'',
                product_status:'Active',
                publish_status:'online',
                product_type_id: null,
                brand_id:'',
                tags:[],
                weight_unit:'kg',
                meta_title:'',
                meta_desc:'',
                product_template:'Default',
                handle:'',
            },
            nameRule:[(v) => !!v || "Name is required",],
            ptypeRule:[(v) => !!v || "Type is required",],
            brandRule:[(v) => !!v || "Brand is required",],
            typedPtype:"",
            ptypes:[],
            typedBrand:"",
            pbrands:[],
            typedTag:"",
            ptags:[],
            poptions:[],
        }
    },
    created() {
        this.generateSKU();
    },
    mounted() {
        this.getProDetails();
        this.prepareVariantsForSave();
    },
    computed: {
        optionInputsTrimmed() {
            return this.optionValueInputs.map(v => (v ?? '').trim());
        },
        isAllUniqueCI() {
            const lowers = this.optionInputsTrimmed.map(v => v.toLowerCase());
            return lowers.length === new Set(lowers).size;
        },
        canAddAnother() {
            return this.optionInputsTrimmed.every(v => v.length > 0) && this.isAllUniqueCI;
        },
        canSaveVariant() {
            return !!this.selectedOption && this.optionValueInputs.length > 0 && this.canAddAnother;
        },
        variantItems(){
            let items = [];
            const optionKeys = Object.keys(this.variants);
            const optionValues = Object.values(this.variants);
            if(optionValues.length === 0) return items;
            let baseSKU = this.sku || Math.random().toString(36).substring(2,7).toLowerCase();
            let vprice = this.price;
            let vcomprice = this.compareprice;
            let vcostprice = this.costprice;
            let vstockQuantity = this.stockQuantity;
            let vbarcode = this.barcode;
            let variantCount = 0;
            const generateCombinations = (arrays,prefix = []) =>{
                if(!arrays.length){
                    let skuSuffix = variantCount === 0 ? "" : `-${variantCount}`;
                    items.push({
                        variantImage: null,
                        variant: optionKeys.map((key, i) => `${key}: ${prefix[i]}`).join(" / "),
                        price: `${vprice}`,
                        compareprice: `${vcomprice}`,
                        costprice: `${vcostprice}`,
                        stock: `${vstockQuantity}`,
                        sku: `${baseSKU}${skuSuffix}`,
                        barcode: `${vbarcode}`,
                        optname: optionKeys,
                        optvalue: optionKeys.reduce((acc, key, i) =>
                        {
                            acc[key] = prefix[i];
                            return acc;
                        }, {})
                    });
                    variantCount++
                    return
                }
                const firstArray = arrays[0];
                const remainingArrays = arrays.slice(1);
                firstArray.forEach(value => {
                    if (value && value.trim() !== '') {
                        generateCombinations(remainingArrays, [...prefix, value]);
                    }
                });
            };
            generateCombinations(optionValues);
            return items;
        },
        variantHeaders() {
            return [
                { title: "Image", value: "variantImage", editable: true,maxWidth:"60px" },
                { title: this.variantItems[0]?.optname?.join(' / '), value: "optname" },
                { title: "Price", value: "price", editable: true },
                { title: "Available", value: "stock", editable: true },
                { title: "SKU", value: "sku" },
            ];
        },
        filteredOptions() {
            return this.poptions.filter(option => {
                return (
                    option.option_name === this.selectedOption || !this.variants.hasOwnProperty(option.option_name)
                );
            });
        },
    },
    watch:{
        variantItems: {
            handler(newValue) {
                this.vitems = newValue.map(item => ({
                    ...item,
                    variantImage: item.variantImage || null,
                    preview: item.preview || null,
                }));
            },
            immediate: true
        },
        'npro.prodname'(newTitle) {
            this.npro.handle = newTitle
                .toLowerCase()
                .trim()
                .replace(/[\s\W-]+/g, '-') // Replace spaces/special chars with -
                .replace(/^-+|-+$/g, '')   // Remove leading/trailing hyphens
        }
    },
    methods:{
        startNewOption() {
            this.selectedOption = '';
            this.optionValueInputs = [''];
            this.showVariantForm = true;
            this.isEditingVariant = false;
            this.previousOptionKey = null;
        },
        prepareVariantsForSave(){
            const rawVariants = toRaw(this.variants);
            let optionKeys = Object.keys(rawVariants);
            let optionValues = Object.values(rawVariants);
            optionKeys = optionKeys.map(k => k.trim());            // clean up keys
            optionValues = optionValues.map(arr => arr.filter(v => v !== '')); // remove empty strings

            // Combine them back into an object to save
            const finalVariants = {};
            optionKeys.forEach((key, index) => {
                finalVariants[key] = optionValues[index];
            });
            return finalVariants;
        },
        addOptionField(){
            if (!this.canAddAnother) return;
            this.optionValueInputs.push("");
        },
        removeOptionField(index){
            this.optionValueInputs.splice(index,1)
        },
        saveVariant() {
            if (!this.canSaveVariant) return;
            // case-insensitive unique, keep original casing of first occurrence
            const seen = new Set();
            const newValues = [];
            for (const v of this.optionInputsTrimmed) {
                const key = v.toLowerCase();
                if (!seen.has(key)) {
                    seen.add(key);
                    newValues.push(v);
                }
            }

            const optionKey = this.selectedOption;
            if (this.isEditingVariant && this.previousOptionKey && this.previousOptionKey !== optionKey) {
                const existingValues = this.variants[this.previousOptionKey];
                delete this.variants[this.previousOptionKey];
                this.variants[optionKey] = newValues.length ? newValues : existingValues;
            } else {
                this.variants[optionKey] = newValues;
            }
            this.prepareVariantsForSave();
            this.cancelVariant();
        },
        cancelVariant() {
            this.selectedOption = "";
            this.optionValueInputs = [""];
            this.showVariantForm = false;
            this.isEditingVariant = false;
            this.previousOptionKey = null;
        },
        editVariant(key){
            if(this.variants[key]){
                this.previousOptionKey = key;
                this.selectedOption = key;
                this.optionValueInputs = [...this.variants[key]];
                this.showVariantForm = true;
                this.isEditingVariant = true;
            }
        },
        inputHasError(idx) {
            const v = (this.optionValueInputs[idx] ?? '').trim();
            if (!v) return true; // empty
            const lowers = this.optionInputsTrimmed.map(x => x.toLowerCase());
            const firstIdx = lowers.indexOf(v.toLowerCase());
            return firstIdx !== -1 && firstIdx !== idx; // duplicate elsewhere
        },
        inputError(idx) {
            const v = (this.optionValueInputs[idx] ?? '').trim();
            if (!v) return 'Add value';
            const lowers = this.optionInputsTrimmed.map(x => x.toLowerCase());
            const firstIdx = lowers.indexOf(v.toLowerCase());
            if (firstIdx !== -1 && firstIdx !== idx) return 'Duplicate';
            return '';
        },
        async getProDetails(){
            await axios.get('/sadmin/pros/new')
                .then((resp)=>{
                    const respData = resp.data;
                    this.location = respData.location;
                    this.pbrands = respData.brands || [];
                    this.ptypes = respData.ptypes || [];
                    this.ptags = respData.tags || [];
                    this.poptions = respData.poptions || [];
                });
        },
        generateSKU() {
            if (!this.sku) {
                this.sku = Math.random().toString(36).substring(2, 7).toUpperCase();
            }
        },
        regenerateSKU() {
            this.sku = Math.random().toString(36).substring(2, 7).toUpperCase();
        },
        triggerFileInput() {
            this.$refs.fileInput.click();
        },
        handleFileUpload() {
            if (this.productImage) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.featuredImage = e.target.result;
                };
                reader.readAsDataURL(this.productImage);
            }
        },
        triggerVariantFileInput(index) {
            const fileInput = this.$refs[`variantImage${index}`];

            if (fileInput) {
                fileInput.click();
            } else {
                console.error(`File input for index ${index} is undefined`);
            }
        },
        handleVariantFileUpload(event, index) {
            const file = event.target.files[0];
            if (!file) return;
            const previewURL = URL.createObjectURL(file);
            if (this.vitems && this.vitems[index]) {
                this.vitems.splice(index, 1, {
                    ...this.vitems[index],
                    variantImage: file,
                    preview: previewURL
                });
                this.tableKey++;
            }
        },
        removeOption(key) {
            if (this.variants[key]) {
                delete this.variants[key];
                if (this.isEditingVariant && this.previousOptionKey === key) {
                    this.cancelVariant();
                }
            }
        },
        removeValue(key, value) {
            if (this.variants[key]) {
                this.variants[key] = [...this.variants[key].filter(v => v !== value)];
                if (this.variants[key].length === 0) {
                    this.removeOption(key);
                }
            }
            if (this.isEditingVariant && (this.previousOptionKey === key || this.selectedOption === key)) {
                this.optionValueInputs = this.optionValueInputs.filter(v => v !== value);
            }
        },
        addNewPro(){
            this.isLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            let svar; // declare once with let
            if (this.vitems.length < 1) {
                svar = [{
                    istax: this.istax,
                    price: this.price,
                    compareprice: this.compareprice,
                    costprice: this.costprice,
                    sku: this.sku,
                    stock: this.stockQuantity,
                    barcode: this.barcode,
                    weight: this.weight,
                    isdefault: 0,
                    weightUnit: this.weightUnit,
                    variantImage: this.variantImage || null,
                    optvalue: {},
                    optname: [],
                }];
            } else {
                svar = this.vitems;
            }
            const nprod = {
                'title':this.npro.prodname,
                'publish_status':this.npro.publish_status,
                'short_description':this.npro.short_description,
                'body_html':this.npro.quillContent,
                'featured_image':this.productImage,
                'product_status':this.npro.product_status,
                'product_type_id':this.npro.product_type_id,
                'brand_id':this.npro.brand_id,
                'tags':this.npro.tags,
                'meta_title':this.npro.meta_title,
                'meta_desc':this.npro.meta_desc,
                'location_id':this.location.location_id,
                variants:svar
            }
            axios.post('/sadmin/product/new',nprod,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.$router.push({name:'ProductEdit',params:{product_id:resp.data.product_id}});
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        resetForm(){
            this.$refs.adForm.reset();
            this.$router.push({name:'products'});
        },
        onPtypeInput() {
            setTimeout(() => {
                const inputEl = this.$refs.ptypeInputRef?.$el?.querySelector('input');
                if (inputEl) {
                    this.typedPtype = inputEl.value;
                }
            }, 10);
        },
        async addNewProductType(){
            const name = this.typedPtype?.trim();
            if (!name) return;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const aptype = {
                product_type_name: name,
                product_type_status: "Active",
            }
            axios.post('/sadmin/ptype/update',aptype,uheaders)
                .then((resp)=>{
                    if(resp.data.success && resp.data.ptype){
                        const newType = resp.data.ptype;
                        // const exists = this.ptypes.some(pt => pt.product_type_id === newType.product_type_id);
                        // if (!exists) {
                        //     this.ptypes.push(newType);
                        // }
                        this.npro.product_type_id = newType.product_type_id;
                        this.typedPtype = '';
                        const inputEl = this.$refs.ptypeInputRef?.$el?.querySelector('input');
                        if (inputEl) inputEl.value = '';
                        this.getProDetails();
                        window.Toast.success('New Type Added')
                    }
                })
                .catch((error)=>{
                    window.Toast.error('Some error to add Product Type')
                })
        },
        onBrandInput() {
            setTimeout(() => {
                const inputEl = this.$refs.brandInputRef?.$el?.querySelector('input');
                if (inputEl) {
                    this.typedBrand = inputEl.value;
                }
            }, 10);
        },
        async addNewBrand(){
            const name = this.typedBrand?.trim();
            if (!name) return;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const abrand = {
                brand_name: name,
                brand_status: "Active",
            }
            axios.post('/sadmin/brand/update',abrand,uheaders)
                .then((resp)=>{
                    if(resp.data.success && resp.data.brand){
                        const newBrand = resp.data.brand;
                        // const exists = this.pbrands.some(pt => pt.brand_id === newBrand.brand_id);
                        // if (!exists) {
                        //     this.pbrands.push(newBrand);
                        // }
                        this.npro.brand_id = newBrand.brand_id;
                        this.typedBrand = '';
                        const inputEl = this.$refs.brandInputRef?.$el?.querySelector('input');
                        if (inputEl) inputEl.value = '';
                        window.Toast.success('New Brand Added')
                    }
                    this.getProDetails();
                })
                .catch((error)=>{
                    window.Toast.error('Some error to add Brand')
                })
        },
        onTagInput() {
            setTimeout(() => {
                const inputEl = this.$refs.tagInputRef?.$el?.querySelector('input');
                if (inputEl) {
                    this.typedTag = inputEl.value;
                }
            }, 10);
        },
        async addNewTag(){
            const name = this.typedTag?.trim();
            if (!name) return;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const atag = {
                tag_name: name,
                tag_status: "Active",
            };
            axios.post('/sadmin/tag/update',atag,uheaders)
                .then((resp)=>{
                    if(resp.data.success && resp.data.tag){
                        const newTag = resp.data.tag;
                        // const exists = this.ptags.some(tag => tag.tag_id === newTag.tag_id);
                        // if (!exists) {
                        //     this.ptags.push(newTag);
                        // }
                        // if (!this.npro.tags.includes(newTag.tag_name)) {
                        //     this.npro.tags.push(newTag.tag_name);
                        // }
                        this.typedTag = '';
                        const inputEl = this.$refs.tagInputRef?.$el?.querySelector('input');
                        if (inputEl) inputEl.value = '';
                        this.getProDetails();
                        window.Toast.success('New Tag Added')
                    }
                })
                .catch((error)=>{
                    window.Toast.error('Some error to add Tag')
                })
        },
    },
}

</script>

<style scoped>
.optvals {
    max-width: 100%;
    min-width: 190px;
}
</style>
