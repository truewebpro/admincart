<template>
    <v-container class="propage pa-2">
        <v-row dense class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn link to="/products" icon variant="tonal" density="compact">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    Edit {{productname}} <v-chip v-if="pro.archived" color="red" variant="outlined" density="compact" >Archived</v-chip>
                </h2>
<!--                <v-btn @click="generateAiContent" color="success" density="compact">Generate Content</v-btn>-->
            </v-col>
            <v-col cols="12" md="6" class="text-end d-flex ga-2 flex-wrap justify-center">
                <v-btn :href="domain+'products/'+pro.handle" target="_blank" variant="tonal" class="text-none"
                       density="compact" title="Preview" color="grey-darken-1">Preview</v-btn>
                <v-btn variant="outlined" @click="disCard" class="text-none me-2" density="compact" color="grey-darken-4">Discard</v-btn>
                <v-menu>
                    <template v-slot:activator="{props}">
                        <v-btn v-bind="props" variant="outlined" class="text-none" append-icon="mdi-chevron-down"
                               density="compact">More Actions</v-btn>
                    </template>
                    <v-list nav density="compact">
                        <v-list-item base-color="dark" v-if="pro.archived" @click="restoreProduct">
                            <v-list-item-title><v-icon class="me-2">mdi-archive-cancel-outline</v-icon>Restore Product</v-list-item-title>
                        </v-list-item>
                        <v-list-item base-color="dark" v-else @click="archiveProduct">
                            <v-list-item-title><v-icon class="me-2">mdi-archive-outline</v-icon>Archive Product</v-list-item-title>
                        </v-list-item>
                        <v-list-item base-color="error" @click="perdeleteProduct">
                            <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Product</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>
                <v-btn @click="editProductById" :loading="isLoading" :disabled="pro.archived || isLoading" color="grey-darken-4" density="compact" class="text-none" >Save</v-btn>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col cols="12" md="9">
                <v-card class="border-sm">
                    <v-card-text>
                        <v-text-field v-model="productname" variant="outlined" label="Title" density="compact" persistent-placeholder></v-text-field>
                        <v-textarea v-model="pro.short_description" variant="outlined" label="Short Description"
                                    persistent-placeholder counter="500" persistent-counter
                                    class="mb-2" density="compact"></v-textarea>
                        <quill-editor ref="quillRef" theme="snow" v-model="quillContent"  @text-change="onEditorChange"
                                      :options="quillOptions"></quill-editor>
                        <v-btn color="primary" variant="outlined" density="compact"
                               prepend-icon="mdi-text-search-variant" class="mt-1"
                               @click="contentDialog = true">Write AI Description</v-btn>
                        <v-dialog v-model="contentDialog" max-width="600">
                            <v-card>
                               <v-form v-model="contentValid" @submit.prevent="generateAiContent">
                                   <v-card-title>Generate Content</v-card-title>
                                   <v-divider class="mt-2"/>
                                   <v-card-text>
                                       <v-text-field v-model="productTitle" label="Title" :placeholder="productname"
                                                     variant="underlined" persistent-placeholder></v-text-field>
                                       <v-text-field v-model="keywords" multiple chips closable-chips placeholder="Vape product, Brand Hayati"
                                                     persistent-placeholder
                                                     label="Keywords Comma separated" variant="underlined"></v-text-field>
                                   </v-card-text>
                                   <v-card-actions>
                                       <v-spacer/>
                                       <v-btn color="success" variant="flat" density="comfortable" type="submit" :disabled="!contentValid">Submit</v-btn>
                                       <v-btn color="red" density="comfortable" @click="contentDialog = false">Cancel</v-btn>
                                   </v-card-actions>
                               </v-form>
                            </v-card>
                        </v-dialog>
                        <div class="mt-3">
                            <v-card class="d-flex align-center justify-center" height="90" outlined @click="triggerFileInput">
                                <v-icon v-if="!featuredImage" size="40" color="grey darken-1">mdi-image-area</v-icon>
                                <v-img v-else :src="getFeaturedImageSrc" contain height="100%" />
                                <v-file-input ref="fileInput" v-model="productImage" accept="image/*" hide-input @change="handleFileUpload" style="display: none" />
                            </v-card>
                        </div>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm" v-if="!Object.keys(variants).length">
                    <v-card-title>Pricing</v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field v-model="price" variant="outlined" label="Price" density="compact"
                                                prefix="£" control-variant="stacked"
                                                persistent-placeholder placeholder="0.00"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field v-model="compareprice" variant="outlined" label="Compare Price"
                                                density="compact" control-variant="stacked"
                                                prefix="£" persistent-placeholder placeholder="0.00"
                                                append-inner-icon="mdi-progress-question"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field v-model="costprice" variant="outlined" label="Cost Price"
                                                density="compact" control-variant="stacked"
                                              prefix="£" persistent-placeholder placeholder="0.00"></v-text-field>
                            </v-col>
                            <v-col cols="12" class="pt-0">
                                <v-checkbox-btn v-model="taxable" label="Charge tax on this product"
                                                :value="1" hide-details></v-checkbox-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm" v-if="!Object.keys(variants).length">
                    <v-card-title>Inventory</v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field v-model="sku" variant="outlined" label="SKU" density="compact"
                                              persistent-placeholder placeholder="SKU001"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field  v-model="stockQuantity"
                                                variant="outlined" label="Stock" density="compact"
                                              persistent-placeholder control-variant="stacked" placeholder="0"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field v-model="barcode" variant="outlined" label="Barcode" density="compact"
                                              prefix="UPC" persistent-placeholder placeholder="876543219011"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4" class="pb-0">
                                <v-text-field v-model="weight" variant="outlined" label="Weight" density="compact"
                                              persistent-placeholder placeholder="0.00">
                                    <template v-slot:append>
                                        <v-select variant="outlined" v-model="weightUnit" density="compact" :items="weight_units" value="g" hide-details></v-select>
                                    </template>
                                </v-text-field>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Variants</v-card-title>
                    <v-card-text v-if="Object.keys(variants).length >= 0">
                        <v-container class="px-0" v-for="(variantList, key) in variants" :key="key">
                            <v-row class="align-center">
                                <v-col cols="10" class="d-flex align-center flex-wrap">
                                    <span class="font-weight-bold text-h6 mr-2">{{ key }}:</span>
                                    <v-chip v-for="value in variants[key]" variant="outlined" class="me-1 mb-1 text-capitalize"
                                            :key="key + '::' + value" density="compact" color="primary">
                                        {{ value }}
                                    </v-chip>
                                </v-col>
                                <v-col cols="2" class="ps-0 d-flex justify-end">
                                    <v-btn icon="mdi-pencil" color="primary" variant="outlined" @click="editVariant(key)"
                                           density="comfortable" class="me-2"></v-btn>
<!--                                    <v-btn icon="mdi-delete" color="red" variant="outlined" @click="removeOption(key)"-->
<!--                                           density="comfortable" v-if="variants[key].length < 2"></v-btn>-->
                                    <v-btn icon="mdi-delete" color="red" variant="outlined" density="comfortable"
                                           v-if="variants[key].length < 2 && Object.keys(variants).length < 3 && Object.keys(variants).length > 1 "
                                           @click="removeLastOption(key)">
                                    </v-btn>
                                    <v-btn icon="mdi-delete" color="red" density="comfortable" v-if="variants[key].length < 2 && Object.keys(variants).length < 2 && Object.keys(variants).length > 0 "
                                           @click="removeLastOption(key)">
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
                                              hide-details :readonly="isEditingVariant"></v-select>
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
                </v-card>
                <v-card v-if="vitems.length > 0 && vitems.some(item => item.optname && item.optname.length > 0)"
                        class="mt-4 border-sm">
                    <v-card-title>Variants Details</v-card-title>
                    <v-toolbar v-if="selectedVariants.length > 0" height="44" color="white">
                        <v-btn variant="text" density="compact" class="font-weight-bold text-none">{{selectedVariants.length}} Selected</v-btn>
                        <v-btn @click="selectedVariants = []" variant="outlined" density="compact" class="text-none">Unselect All</v-btn>
                        <v-spacer/>
                        <v-menu>
                            <template v-slot:activator="{props}">
                                <v-btn v-bind="props" variant="outlined" class="text-none me-2" append-icon="mdi-chevron-down"
                                       density="compact">Edit Product Bulk</v-btn>
                            </template>
                            <v-list nav density="compact">
                                <v-list-item base-color="dark" @click="bulkPriceDialog = true">
                                    <v-list-item-title >Edit Price</v-list-item-title>
                                </v-list-item>
                                <v-list-item base-color="dark" @click="bulkQtyDialog = true">
                                    <v-list-item-title>Edit Qty</v-list-item-title>
                                </v-list-item>
<!--                                <v-list-item base-color="error" @click="bulkCompareDialog = true">-->
<!--                                    <v-list-item-title>Edit Compare Price</v-list-item-title>-->
<!--                                </v-list-item>-->
<!--                                <v-list-item base-color="error" @click="bulkCostDialog = true">-->
<!--                                    <v-list-item-title>Edit Cost Price</v-list-item-title>-->
<!--                                </v-list-item>-->
                            </v-list>
                        </v-menu>
                        <v-dialog v-model="bulkPriceDialog" max-width="350">
                            <v-card>
                                <v-card-text>
                                    <v-number-input v-model="bulkprice" density="compact" prefix="£" variant="outlined"
                                                    :min="0" :precision="2" label="Product Price"
                                                    persistent-placeholder></v-number-input>
                                    <v-number-input v-model="bulkcompare" density="compact" prefix="£" variant="outlined"
                                                    :min="0" :precision="2" label="Compare Price"
                                                    persistent-placeholder></v-number-input>
                                    <v-number-input v-model="bulkcost" density="compact" prefix="£" variant="outlined"
                                                    :min="0" :precision="2" label="Cost Price"
                                                    persistent-placeholder></v-number-input>
                                </v-card-text>
                                <v-card-actions>
                                    <v-btn @click="updateBulkPrice" color="success" variant="elevated" density="compact">Update</v-btn>
                                    <v-btn @click="bulkPriceDialog = false" color="red" variant="elevated" density="compact">Cancel</v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-dialog>
                        <v-dialog v-model="bulkCompareDialog" max-width="350">
                            <v-card>
                                <v-card-text>
                                    <v-number-input v-model="bulkcompare" density="compact" prefix="£" variant="outlined"
                                                    :min="0" :precision="2" label="Product Price"
                                                    persistent-placeholder></v-number-input>
                                </v-card-text>
                                <v-card-actions>
                                    <v-btn color="success" variant="elevated" density="compact">Update</v-btn>
                                    <v-btn @click="bulkCompareDialog = false" color="red" variant="elevated" density="compact">Cancel</v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-dialog>
                        <v-dialog v-model="bulkCostDialog" max-width="350">
                            <v-card>
                                <v-card-text>
                                    <v-number-input v-model="bulkcost" density="compact" prefix="£" variant="outlined"
                                                    :min="0" :precision="2" label="Product Price"
                                                    persistent-placeholder></v-number-input>
                                </v-card-text>
                                <v-card-actions>
                                    <v-btn color="success" variant="elevated" density="compact">Update</v-btn>
                                    <v-btn @click="bulkCostDialog = false" color="red" variant="elevated" density="compact">Cancel</v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-dialog>
                        <v-dialog v-model="bulkQtyDialog" max-width="350">
                            <v-card>
                                <v-card-text>
                                    <v-number-input v-model="bulkqty" density="compact" variant="outlined"
                                                    :min="0" label="Variants Qty"
                                                    persistent-placeholder></v-number-input>
                                </v-card-text>
                                <v-card-actions>
                                    <v-btn @click="updateBulkQty" color="success" variant="elevated" density="compact">Update</v-btn>
                                    <v-btn @click="bulkQtyDialog = false" color="red" variant="elevated" density="compact">Cancel</v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-dialog>
                    </v-toolbar>
                    <v-data-table :items="vitems" :headers="variantHeaders" :key="vtableKey" hover show-select
                                  :hide-default-footer="vitems.length < 49" items-per-page="50"
                                  v-model="selectedVariants" return-object mobileBreakpoint="lg">
                        <template v-slot:item.variantImage="{ item, index }">
                            <v-card class="d-flex align-center justify-center ma-1" width="50" height="50" max-width="50"
                                    @click="triggerVariantFileInput(index)" variant="outlined">
                                <v-icon v-if="!item.variantImage" size="50" color="grey darken-1" > mdi-image-area </v-icon>
                                <v-img v-else :src="getVariantImageSrc(item)" contain height="100%" max-width="50px" ></v-img>
                                <input type="file" :ref="`variantImage${index}`" accept="image/*" @change="handleVariantFileUpload($event, index)" style="display: none" />
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
                            <v-text-field v-model="item.price" width="90px" density="compact" variant="outlined" prefix="£" hide-details />
                        </template>
                        <template v-slot:item.stock="{ item }">
                            <v-text-field v-model="item.stock" width="90px" density="compact" variant="outlined" type="number" hide-details />
                        </template>
                        <template v-slot:item.sku="{ item }">
                            <v-text-field v-model="item.sku" width="150px" density="compact" variant="outlined" hide-details />
                        </template>
                    </v-data-table>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Search engine listing</v-card-title>
                    <v-card-text>
                        <div class="font-weight-medium text-h6">{{this.$store.state.shop.shop_name}}</div>
                        <div class="text-body-2 text-grey-darken-4 mb-2">{{ domain }}>{{pro.handle}}</div>
                        <div v-if="pro.meta_title" class="font-weight-medium text-h5 text-blue-darken-2">{{pro.meta_title}}</div>
                        <div v-else class="font-weight-medium text-h5 text-blue-darken-2">{{productname}}</div>
                        <div v-if="pro.meta_desc" class="text-body-1">
                            {{ truncateText(stripHtml(pro.meta_desc), 100) }}
                        </div>
                        <div v-else class="text-body-1">
                            {{ truncateText(stripHtml(quillContent), 100) }}
                        </div>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-text>
                        <div class="mb-4">
                            <v-text-field v-model="pro.meta_title" variant="outlined" density="compact" label="Page Title"
                                          counter="70" :placeholder="productname" persistent-counter
                                          persistent-placeholder></v-text-field>
                            <v-btn color="primary" variant="outlined" density="compact"
                                   prepend-icon="mdi-text-search-variant" class="mt-1"
                                   @click="mtitleDialog = true">Write AI Meta Title</v-btn>
                            <v-dialog v-model="mtitleDialog" max-width="600">
                                <v-card>
                                    <v-form v-model="contentValid" @submit.prevent="generateAiMetaTitle">
                                        <v-card-title>Generate Meta Title</v-card-title>
                                        <v-divider class="mt-2"/>
                                        <v-card-text>
                                            <v-text-field v-model="productTitle" label="Title" :placeholder="productname"
                                                          variant="underlined" persistent-placeholder></v-text-field>
                                            <v-text-field v-model="keywords" multiple chips closable-chips placeholder="Vape product, Brand Hayati"
                                                          persistent-placeholder
                                                          label="Keywords Comma separated" variant="underlined"></v-text-field>
                                        </v-card-text>
                                        <v-card-actions>
                                            <v-spacer/>
                                            <v-btn color="success" variant="flat" density="comfortable" type="submit" :disabled="!contentValid">Submit</v-btn>
                                            <v-btn color="red" density="comfortable" @click="mtitleDialog = false">Cancel</v-btn>
                                        </v-card-actions>
                                    </v-form>
                                </v-card>
                            </v-dialog>
                        </div>
                        <div class="mb-4">
                            <v-textarea v-model="pro.meta_desc" variant="outlined" density="compact" label="Meta Description"
                                        counter="160" :placeholder="(stripHtml(quillContent))"
                                        persistent-counter persistent-placeholder></v-textarea>
                            <v-btn color="primary" variant="outlined" density="compact"
                                   prepend-icon="mdi-text-search-variant" class="mt-1"
                                   @click="mdescDialog = true">Write AI Meta Description</v-btn>
                            <v-dialog v-model="mdescDialog" max-width="600">
                                <v-card>
                                    <v-form v-model="contentValid" @submit.prevent="generateAiMetaDesc">
                                        <v-card-title>Generate Content</v-card-title>
                                        <v-divider class="mt-2"/>
                                        <v-card-text>
                                            <v-text-field v-model="productTitle" label="Title" :placeholder="productname"
                                                          variant="underlined" persistent-placeholder></v-text-field>
                                            <v-text-field v-model="keywords" multiple chips closable-chips placeholder="Vape product, Brand Hayati"
                                                          persistent-placeholder
                                                          label="Keywords Comma separated" variant="underlined"></v-text-field>
                                        </v-card-text>
                                        <v-card-actions>
                                            <v-spacer/>
                                            <v-btn color="success" variant="flat" density="comfortable" type="submit"
                                                   :disabled="!contentValid" :loading="mdescLoading">Submit</v-btn>
                                            <v-btn color="red" density="comfortable" @click="mdescDialog = false">Cancel</v-btn>
                                        </v-card-actions>
                                    </v-form>
                                </v-card>
                            </v-dialog>
                        </div>
                        <div>
                            <v-text-field v-model="pro.handle" variant="outlined" density="compact" persistent-placeholder
                                          persistent-hint :prefix="domain+'products/'"></v-text-field>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm">
                    <v-card-title>Reviews</v-card-title>
                    <v-card-text>
                        <v-btn color="yellow" @click="reviewDialog = true" density="comfortable">Add Review</v-btn>
                    </v-card-text>
                    <v-card-title>Status</v-card-title>
                    <v-card-text>
                        <v-select v-model="pro.pstatus" variant="outlined" density="compact" label="Status"
                                  :items="prostatus" persistent-placeholder></v-select>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Publishing</v-card-title>
                    <v-card-subtitle>Sales Channels</v-card-subtitle>
                    <v-card-text>
                        <v-checkbox-btn v-model="pro.selectedSalesChannel" label="Online" value="online" hide-details></v-checkbox-btn>
                        <v-checkbox-btn v-model="pro.selectedSalesChannel" label="App" value="app" hide-details></v-checkbox-btn>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Product Organization</v-card-title>
                    <v-card-text>
                        <v-autocomplete ref="ptypeInputRef" v-model="pro.ptype" :items="mptypes" variant="outlined" density="compact"
                                    label="Type" item-title="product_type_name" item-value="product_type_id" :no-data-text="typedPtype || 'No types found'"
                                    persistent-placeholder :return-object="false" @keydown="onPtypeInput">
                            <template #no-data>
                                <v-btn variant="text" class="text-none" size="small" @click="addNewProductType" :disabled="!typedPtype || !typedPtype.trim()" >
                                    Add "{{ typedPtype }}"
                                </v-btn>
                            </template>
                        </v-autocomplete>
                        <v-autocomplete ref="brandInputRef" v-model="pro.brand" :items="mbrands" variant="outlined" density="compact"
                                    label="Brand" item-title="brand_name" item-value="brand_id" :no-data-text="typedBrand || 'No Brand found'"
                                    persistent-placeholder :return-object="false" @keydown="onBrandInput">
                            <template #no-data>
                                <v-btn variant="text" class="text-none" size="small" @click="addNewBrand" :disabled="!typedBrand || !typedBrand.trim()" >
                                    Add "{{ typedBrand }}"
                                </v-btn>
                            </template>
                        </v-autocomplete>
                        <v-autocomplete ref="tagInputRef" v-model="pro.tags" :items="mtags" variant="outlined" density="compact" label="Tags"
                                    item-title="tag_name" :return-object="false" :no-data-text="typedTag || 'No Tag found'"
                                    persistent-placeholder multiple chips closable-chips @keydown="onTagInput">
                            <template #no-data>
                                <v-btn variant="text" class="text-none" size="small" @click="addNewTag" :disabled="!typedTag || !typedTag.trim()" >
                                    Add "{{ typedTag }}"
                                </v-btn>
                            </template>
                        </v-autocomplete>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Vape Highlights</v-card-title>
                    <v-data-table :items="highs" :headers="highHeaders" density="compact" id="hid" items-per-page="20" hide-default-footer>
                        <template v-slot:item.fimage="{item}">
                            <v-img :src="cdn+item.fimage" max-width="48" rounded contain class="my-2"/>
                        </template>
                        <template v-slot:item.ftitle="{item}">
                            <div class="font-weight-medium">{{item.ftitle}}</div>
                            <div>{{item.fvalue}}</div>
                        </template>
                        <template v-slot:item.actions="{item}">
                            <v-btn @click="editHigh(item)" density="compact" color="primary" icon="mdi-pencil" variant="text"/>
                            <v-btn @click="delHigh(item)" density="compact" color="red" icon="mdi-delete" variant="text"/>
                        </template>
                    </v-data-table>
                    <v-dialog v-model="editHighDialog" max-width="400">
                        <v-card>
                            <v-card-title>{{editedItem.ftitle}}</v-card-title>
                            <v-card-text>
                                <v-img :src="cdn+editedItem.fimage" max-width="75"/>
                                <v-form v-model="huValid">
                                    <v-text-field v-model="editedItem.fvalue" density="compact" variant="outlined"
                                                  class="my-3" :rules="fvalRule"></v-text-field>
                                    <div class="d-flex ga-2 mt-3">
                                        <v-spacer/>
                                        <v-btn :disabled="!huValid" @click="updateHighlight" color="success" density="comfortable">update</v-btn>
                                        <v-btn @click="editHighDialog = false" color="red" density="comfortable">cancel</v-btn>
                                    </div>
                                </v-form>
                            </v-card-text>
                        </v-card>
                    </v-dialog>
                    <v-dialog v-model="deleteHighDialog" max-width="400">
                        <v-card>
                            <v-card-title>{{editedItem.ftitle}}</v-card-title>
                            <v-card-text class="text-center">
                                <v-img :src="cdn+editedItem.fimage" max-width="75"/>
                                <h4>{{ editedItem.fvalue }}</h4>
                                <h2>Are you sure to delete Highlight</h2>
                                <div class="d-flex ga-2 mt-3">
                                    <v-spacer/>
                                    <v-btn @click="deleteHighlight" color="success" density="comfortable">Yes</v-btn>
                                    <v-btn @click="deleteHighDialog = false" color="red" density="comfortable">No</v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-dialog>
                    <v-card-text>
                        <v-btn @click="addHighDialog = true" color="primary" density="comfortable">Add Highlight</v-btn>
                        <v-dialog v-model="addHighDialog" max-width="400">
                            <v-card>
                                <v-card-title class="d-flex justify-space-between">
                                    Select Feature
                                    <v-icon @click="addHighDialog = false">mdi-close</v-icon>
                                </v-card-title>
                                <v-card-text>
                                    <v-autocomplete v-model="selectedFeature" :items="features" item-title="ftitle"
                                                    density="comfortable" variant="underlined" return-object
                                                    label="Select Highlight" hide-selected>
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item density="compact"
                                                v-bind="props"
                                                :prepend-avatar="cdn+item.raw.fimage"
                                                :title="item.raw.ftitle"
                                            ></v-list-item>
                                        </template>
                                    </v-autocomplete>
                                    <div v-if="selectedFeature">
                                        <v-img :src="cdn+selectedFeature.fimage" max-width="75"/>
                                        <v-form v-model="haValid">
                                            <v-text-field v-model="fvalue" variant="outlined" density="compact"
                                                          :label="selectedFeature.ftitle" persistent-placeholder
                                                          :rules="fvalRule" hint="Enter Relevant value of the Highlight"
                                                          persistent-hint
                                                          class="mt-3"></v-text-field>
                                            <div class="d-flex ga-2 mt-3">
                                                <v-spacer/>
                                                <v-btn :disabled="!haValid" @click="addHighlight" color="success" density="comfortable">Add Highlight</v-btn>
                                                <v-btn @click="addHighDialog = false" color="red" density="comfortable">cancel</v-btn>
                                            </div>
                                        </v-form>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-dialog>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Rating overview</v-card-title>
                    <v-card-text class="px-1">
                        <v-card v-if="reviews.rcount" class="d-flex flex-column mx-auto px-0" elevation="0">
                            <div class="d-flex align-center flex-column my-auto">
                                <div class="text-h2 mt-2">
                                    {{(reviews.sum / reviews.rcount).toFixed(1)}}
                                    <span class="text-h6 ml-n3">/5</span>
                                </div>
                                <v-rating readonly :model-value="(reviews.sum / reviews.rcount)" color="yellow-darken-3" half-increments/>
                                <div class="px-3">{{reviews.rcount}} ratings</div>
                            </div>
                            <v-list bg-color="transparent" class="d-flex flex-column-reverse" density="compact">
                                <v-list-item v-for="(rating,i) in reviews.stars" :key="i">
                                    <v-progress-linear :model-value="((rating))" class="mx-n5"
                                                       color="yellow-darken-3" height="5" rounded
                                                       :max="reviews.rcount"></v-progress-linear>
                                    <template v-slot:prepend>
                                        <span>{{ i+1 }}</span>
                                        <v-icon class="mx-1" icon="mdi-star" color="orange"></v-icon>
                                    </template>
                                    <template v-slot:append>
                                        <div class="rating-values">
                                            <span class="d-flex justify-end"> {{ rating }} </span>
                                        </div>
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-card>
                    </v-card-text>
                    <v-card-text>
                        <v-dialog max-width="500" v-model="reviewDialog">
                            <v-form v-model="reviewValid" @submit.prevent="addReview">
                                <v-card>
                                    <v-card-text>
                                        <v-rating v-model="review.rating" color="orange" hover></v-rating>
                                        <v-combobox v-model="review.review_title" :items="rtitles" :rules="titleRule"
                                                    label="Review Title" variant="outlined"
                                                    density="compact" clearable
                                                    class="mb-3" persistent-placeholder></v-combobox>
                                        <v-textarea v-model="review.review_text" label="Review Text" variant="outlined"
                                                    density="compact" :placeholder="review.review_title + ' '+ productname"
                                                    :rules="rtextRule"
                                                    class="mb-3" persistent-placeholder></v-textarea>
                                        <v-date-input v-model="review.date" variant="outlined" density="compact"></v-date-input>
                                        <div class="d-flex ga-3">
                                            <v-spacer/>
                                            <v-btn :disabled="!reviewValid" type="submit" color="success" density="comfortable">Add</v-btn>
                                            <v-btn @click="reviewDialog  = false" color="red" density="comfortable">Cancel</v-btn>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-form>
                        </v-dialog>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4 border-sm">
                    <v-card-title>Template</v-card-title>
                    <v-card-text>
                        <v-select variant="outlined" density="compact" label="Template"
                                  :items="protemplates" value="Default" persistent-placeholder></v-select>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row class="ptiers" align="stretch">
            <v-col cols="12" md="9">
                <v-card class="border-sm h-100">
                    <v-card-title>Tier Pricing</v-card-title>
                    <v-card-subtitle>Base Price: £{{ price }}</v-card-subtitle>
                    <v-card-text>
                        <div v-if="tiers.length === 0" class="text-grey">
                            No tier pricing applied. Product uses base price.
                        </div>
                        <v-form v-model="tierForm" @submit.prevent="saveTier">
                            <v-row dense v-for="(tier,index) in tiers" :key="index">
                                <v-col cols="6" md="3">
                                    <v-number-input v-model="tier.min_qty" label="Min Qty" persistentPlaceholder
                                                    variant="outlined" controlVariant="stacked" density="compact"
                                                    :rules="getTierQtyRules(index)"/>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <v-number-input prefix="£" v-model="tier.price" label="Price" :precision="2"
                                                    persistentPlaceholder variant="outlined" controlVariant="stacked"
                                                    density="compact" :rules="getTierPriceRules(index)"
                                                    hint="Tier price must me less than Base Price" persistentHint/>
                                </v-col>
                                <v-col cols="8" md="4">
                                    <v-select :items="['fixed']" v-model="tier.pricing_type" label="Pricing Type" persistentPlaceholder variant="outlined" density="compact"/>
                                </v-col>
                                <v-col cols="4" md="2">
                                    <v-btn
                                        icon="mdi-delete"
                                        color="error"
                                        variant="text"
                                        @click="removeTier(index)"
                                    />
                                </v-col>
                            </v-row>
                            <div class="d-flex ga-2">
                                <v-btn @click="addTier" :disabled="tiers.some(t => !t.price || t.min_qty < 2)"
                                       color="primary" density="compact" :class="tiers.length > 2 ? 'd-none' : ''">Add Tier</v-btn>
                                <v-btn type="submit" :disabled="!tierForm" color="success" density="compact">Save Tier</v-btn>
                            </div>
                        </v-form>
                    </v-card-text>
                    <v-card-text v-if="tiers.length > 0" class="mt-4">
                        <v-row dense align="stretch">
                            <v-col cols="6" md="3">
                                <v-card class="border-sm h-100">
                                    <v-card-text>
                                        <h3 class="mb-1">1 <span v-if="unit_pack_qty > 1">Pack</span><span v-else>{{unit_name}}</span></h3>
                                        <h3 v-if="unit_pack_qty > 1">{{ unit_pack_qty }} {{ unit_name }}s</h3>
                                        <div v-if="unit_pack_qty > 1">£<span v-if="price">{{ (price/unit_pack_qty).toFixed(2) }}</span> per {{ unit_name }}</div>
                                        <div v-else>£{{ (price) }} per {{ unit_name }}</div>
                                        <div class="text-red font-weight-bold my-1">No Savings</div>
                                        <v-chip color="primary" variant="tonal" density="compact" class="font-weight-bold mt-1">
                                            Earn {{Math.ceil(price*1)}} pts
                                        </v-chip>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col v-if="tiers.length > 0" v-for="(tier,tdx) in tiers" cols="6" md="3">
                                <v-card class="border-sm">
                                    <v-card-text v-if="tier?.price">
                                        <h3 class="mb-1">{{tier.min_qty}} <span v-if="unit_pack_qty > 1">Pack</span><span v-else>{{unit_name}}</span>s</h3>
                                        <h3 v-if="unit_pack_qty > 1">{{tier.min_qty*unit_pack_qty}} {{ unit_name }}s</h3>
                                        <div v-if="unit_pack_qty > 1">£{{ (tier?.price/unit_pack_qty).toFixed(2) }} per {{ unit_name }}</div>
                                        <div v-else>£{{ (tier?.price)?.toFixed(2) }} per {{ unit_name }}</div>
                                        <div class="text-red font-weight-bold my-1">
                                            Save £{{(tier.min_qty*price-tier.min_qty*tier.price).toFixed(2)}}
                                            <v-chip color="red" variant="tonal" density="compact" class="font-weight-medium"> {{Math.round((tier.min_qty*price-tier.min_qty*tier.price)*100/(price*tier.min_qty))}}%</v-chip>
                                        </div>
                                        <v-chip color="primary" variant="tonal" density="compact" class="font-weight-bold">
                                            Earn {{Math.ceil(tier.price*tier.min_qty)}} pts
                                        </v-chip>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm h-100">
                    <v-card-title>Product Unit Count</v-card-title>
                    <v-card-subtitle>Base Price: £{{ price }}</v-card-subtitle>
                    <v-card-text>
                        <v-form v-model="unitform" @submit.prevent="updateUnitPack">
                            <v-text-field v-model="unit_name" variant="outlined" density="compact" label="Unit Name"
                                          :rules="unitNameRule"></v-text-field>
                            <v-number-input v-model="unit_pack_qty" :min="0" variant="outlined" controlVariant="stacked"
                                            label="Pack Qty" density="compact" :rules="unitQtyRule"></v-number-input>
                            <v-btn type="submit" :disabled="!unitform" color="primary" density="compact">Update Unit</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-title>
                        Product Specifics
                    </v-card-title>
                    <v-card-text>
                        <v-btn density="compact" color="success" append-icon="mdi-plus" @click="addSpecDialog = true">Add Specifics</v-btn>
                    </v-card-text>
                    <v-dialog v-model="addSpecDialog" max-width="400">
                        <v-card>
                            <v-card-text>
                                <v-text-field v-model="defaultSpec.stitle" label="Specific Title"
                                              density="comfortable" variant="underlined" class="mb-1"
                                              counter="20" persistent-counter/>
                                <v-textarea v-model="defaultSpec.svalue" label="Specific Value" density="comfortable"
                                            variant="underlined" counter="200" persistent-counter/>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer/>
                                <v-btn @click="addSpecific" density="comfortable" color="success" variant="elevated">Add</v-btn>
                                <v-btn density="comfortable" color="red" @click="addSpecDialog = false">Cancel</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                    <v-dialog v-model="editSpecDialog" max-width="400">
                        <v-card>
                            <v-card-text>
                                <v-text-field v-model="editedSpec.stitle" label="Specific Title"
                                              density="comfortable" variant="underlined" class="mb-1"/>
                                <v-textarea v-model="editedSpec.svalue" label="Specific Value" density="comfortable" variant="underlined"/>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer/>
                                <v-btn @click="editSpecific" density="comfortable" color="success" variant="elevated">Update</v-btn>
                                <v-btn density="comfortable" color="red" @click="editSpecDialog = false">Cancel</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                    <v-dialog v-model="deleteSpecDialog" max-width="400">
                        <v-card>
                            <v-card-text class="text-center">
                                Are you sure to delete this Product Specific
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer/>
                                <v-btn @click="deleteSpecific" density="comfortable" color="success" variant="elevated">Yes</v-btn>
                                <v-btn density="comfortable" color="red" @click="deleteSpecDialog = false">No</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-card>
            </v-col>
        </v-row>
        <v-row v-if="specifics.length">
            <v-col v-for="(spec,sdx) in specifics" :key="sdx" cols="6" md="3">
                <v-card>
                    <v-card-text>
                        <div class="text-h5">{{ spec.stitle }}</div>
                        <div class="text-body-1">{{ spec.svalue }}</div>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn density="compact" color="info" @click="editSpec(spec)">Edit</v-btn>
                        <v-btn density="compact" color="red" @click="deleteSpec(spec)">Remove</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
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
                :alink="alinks"
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
import {ref, toRaw} from 'vue';
import { VDateInput } from "vuetify/labs/VDateInput";
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

export default {
    name:"ProductEdit",
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
    props:{
        product_id:[Number, String]
    },
    data(){
        return{
            selectedVariants:[],
            bulkPriceDialog:false,
            bulkprice:0,
            bulkQtyDialog:false,
            bulkqty:1,
            bulkCompareDialog:false,
            bulkcompare:0,
            bulkCostDialog:false,
            bulkcost:0,
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
            selectedOption: "",
            optionValueInputs:[''],
            variants: {},
            isEditingVariant: false,
            previousOptionKey: null,
            showVariantForm: false,
            lastEdit: null,
            isLoading:false,
            cdn:this.$store.state.cdn,
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+'/',
            weight_units:['kg','g'],
            prostatus:['Active','Draft'],
            protemplates:['Default','Template 1','Template 2'],
            method: "edit",
            backLoading: false,
            fvalid: false,
            productname: "",
            productdesc: "",
            productImage: null,
            featuredImage: null,
            price: "",
            compareprice: "",
            costprice: "",
            taxable: 0,
            barcode: "",
            sku: "",
            stockQuantity: "",
            weight: "",
            weightUnit: "kg",
            newOptionValue: "",
            optionValues: "",
            vitems: [],
            vtableKey: 0,
            isSubmitting: false,
            pro: {
                handle:'',
                short_description:'',
                selectedSalesChannel: [],
                pstatus: "Draft",
                ptype: "",
                brand: "",
                tags: [],
                meta_title:"",
                meta_desc:"",
                archived:false,
            },
            typedText: "",
            availableOptions: [],
            duplicateDialog: false,
            duplicateProductId: null,
            deleteLoading: false,
            quillContent:'',
            quillOptions: {
                theme: 'snow',
                contentType:'Delta',
                modules: {
                    toolbar: [
                        [{'header': [1, 2, 3, 4, 5, 6, false]}],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['link'],
                        [{list: 'ordered'}, {list: 'bullet'}],
                        [{'indent': '-1'}, {'indent': '+1'}],
                        [{'align': []}],
                        ['clean'],
                    ],
                },
            },
            istax:1,
            isdefault:1,
            typedPtype:"",
            typedBrand:"",
            typedTag:"",
            highs:[],
            highHeaders:[
                {title:'Image',value:'fimage',width:60},
                {title:'Title',value:'ftitle'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            editedItem:{
                highlight_id: '',
                ftitle: '',
                fimage: '',
                fvalue: '',
            },
            features:[],
            selectedFeature:null,
            addHighDialog:false,
            editHighDialog:false,
            deleteHighDialog:false,
            haValid:false,
            huValid:false,
            fvalue:'',
            fvalRule:[
                (v) => !!v || "Feature Value is required",
                (v) => (v && v.length >= 1) || "Minimum 1 characters required",
                (v) => (v && v.length <= 20) || "Maximum 20 characters allowed",
            ],
            reviews:[],
            reviewValid:false,
            reviewDialog:false,
            review:{
                review_title:'',
                review_text:'',
                rating:5,
                reviewer_id:"",
            },
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 5) || "Minimum 5 characters required",
                (v) => (v && v.length <= 50) || "Maximum 50 characters allowed",
            ],
            rtextRule:[
                (v) => !!v || "Text is required",
                (v) => (v && v.length >= 20) || "Minimum 20 characters required",
                (v) => (v && v.length <= 160) || "Maximum 160 characters allowed",
            ],
            rtitles:['Great service','Highly impressed!','Would buy again!','Great product!',
                'Very happy with my purchase!','Awesome product!','Great value for money!','Highly recommended!'],
            reviewers:[],
            selectedReviewer:null,
            contentValid:false,
            contentDialog:false,
            contentLoading:false,
            mtitleDialog:false,
            mtitleLoading:false,
            mdescDialog:false,
            mdescLoading:false,
            productTitle:'',
            keywords:'',
            contentDesc:['description'],
            contentMtitle:['metaTitle'],
            contentMdesc:['metaDescription'],
            contentFeatures:['features'],
            contentFaqs:['faq'],
            contentTypes:["description","faq","metaTitle","metaDescription","features"],
            aitypes:["description"],
            specifics:[],
            addSpecDialog:false,
            editSpecDialog:false,
            deleteSpecDialog:false,
            defaultSpec:{
                stitle:'',
                svalue:''
            },
            editedSpecIndex:-1,
            editedSpec:{
                specific_id:'',
                stitle:'',
                svalue:'',
                shop_id:''
            },
            deletedSpec:{
                specific_id:'',
            },
            tiers:[],
            unitform:false,
            unit_name:"Unit",
            unit_pack_qty:0,
            unitNameRule: [
                (v) => !!v || 'Unit Name Required'
            ],
            unitQtyRule: [
                // (v) => !!v || 'Unit Pack Qty Required',
                (v) => v >= 0 || 'Min qty must be ≥ 0',
            ],
            tierForm:false,
        }
    },
    async mounted() {
        if (!this.$store.state.products.length) {
            await this.$store.dispatch('fetchProducts')
        }
        this.getProductByID();
        this.$store.dispatch('fetchAlinks');
    },
    computed: {
        alinks(){
            return this.$store.state.alinks;
        },
        mbrands(){
            return this.$store.state.brands;
        },
        mtags(){
            return this.$store.state.tags;
        },
        mptypes(){
            return this.$store.state.productTypes;
        },
        optionInputsTrimmed() {
            return this.optionValueInputs.map(v => (v ?? '').trim());
        },
        isAllUniqueCI() {
            const lowers = this.optionInputsTrimmed.map(v => v.toLowerCase());
            return lowers.length === new Set(lowers).size;
        },
        canAddAnother() {
            // every current input must be non-empty AND all unique (case-insensitive)
            return this.optionInputsTrimmed.every(v => v.length > 0) && this.isAllUniqueCI;
        },
        canSaveVariant() {
            // must have an option selected and current inputs valid
            return !!this.selectedOption && this.optionValueInputs.length > 0 && this.canAddAnother;
        },
        variantHeaders() {
            return [
                { title: "Image", value: "variantImage", editable: true,maxWidth:"60px" },
                { title: this.vitems[0]?.optname?.join(' / '), value: "optname" },
                { title: "Price", value: "price", editable: true },
                { title: "Available", value: "stock", editable: true },
                { title: "SKU", value: "sku" },
            ];
        },
        filteredOptions() {
            return this.availableOptions.filter(option => {
                return (
                    option.option_name === this.selectedOption ||
                    !this.variants.hasOwnProperty(option.option_name)
                );
            });
        },
        getFeaturedImageSrc() {
            if (typeof this.featuredImage === "string" && this.featuredImage.startsWith("data:image")) {
                return this.featuredImage;
            }
            return this.cdn + this.featuredImage;
        },
    },
    methods:{
        getTierQtyRules(index) {
            return [
                v => !!v || 'Qty Required',
                v => v >= 2 || 'Min qty must be ≥ 2',
                v => {
                    if (index === 0) return true
                    const prev = this.tiers[index - 1]
                    return v > prev.min_qty || `Must be > ${prev.min_qty}`
                }
            ]
        },
        getTierPriceRules(index) {
            return [
                (v) => !!v || 'Price Required',
                (v) => v > 0 || 'Price must be > 0',
                (v) => v < this.price || `Must be less than £${this.price}`,
                v => {
                    if (index === 0) return true
                    const prev = this.tiers[index - 1]
                    return v < prev.price || `Must be < £${prev.price}`
                }
            ]
        },
        addTier(){
            const hasEmpty = this.tiers.some(t => !t.price)
            if (hasEmpty) {
                alert("Fill existing tier first")
                return
            }
            this.tiers.push({ min_qty: 2, price: null, pricing_type: 'fixed' });
        },
        removeTier(index){
            this.tiers.splice(index,1);
        },
        saveTier(){
            const tierdata = {
                product_id:this.product_id,
                tiers: this.tiers.map(t => ({
                    min_qty: Number(t.min_qty),
                    price: Number(t.price),
                    pricing_type: t.pricing_type
                }))
            }
            axios.post('/sadmin/product/save-tier-pricing',tierdata)
                .then((resp)=>{
                    this.$store.commit('UPDATE_PRODUCT',resp.data.product)
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getProductByID();
                        })
                    window.Toast.success(resp.data.message);
                })
                .catch((err) => {
                    window.Toast.error(err.message);
                })
        },
        updateUnitPack(){
            const unitData = {
                product_id:Number(this.product_id),
                unit_name:this.unit_name,
                unit_pack_qty:this.unit_pack_qty,
            }
            axios.post('/sadmin/product/update-unit-pack',unitData)
                .then((resp)=>{
                    this.$store.commit('UPDATE_PRODUCT',resp.data.product)
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getProductByID();
                        })
                    window.Toast.success(resp.data.message);
                })
                .catch((err) => {
                    window.Toast.error(err.message);
                })
        },
        _normalizeVal(val) {
            return (val === undefined || val === null) ? '' : String(val).trim();
        },
        _eqVal(a, b) {
            return this._normalizeVal(a).toLowerCase() === this._normalizeVal(b).toLowerCase();
        },
        _serializeOptValue(obj) {
            // canonical sorted serialization so keys order doesn't matter
            const keys = Object.keys(obj).sort();
            return JSON.stringify(keys.map(k => [k, this._normalizeVal(obj[k])]));
        },
        _deriveBaseSKUAndNext(existingSkus = []) {
            // prefer this.sku (product-level) if present and non-empty
            let base = (this.sku && this.sku !== '') ? this.sku : (existingSkus[0] || 'SKU');
            // strip trailing numeric suffix -n
            const match = base.match(/^(.+?)-(\d+)$/);
            if (match) base = match[1];

            // find max suffix used with this base
            let maxSuffix = -1;
            for (const s of existingSkus) {
                if (!s) continue;
                if (s === base) maxSuffix = Math.max(maxSuffix, 0);
                const m = s.match(new RegExp(`^${base}-(\\d+)$`));
                if (m) maxSuffix = Math.max(maxSuffix, parseInt(m[1], 10));
            }
            const next = maxSuffix >= 0 ? maxSuffix + 1 : 1;
            return { base, next };
        },
        startNewOption() {
            this.selectedOption = '';
            this.optionValueInputs = [''];
            this.showVariantForm = true;
            this.isEditingVariant = false;
            this.previousOptionKey = null;
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
        addOptionValue() {
            if (!this.selectedOption || !this.newOptionValue.trim()) return;
            const newValue = this.newOptionValue.trim();

            if (!this.variants[this.selectedOption]) {
                this.variants = { ...this.variants, [this.selectedOption]: [] };
            }
            if (!this.variants[this.selectedOption].includes(newValue)) {
                this.variants = {
                    ...this.variants,
                    [this.selectedOption]: [...this.variants[this.selectedOption], newValue],
                };
            }

            this.newOptionValue = "";
            this.generateVariantCombinations();
            this.showVariantForm = false;
        },
        addOptionField(){
            if (!this.canAddAnother) return;
            this.optionValueInputs.push("");
        },
        removeOptionField(index){
            this.optionValueInputs.splice(index,1)
        },
        cancelVariant() {
            this.selectedOption = "";
            this.optionValueInputs = [""];
            this.showVariantForm = false;
            this.isEditingVariant = false;
            this.previousOptionKey = null;
        },
        editVariant(key) {
            if(this.variants[key]){
                this.previousOptionKey = key;
                this.selectedOption = key;
                this.optionValueInputs = [...this.variants[key]];
                this.showVariantForm = true;
                this.isEditingVariant = true;
            }
        },
        setLastOption(key){
            this.isEditingVariant = false;
            if(this.variants[key]){
                delete this.variants[key];
                this.vitems = this.vitems.map((row,index)=>{
                    if (!row.optvalue) return row;
                    const newOptValue = { ...row.optvalue };
                    delete newOptValue[key];
                    return {
                        ...row,
                        optvalue: newOptValue,
                        optname: Object.keys(newOptValue),
                        variant: Object.entries(newOptValue).map(([k,v]) => `${k}: ${v}`).join(" / ")
                    };
                })

            }
            if (this.isEditingVariant && this.previousOptionKey === key) {
                this.cancelVariant(); // already clears all edit states
            }

        },
        removeLastOption(key){
            this.isEditingVariant = false;

            if(this.variants[key]){
                delete this.variants[key];
                this.vitems = this.vitems.map((row,index)=>{
                    if (!row.optvalue) return row;
                    const newOptValue = { ...row.optvalue };
                    delete newOptValue[key];
                    return {
                        ...row,
                        optvalue: newOptValue,
                        optname: Object.keys(newOptValue),
                        variant: Object.entries(newOptValue).map(([k,v]) => `${k}: ${v}`).join(" / ")
                    };
                })

            }
            if (this.isEditingVariant && this.previousOptionKey === key) {
                this.cancelVariant(); // already clears all edit states
            }

        },
        removeOption(key) {
            if (this.variants[key]) {
                delete this.variants[key];
                // If the deleted option is currently being edited, reset the edit form
                if (this.isEditingVariant && this.previousOptionKey === key) {
                    this.cancelVariant(); // already clears all edit states
                }
            }
        },
        removeValue(key, value) {
            if (!this.variants[key]) return;

            this.variants = {
                ...this.variants,
                [key]: this.variants[key].filter((v) => v !== value),
            };

            // clean up if no values left
            if (this.variants[key].length === 0) {
                this.removeOption(key);
            } else {
                this.generateVariantCombinations();
            }
        },
        getProductByID(){
            axios.get('/sadmin/products/'+this.product_id)
                .then((resp)=>{
                    const prod = resp.data.prod;
                    if(prod.deleted_at != null){
                        this.pro.archived = true;
                    }
                    this.pro.handle = prod.handle;
                    this.quillContent = prod.body_html;
                    this.pro.short_description = prod.short_description;
                    const quill = this.$refs.quillRef.getQuill()
                    if (quill.getLength() <= 1) {
                        quill.clipboard.dangerouslyPasteHTML(this.quillContent);
                        const html = quill.root.innerHTML;
                        this.quillContent = html;
                    }
                    this.productname = prod.title;
                    this.productdesc = prod.body_html;
                    if (prod.featured_image) this.featuredImage = prod.featured_image;
                    if (!prod.variants || prod.variants.length === 0) {
                        this.price = prod.price || "";
                        this.compareprice = prod.compareprice || "";
                        this.costprice = prod.costprice || "";
                        this.taxable = prod.istax || 0;
                        this.barcode = prod.barcode || "";
                        this.sku = prod.sku || "";
                        this.stockQuantity = prod.stock || "";
                        this.weight = prod.weight || "";
                        this.weightUnit = prod.weightunit || "kg";
                    } else {
                        this.price = prod.variants[0].price || "";
                        this.compareprice = prod.variants[0].compareprice || "";
                        this.costprice = prod.variants[0].costprice || "";
                        this.taxable = prod.variants[0].istax || 0;
                        this.barcode = prod.variants[0].barcode || "";
                        this.sku = prod.variants[0].sku || "";
                        this.stockQuantity = prod.variants[0].stock || "";
                        this.weight = prod.variants[0].weight || "";
                        this.weightUnit = prod.variants[0].weightunit || "kg";
                    }
                    this.pro.pstatus = prod.product_status || "Draft";
                    this.pro.selectedSalesChannel = prod.publish_status || [];
                    this.pro.ptype = prod.product_type_id || "";
                    this.pro.brand = prod.brand_id || "";
                    this.pro.tags = prod.tags || [];
                    this.availableOptions = resp.data.poptions || [];
                    if (prod.variants && prod.variants.length > 0) {
                        this.vitems = prod.variants.map(variant => ({
                            variant_id: variant.variant_id,
                            variantImage: variant.variant_image || null,
                            variant: Object.entries(variant.option_values || {})
                                .map(([k, v]) => `${k}: ${v}`)
                                .join(" / "),
                            price: variant.price || "",
                            compareprice: variant.compareprice || "",
                            costprice: variant.costprice || "",
                            stock: variant.stock || "",
                            sku: variant.sku || "",
                            barcode: variant.barcode || "",
                            optname: Object.keys(variant.option_values || {}),
                            optvalue: variant.option_values || {}
                        }));
                        this.variants = {};
                        prod.variants.forEach(v => {
                            if (!v.option_values) return;
                            Object.entries(v.option_values).forEach(([key, val]) => {
                                if (!this.variants[key]) this.variants[key] = [];
                                if (!this.variants[key].includes(val)) this.variants[key].push(val);
                            });
                        });
                    }
                    this.pro.meta_title = prod.meta_title;
                    this.pro.meta_desc = prod.meta_desc;
                    this.unit_name = prod.unit_name;
                    this.unit_pack_qty = prod.unit_pack_qty;
                    this.stypes = resp.data.stypes;
                    this.sections = prod.sections;
                    this.highs = prod.highs;
                    this.allCategories = resp.data.categories || [];
                    this.allBrands = resp.data.brands || [];
                    const highIds = prod.highs.map(h => h.feature_id);
                    this.features = resp.data.features.filter(f => !highIds.includes(f.feature_id));
                    this.reviews = resp.data.reviews;
                    this.reviewers = resp.data.reviewers;
                    this.specifics = prod.specifics;
                    this.tiers = (prod.tiers || [])
                        .map(t => ({
                            min_qty: Number(t.min_qty),
                            price: Number(t.price),
                            pricing_type: t.pricing_type || 'fixed'
                        }))
                        .sort((a, b) => a.min_qty - b.min_qty)
                })
        },
        generateVariantCombinations(returnOnly = false) {
            // produce an array of { optvalue: {Key: Value}, keyStr } for all combos
            const rawVariants = toRaw(this.variants) || {};
            const keys = Object.keys(rawVariants);
            if (!keys.length) {
                if (!returnOnly) this.vitems = [];
                return [];
            }

            // build arrays for recursion
            const arrays = keys.map(k => (Array.isArray(rawVariants[k]) ? rawVariants[k] : []));

            // recursive combine
            const combos = [];
            const build = (index, prefix) => {
                if (index === keys.length) {
                    const opt = {};
                    keys.forEach((k, i) => { opt[k] = prefix[i]; });
                    combos.push({ optvalue: opt, keyStr: this._serializeOptValue(opt) });
                    return;
                }
                const arr = arrays[index] || [''];
                for (const v of arr) build(index + 1, [...prefix, v]);
            };
            build(0, []);
            if (returnOnly) return combos;
            // otherwise we leave mapping to saveVariant()
            return combos;
        },
        // --- main function: matches old rows to new combos and returns final rows (also sets this.vitems) ---
        generateVariantCombinationsAndPreserve(lastEdit = null) {
            // lastEdit: optional { key, renames } provided by saveVariant
            const rawVariants = toRaw(this.variants) || {};
            const newKeys = Object.keys(rawVariants);
            if (!newKeys.length) { this.vitems = []; return []; }

            const combos = this.generateVariantCombinations(true); // array of {optvalue, keyStr}

            // old rows (copy)
            const oldRows = Array.isArray(this.vitems) ? [...this.vitems] : [];

            // gather existing SKUs for this product to derive base/next
            const existingSkus = oldRows.map(r => r.sku).filter(Boolean);
            const { base: skuBase, next: startSuffix } = this._deriveBaseSKUAndNext(existingSkus);
            let suffix = startSuffix;

            const usedOldIdx = new Set();

            // helpers:
            const sameAllKeys = (a, b, keys) => keys.every(k => this._eqVal(a[k], b[k]));
            const objKeys = obj => Object.keys(obj || {});

            // 1) Strict full-match (oldRow has same keys count and same values across those keys)
            const finalRows = combos.map(() => null);

            combos.forEach((combo, ci) => {
                for (let i = 0; i < oldRows.length; i++) {
                    if (usedOldIdx.has(i)) continue;
                    const old = oldRows[i];
                    if (!old.optvalue) continue;
                    const oldKeys = objKeys(old.optvalue);
                    // strict match requires same key set as newKeys
                    if (oldKeys.length !== newKeys.length) continue;
                    // check equality key-by-key (case-insensitive)
                    const ok = newKeys.every(k => this._eqVal(old.optvalue[k], combo.optvalue[k]));
                    if (ok) {
                        usedOldIdx.add(i);
                        finalRows[ci] = {
                            ...combo.optvalue && { optname: Object.keys(combo.optvalue) },
                            ...combo,
                            variant: Object.entries(combo.optvalue).map(([k, v]) => `${k}: ${v}`).join(" / "),
                            price: old.price ?? "",
                            compareprice: old.compareprice ?? "",
                            costprice: old.costprice ?? "",
                            stock: old.stock ?? 0,
                            sku: old.sku ?? null,
                            barcode: old.barcode ?? "",
                            variantImage: old.variantImage ?? null,
                            optname: Object.keys(combo.optvalue),
                            optvalue: combo.optvalue,
                            variant_id: old.variant_id ?? null
                        };
                        break;
                    }
                }
            });

            // 2) Rename-aware match (if lastEdit provided)
            if (lastEdit && lastEdit.key && lastEdit.renames) {
                const editedKey = lastEdit.key;
                const renames = lastEdit.renames;
                combos.forEach((combo, ci) => {
                    if (finalRows[ci]) return;
                    for (let i = 0; i < oldRows.length; i++) {
                        if (usedOldIdx.has(i)) continue;
                        const old = oldRows[i];
                        if (!old.optvalue) continue;
                        // check all keys except editedKey match
                        let ok = true;
                        for (const nk of newKeys) {
                            if (nk === editedKey) continue;
                            if (!this._eqVal(old.optvalue[nk], combo.optvalue[nk])) { ok = false; break; }
                        }
                        if (!ok) continue;
                        // check mapping: old value at editedKey -> renamed value should equal combo value
                        const oldVal = old.optvalue[editedKey];
                        const mapped = renames[oldVal] ?? null;
                        if (mapped && this._eqVal(mapped, combo.optvalue[editedKey])) {
                            usedOldIdx.add(i);
                            finalRows[ci] = {
                                ...combo,
                                variant: Object.entries(combo.optvalue).map(([k, v]) => `${k}: ${v}`).join(" / "),
                                price: old.price ?? "",
                                compareprice: old.compareprice ?? "",
                                costprice: old.costprice ?? "",
                                stock: old.stock ?? 0,
                                sku: old.sku ?? null,
                                barcode: old.barcode ?? "",
                                variantImage: old.variantImage ?? null,
                                optname: Object.keys(combo.optvalue),
                                optvalue: combo.optvalue,
                                variant_id: old.variant_id ?? null
                            };
                            break;
                        }
                    }
                });
            }

            // 3) Partial-match mapping for added keys: if an old row had fewer keys (oldKeys subset of newKeys),
            //    we attempt to preserve that old row mapping to a combo whose NEW keys all equal the "default" value
            //    (we consider the first value of the new option as default).
            combos.forEach((combo, ci) => {
                if (finalRows[ci]) return;
                for (let i = 0; i < oldRows.length; i++) {
                    if (usedOldIdx.has(i)) continue;
                    const old = oldRows[i];
                    if (!old.optvalue) continue;
                    const oldKeys = objKeys(old.optvalue);
                    // ensure oldKeys are subset of newKeys
                    const isSubset = oldKeys.every(k => newKeys.includes(k));
                    if (!isSubset) continue;
                    // match all oldKeys values
                    let ok = true;
                    for (const k of oldKeys) {
                        if (!this._eqVal(old.optvalue[k], combo.optvalue[k])) { ok = false; break; }
                    }
                    if (!ok) continue;

                    // now check NEW keys (newKeys minus oldKeys) if they equal default value (first value)
                    const addedKeys = newKeys.filter(k => !oldKeys.includes(k));
                    let allNewAreDefault = true;
                    for (const nk of addedKeys) {
                        const firstVal = (Array.isArray(rawVariants[nk]) ? rawVariants[nk][0] : undefined) ?? '';
                        if (!this._eqVal(combo.optvalue[nk], firstVal)) { allNewAreDefault = false; break; }
                    }
                    if (!allNewAreDefault) continue;

                    // Accept mapping
                    usedOldIdx.add(i);
                    finalRows[ci] = {
                        ...combo,
                        variant: Object.entries(combo.optvalue).map(([k, v]) => `${k}: ${v}`).join(" / "),
                        price: old.price ?? "",
                        compareprice: old.compareprice ?? "",
                        costprice: old.costprice ?? "",
                        stock: old.stock ?? 0,
                        sku: old.sku ?? null,
                        barcode: old.barcode ?? "",
                        variantImage: old.variantImage ?? null,
                        optname: Object.keys(combo.optvalue),
                        optvalue: combo.optvalue,
                        variant_id: old.variant_id ?? null
                    };
                    break;
                }
            });

            const lastRow = oldRows.length ? oldRows[oldRows.length - 1] : null;

            // 4) Any unmatched combos are brand new => create new rows with new SKUs
            for (let ci = 0; ci < combos.length; ci++) {
                if (finalRows[ci]) continue;
                const combo = combos[ci];
                // create new SKU using product's base and suffix (avoid collisions inside this product)
                const newSku = suffix === 0 ? skuBase : `${skuBase}-${suffix}`;
                suffix++;

                finalRows[ci] = {
                    ...combo,
                    variant: Object.entries(combo.optvalue).map(([k, v]) => `${k}: ${v}`).join(" / "),
                    price: lastRow?.price ?? "",
                    compareprice: lastRow?.compareprice ?? "",
                    costprice:lastRow?.costprice ?? "",
                    stock: 0,
                    sku: newSku,
                    barcode: "",
                    variantImage: null,
                    optname: Object.keys(combo.optvalue),
                    optvalue: combo.optvalue,
                    variant_id: null
                };
            }

            // finalRows is in the order of combos (which uses newKeys ordering)
            this.vitems = finalRows;
            this.vtableKey = (this.vtableKey || 0) + 1;
            return finalRows;
        },
        // --- saveVariant: updates this.variants and generates new rows (uses rename detection) ---
        saveVariant() {
            if (!this.canSaveVariant) return;

            const optionKey = (this.selectedOption || '').trim();
            if (!optionKey) return;

            // dedupe and preserve casing of first occurrence while using case-insensitive uniqueness
            const inputs = (this.optionValueInputs || []).map(v => (v ?? '').trim()).filter(v => v !== '');
            const seen = new Set();
            const newValues = [];
            for (const v of inputs) {
                const lk = v.toLowerCase();
                if (!seen.has(lk)) {
                    seen.add(lk);
                    newValues.push(v);
                }
            }

            // capture before values if editing same key
            let before = [];
            if (this.isEditingVariant && this.previousOptionKey) {
                before = Array.isArray(this.variants[this.previousOptionKey]) ? [...this.variants[this.previousOptionKey]] : [];
            }

            // if key rename (previousKey -> optionKey) and different, remove previous key entry (we will write new below)
            if (this.isEditingVariant && this.previousOptionKey && this.previousOptionKey !== optionKey) {
                // keep before copy then delete
                delete this.variants[this.previousOptionKey];
            }

            // write new values to this.variants
            this.variants = { ...this.variants, [optionKey]: newValues };

            // build rename map if same key edited (mapping removed -> added)
            let renames = {};
            if (this.isEditingVariant && this.previousOptionKey === optionKey) {
                const after = newValues;
                const removed = before.filter(v => !after.map(a => a.toLowerCase()).includes(v.toLowerCase()));
                const added   = after.filter(v => !before.map(b => b.toLowerCase()).includes(v.toLowerCase()));
                if (removed.length === 1 && added.length === 1) {
                    renames[removed[0]] = added[0];
                } else {
                    // try index-based mapping
                    const len = Math.min(before.length, after.length);
                    for (let i = 0; i < len; i++) {
                        if (before[i] && after[i] && before[i] !== after[i]) renames[before[i]] = after[i];
                    }
                }
            }

            // attempt to preserve existing variants by mapping rules (pass rename map)
            this.lastEdit = (Object.keys(renames).length ? { key: optionKey, renames } : null);
            this.generateVariantCombinationsAndPreserve(this.lastEdit);

            // reset form
            this.cancelVariant();
        },
        removeVariantOption(keyToRemove) {
            // 1. Remove the key from `variants`
            const { [keyToRemove]: removed, ...rest } = this.variants;
            this.variants = rest;

            // 2. Update each vitem's optvalue and variant text
            this.vitems = this.vitems.map(item => {
                const newOptValue = { ...item.optvalue };
                delete newOptValue[keyToRemove];

                const newVariantStr = Object.entries(newOptValue)
                    .map(([k, v]) => `${k}: ${v}`)
                    .join(' / ');

                return {
                    ...item,
                    optvalue: newOptValue,
                    variant: newVariantStr,
                    // Leave sku, price, stock, variant_id untouched
                };
            });

            // Optional: update the table key if using force refresh
            this.vtableKey++;
        },
        removeIncompleteRows() {
            const before = this.vitems.length;
            this.vitems = this.vitems.filter(item => !item.incomplete);
            const after = this.vitems.length;
            console.log(`Removed ${before - after} incomplete rows`);
            this.tableKey++;
        },
        maybeRemoveIncompleteRows() {
            const activeOptionCount = Object.keys(this.variants).length;

            // Update rows with incomplete flag
            this.vitems = this.vitems.map(item => {
                const optKeyCount = Object.keys(item.optvalue).length;
                return {
                    ...item,
                    incomplete: optKeyCount < activeOptionCount
                };
            });

            // Only remove incomplete if at least 2 active option groups
            if (activeOptionCount > 1) {
                const hasIncomplete = this.vitems.some(item => item.incomplete);
                if (hasIncomplete) {
                    this.removeIncompleteRows();
                }
            }
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
            const input = this.$refs.fileInput?.$el?.querySelector('input[type="file"]');
            if (input) input.click();
        },
        handleFileUpload() {
            if (this.productImage instanceof File) {
                const reader = new FileReader();
                reader.onload = e => {
                    this.featuredImage = e.target.result;
                };
                reader.readAsDataURL(this.productImage);
            }
        },
        getVariantImageSrc(item) {
            if (item.preview) {
                return item.preview;
            }
            // if (!item.variantImage) return "";
            // if (typeof item.variantImage === "object" && item.variantImage.preview) {
            //     return item.variantImage.preview;
            // }
            if (typeof item.variantImage === "string") {
                return this.cdn + item.variantImage;
            }
            if (item.variantImage instanceof File) {
                return URL.createObjectURL(item.variantImage);
            }
            return "";
        },
        triggerVariantFileInput(index) {
            const fileInput = this.$refs[`variantImage${index}`];
            if (fileInput) {
                fileInput.click();
            } else {
                console.error(`File input for index ${index} is undefined or not found.`);
            }
        },
        handleVariantFileUpload(event, index) {
            const file = event.target.files[0];
            if (!file) return;
            const previewURL = URL.createObjectURL(file);

            this.vitems.splice(index, 1, {
                ...this.vitems[index],
                variantImage: file, // ✅ keep file
                preview: previewURL // for displaying image
            });
            // const reader = new FileReader();
            // reader.onload = (e) => {
            //     this.vitems[index] = {
            //         ...this.vitems[index],
            //         variantImage: {
            //             file: file,
            //             preview: e.target.result
            //         }
            //     };
            // };
            // reader.readAsDataURL(file);
        },
        editProductById(){
            this.isLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            let svar;
            if(Object.keys(this.variants).length < 1){
                svar = [{
                    variant_id:this.vitems[0].variant_id,
                    istax: this.istax,
                    price: this.price,
                    compareprice: this.compareprice,
                    costprice: this.costprice,
                    sku: this.sku,
                    stock: this.stockQuantity,
                    barcode: this.barcode,
                    featured_image:this.productImage,
                    weight: this.weight,
                    isdefault: 0,
                    weightUnit: this.weightUnit,
                    optvalue: {},
                    optname: [],
                }];
            } else {
                svar = this.vitems
            }
            const epro = {
                title:this.productname,
                body_html:this.quillContent,
                short_description:this.pro.short_description,
                featured_image:this.productImage,
                publish_status:this.pro.selectedSalesChannel,
                product_status:this.pro.pstatus,
                product_type_id:this.pro.ptype,
                brand_id:this.pro.brand,
                tags:this.pro.tags,
                handle:this.pro.handle,
                meta_title:this.pro.meta_title,
                meta_desc:this.pro.meta_desc,
                vitems:svar,
            }
            axios.post('/sadmin/product/update/'+this.product_id,epro,uheaders)
                .then((resp) => {
                    this.$store.commit('UPDATE_PRODUCT',resp.data.product)
                    this.$store.dispatch('fetchShopResources')
                        .then(()=>{
                            this.getProductByID();
                        })
                    window.Toast.success(resp.data.message);
                })
                .catch((err) => {
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        disCard(){
            this.getProductByID();
            this.vitems = [...this.vitems];
        },
        archiveProduct(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const sdelete = {
                product_id: this.product_id,
                mtype:"softdelete"
            }
            axios.post('/sadmin/product/delete/'+this.product_id,sdelete,uheaders)
                .then((resp)=>{
                    const now = new Date().toISOString()
                    this.$store.commit('UPDATE_PRODUCT',{
                        product_id: Number(this.product_id),
                        deleted_at: now,
                    })
                    this.pro.archived = true
                    window.Toast.success(resp.data.message);
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
        },
        restoreProduct(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const restore = {
                product_id: this.product_id,
                mtype:"restore"
            }
            axios.post('/sadmin/product/delete/'+this.product_id,restore,uheaders)
                .then((resp)=>{
                    this.$store.commit('UPDATE_PRODUCT',{
                        product_id: Number(this.product_id),
                        deleted_at: null,
                    })
                    window.Toast.success(resp.data.message);
                    this.pro.archived = false;
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
        },
        perdeleteProduct(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const pdelete = {
                product_id: this.product_id,
                mtype:"perdelete"
            }
            axios.post('/sadmin/product/delete/'+this.product_id,pdelete,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.$store.commit('DELETE_PRODUCT',{
                        product_id:Number(this.product_id)
                    })
                    this.$router.push({name:'products'});
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
        },
        onEditorChange(delta, oldDelta, source) {
            const quill = this.$refs.quillRef.getQuill();
            this.quillContent = quill.root.innerHTML;
        },
        stripHtml(html) {
            const div = document.createElement('div')
            div.innerHTML = html
            return div.textContent || div.innerText || ''
        },
        truncateText(text, limit) {
            if (text.length <= limit) return text
            return text.substring(0, limit) + '...'
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
                        this.$store.commit('ADD_PRODUCT_TYPE',newType)
                        // const exists = this.mptypes.some(pt => pt.product_type_id === newType.product_type_id);
                        // if (!exists) {
                        //     this.mptypes.push(newType);
                        // }
                        this.pro.product_type_id = newType.product_type_id;
                        this.typedPtype = '';
                        const inputEl = this.$refs.ptypeInputRef?.$el?.querySelector('input');
                        if (inputEl) inputEl.value = '';
                        this.$store.dispatch('fetchShopResources').then(() => {
                            this.getProductByID();
                        });
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
                        this.$store.commit('ADD_BRAND',newBrand)
                        // const exists = this.mbrands.some(pt => pt.brand_id === newBrand.brand_id);
                        // if (!exists) {
                        //     this.mbrands.push(newBrand);
                        // }
                        this.pro.brand_id = newBrand.brand_id;
                        this.typedBrand = '';
                        const inputEl = this.$refs.brandInputRef?.$el?.querySelector('input');
                        if (inputEl) inputEl.value = '';
                        window.Toast.success('New Brand Added')
                    }
                    this.$store.dispatch('fetchShopResources').then(() => {
                        this.getProductByID();
                    });
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
                        this.$store.commit('ADD_TAG',newTag)
                        // const exists = this.mtags.some(tag => tag.tag_id === newTag.tag_id);
                        // if (!exists) {
                        //     this.mtags.push(newTag);
                        // }
                        // if (!this.pro.tags.includes(newTag.tag_name)) {
                        //     this.pro.tags.push(newTag.tag_name);
                        // }
                        this.typedTag = '';
                        const inputEl = this.$refs.tagInputRef?.$el?.querySelector('input');
                        if (inputEl) inputEl.value = '';
                        this.$store.dispatch('fetchShopResources').then(() => {
                            this.getProductByID();
                        });
                        window.Toast.success('New Tag Added')
                    }
                })
                .catch((error)=>{
                    window.Toast.error('Some error to add Tag')
                })
        },
        addSection(){
            if (!this.selectToAdd) return;
            const sdata = {
                product_id:this.product_id,
                stype_id:this.selectToAdd.stype_id,
                section_json:this.selectToAdd,
                // sort_order:1,
                // selected_item:this.selectToAdd.stype_id
            }
            axios.post('/sadmin/product/section/add/new',sdata)
                .then((resp)=>{
                    this.getProductByID();
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
                await this.getProductByID();
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
                    this.getProductByID();
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
                    this.getProductByID();
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
                    this.getProductByID();
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
                    this.getProductByID();
                })
                .catch((err)=>{
                    window.Toast.error(err.message)
                })
                .finally(()=>{
                    this.delLoading = false;
                })
        },
        addHighlight(){
            const hdata = {
                fvalue:this.fvalue,
                position:1,
                feature_id:this.selectedFeature.feature_id,
                product_id:this.product_id,
            }
            axios.post('/sadmin/product/highlight/update',hdata)
                .then((resp)=>{
                    this.getProductByID();
                    this.addHighDialog = false;
                    window.Toast.success('Highlight Added Success')
                    this.selectedFeature = null;
                })
        },
        editHigh(item){
            this.editedIndex = this.highs.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.editHighDialog = true;
        },
        updateHighlight(){
            const udata = {
                highlight_id:this.editedItem.highlight_id,
                fvalue:this.editedItem.fvalue,
                position:1,
                feature_id:this.editedItem.feature_id,
                product_id:this.editedItem.product_id,
            }
            axios.post('/sadmin/product/highlight/update',udata)
                .then((resp)=>{
                    this.getProductByID();
                    this.editHighDialog = false;
                    window.Toast.success('Highlight Updated Success')
                })
        },
        delHigh(item){
            this.editedIndex = this.highs.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.deleteHighDialog = true;
        },
        deleteHighlight(){
            const ddata = {
                highlight_id:this.editedItem.highlight_id,
                fvalue:this.editedItem.fvalue,
                feature_id:this.editedItem.feature_id,
                product_id:this.editedItem.product_id,
            }
            axios.post('/sadmin/product/highlight/delete',ddata)
                .then((resp)=>{
                    this.getProductByID();
                    this.deleteHighDialog = false;
                    window.Toast.success('Highlight Deleted Success')
                })
        },
        addReview(){
            const rdata = {
                review_title:this.review.review_title,
                review_text:this.review?.review_text || this.review.review_title + ' ' + this.productname,
                rating:this.review.rating,
                reviewer_id:this.reviewers[5].id,
                created_at:this.review.date,
                product_id:this.product_id,
            }
            axios.post('/sadmin/product/review/add',rdata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.reviewDialog = false;
                    this.getProductByID();
                })
        },
        generateAiContent(){
            this.contentLoading = true;
            const uheaders = {headers: {'Content-Type': 'application/json'}};
            const aidata = {
                productTitle:this.productTitle || this.productname,
                keywords:this.keywords || this.productname,
                type:this.contentDesc
            }
            axios.post('/sadmin/generate/ai',aidata,uheaders)
                .then((resp)=>{
                    this.quillContent = resp.data.result.description;
                    const quill = this.$refs.quillRef.getQuill()
                    if (quill.getLength() <= 1) {
                        quill.clipboard.dangerouslyPasteHTML(this.quillContent);
                        const html = quill.root.innerHTML;
                        this.quillContent = html;
                    }
                    this.contentDialog = false;
                })
                .catch((err)=>{
                    console.log('err',err);
                })
                .finally(()=>{
                    this.contentLoading = false;
                    window.Toast.success('Product Description Generated');
                })
        },
        generateAiMetaTitle(){
            this.mtitleLoading = false;
            const uheaders = {headers: {'Content-Type': 'application/json'}};
            const aidata = {
                productTitle:this.productTitle || this.productname,
                keywords:this.keywords || this.productname,
                type:this.contentMtitle
            }
            axios.post('/sadmin/generate/ai',aidata,uheaders)
                .then((resp)=>{
                    this.pro.meta_title = resp.data.result.metaTitle;
                    this.mtitleDialog = false;
                })
                .catch((err)=>{
                    console.log('err',err);
                })
                .finally(()=>{
                    this.mtitleLoading = false;
                    window.Toast.success('Meta Title Generated');
                })
        },
        generateAiMetaDesc(){
            this.mdescLoading = true;
            const uheaders = {headers: {'Content-Type': 'application/json'}};
            const aidata = {
                productTitle:this.productTitle || this.productname,
                keywords:this.keywords || this.productname,
                type:this.contentMdesc
            }
            axios.post('/sadmin/generate/ai',aidata,uheaders)
                .then((resp)=>{
                    this.pro.meta_desc = resp.data.result.metaDescription;
                    this.mdescDialog = false;
                    window.Toast.success('Meta Description Generated');
                })
                .catch((err)=>{
                    console.log('err',err);
                    window.Toast.error('Content not Generated');
                })
                .finally(()=>{
                    this.mdescLoading = false;

                })
        },
        addSpecific(){
            const asdata = {
                stitle:this.defaultSpec.stitle,
                svalue:this.defaultSpec.svalue,
                product_id:this.product_id,
            }
            axios.post('/sadmin/specific/update',asdata)
                .then((resp)=>{
                    this.addSpecDialog = false;
                    window.Toast.success(resp.data.message);
                    this.getProductByID();
                    this.defaultSpec.stitle = "";
                    this.defaultSpec.svalue = "";
                })
        },
        editSpec(spec){
            this.editedSpecIndex = this.specifics.indexOf(spec);
            this.editedSpec = Object.assign({},spec);
            this.editSpecDialog = true;
        },
        editSpecific(){
            const esdata = {
                specific_id:this.editedSpec.specific_id,
                stitle:this.editedSpec.stitle,
                svalue:this.editedSpec.svalue,
                product_id:this.product_id,
                shop_id:this.editedSpec.shop_id,
            }
            axios.post('/sadmin/specific/update',esdata)
                .then((resp)=>{
                    this.editSpecDialog = false;
                    window.Toast.success(resp.data.message);
                    this.getProductByID();
                })
        },
        deleteSpec(spec){
            this.editedSpecIndex = this.specifics.indexOf(spec);
            this.deletedSpec = Object.assign({},spec);
            this.deleteSpecDialog = true;
        },
        deleteSpecific(){
            const dsdata = {
                specific_id:this.deletedSpec.specific_id,
            }
            axios.post('/sadmin/specific/delete',dsdata)
                .then((resp)=>{
                    this.deleteSpecDialog = false;
                    window.Toast.success(resp.data.message);
                    this.getProductByID();
                })
        },
        updateBulkPrice(){
            const selectedIds = this.selectedVariants.map(v => v.variant_id);
            const uprice = {
                product_id:this.product_id,
                bulkprice:this.bulkprice,
                bulkcompare:this.bulkcompare,
                bulkcost:this.bulkcost,
                selectedIds:selectedIds,
            }
            axios.post('/sadmin/product/bulk/update',uprice)
                .then((resp)=>{
                    this.$store.commit('UPDATE_PRODUCT', {
                        product_id:Number(this.product_id)
                    })
                    window.Toast.success(resp.data.message);
                    this.getProductByID();
                    this.selectedVariants = [];
                    this.bulkPriceDialog = false;
                })
        },
        updateBulkQty(){
            const selectedvIds = this.selectedVariants.map(v => v.variant_id);
            const uqty = {
                product_id:this.product_id,
                bulkqty:this.bulkqty,
                selectedIds:selectedvIds,
            }
            axios.post('/sadmin/product/bulk/update',uqty)
                .then((resp)=>{
                    this.$store.commit('UPDATE_PRODUCT', {
                        product_id:Number(this.product_id)
                    })
                    window.Toast.success(resp.data.message);
                    this.getProductByID();
                    this.selectedVariants = [];
                    this.bulkQtyDialog = false;
                    this.bulkqty = 0;
                })
        }
    }
}
</script>

<style scoped>
.optvals {
    max-width: 100%;
    min-width: 190px;
}
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
