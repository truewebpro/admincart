<template>
    <v-container>
        <v-row>
            <v-col cols="3" md="6">
                <span class="text-h6">Product Labels</span>
            </v-col>
            <v-col cols="9" md="6" class="text-end">
                <v-btn color="primary" prependIcon="mdi-plus" density="comfortable" @click="openAdd()">
                    Add Label
                </v-btn>
            </v-col>
            <v-col cols="12" md="12">
                <v-card flat>
                    <v-data-table :items="labels" :headers="labelsHeaders" mobileBreakpoint="sm">
                        <template #item.preview="{item}">
                            <img v-if="item.image" :src="cdn+item.image" alt="" class="rounded" style="width:28px;height:28px;object-fit:cover" />
                            <span v-else
                                  class="px-2 py-1 rounded text-caption font-weight-medium"
                                  :style="{ backgroundColor: item.bg_color || '#111827', color: item.color || '#ffffff' }"
                            >
                                {{ item.use_label ? item.label : '(no text)' }}
                            </span>
                        </template>
                        <template #item.position="{item}">
                            {{ positionLabel(item.position) }}
                        </template>
                        <template #item.mode="{item}">
                            <v-chip :color="item.is_smart ? 'blue' : 'grey'" size="small" density="comfortable">
                                {{ item.is_smart ? 'Smart (rules)' : 'Manual' }}
                            </v-chip>
                        </template>
                        <template #item.products="{item}">
                            <v-chip size="small" variant="outlined">{{ item.products_count }}</v-chip>
                        </template>
                        <template #item.status="{item}">
                            <v-chip :color="item.is_active ? 'green' : 'grey'" density="compact" class="font-weight-medium">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </v-chip>
                        </template>
                        <template #item.actions="{item}">
                            <div class="d-flex ga-2">
                                <v-btn color="info" variant="outlined" icon="mdi-pencil"
                                       @click="editLabel(item)" density="comfortable" />
                                <v-btn color="secondary" variant="outlined" icon="mdi-filter-variant"
                                       @click="openRules(item)" density="comfortable" />
                                <v-btn v-if="!item.is_smart" color="teal" variant="outlined" icon="mdi-link-variant"
                                       @click="openAssign(item)" density="comfortable" />
                                <v-btn color="red" variant="outlined" icon="mdi-delete"
                                       @click="confirmDelete(item.id)" density="comfortable" />
                            </div>
                        </template>
                    </v-data-table>
                    <!-- Create / Edit dialog -->
                    <v-dialog v-model="showLabelDialog" max-width="600">
                        <v-card>
                            <v-card-title>{{ isEditMode ? 'Edit Label' : 'Create Label' }}</v-card-title>
                            <v-card-text>
                                <v-text-field
                                    variant="underlined" density="comfortable" persistentPlaceholder
                                    v-model="defaultLabel.label" label="Label Text"
                                    placeholder="e.g. New Arrival"
                                />
                                <v-switch
                                    v-model="defaultLabel.use_label" color="primary" density="comfortable"
                                    label="Show this text (off = image only)"
                                />

                                <div class="mb-4">
                                    <v-img v-if="defaultLabel.image != null" :src="cdn+defaultLabel.image" max-height="100"></v-img>
                                    <v-file-upload v-model="imageFile" density="compact"
                                                   icon="mdi-tag-plus"
                                                   accept="image/*" title="Label Image (Optional)"
                                                   browse-text="Image (Optional)"></v-file-upload>
                                </div>

                                <v-select
                                    variant="underlined" density="comfortable" persistentPlaceholder
                                    v-model="defaultLabel.position"
                                    :items="positions"
                                    item-title="label"
                                    item-value="value"
                                    label="Position"
                                />

                                <v-row dense>
                                    <v-col cols="6">
                                        <v-text-field
                                            variant="underlined" density="comfortable" persistentPlaceholder
                                            v-model="defaultLabel.color" label="Text Color" placeholder="#FFFFFF"
                                        />
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            variant="underlined" density="comfortable" persistentPlaceholder
                                            v-model="defaultLabel.bg_color" label="Background Color" placeholder="#16A34A"
                                        />
                                    </v-col>
                                </v-row>

                                <v-combobox
                                    variant="underlined" density="comfortable" persistentPlaceholder
                                    v-model="defaultLabel.style"
                                    :items="['solid', 'outline', 'ribbon']"
                                    label="Style"
                                />

                                <v-switch
                                    v-model="defaultLabel.is_active" color="primary" density="comfortable"
                                    label="Active"
                                />
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer />
                                <v-btn variant="text" @click="showLabelDialog = false">Cancel</v-btn>
                                <v-btn color="primary" @click="saveLabel" :loading="saveLoading">Save</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Rule builder dialog -->
                    <v-dialog v-model="showRulesDialog" max-width="700">
                        <v-card>
                            <v-card-title>Rules for "{{ rulesLabel?.label }}"</v-card-title>
                            <v-card-text>
                                <p class="text-caption text-medium-emphasis mb-3">
                                    A label with no rules is manual (assign products directly instead).
                                    Any rules here make it auto-apply — products are re-evaluated on save.
                                </p>

                                <div v-for="(rule, idx) in rules" :key="idx" class="d-flex ga-2 align-center mb-2">
                                    <v-select
                                        v-model="rule.column" :items="columnOptions" item-title="label" item-value="value"
                                        label="Field" density="compact" variant="outlined" style="max-width:140px"
                                        @update:model-value="onColumnChange(rule)"
                                    />
                                    <v-select
                                        v-model="rule.relation" :items="relationOptionsFor(rule.column)" item-title="label" item-value="value"
                                        label="Condition" density="compact" variant="outlined" style="max-width:160px"
                                    />
                                    <v-select
                                        v-if="rule.column === 'brand'"
                                        v-model="rule.condition" :items="brands" item-title="brand_name" item-value="brand_id"
                                        label="Brand" density="compact" variant="outlined"
                                    />
                                    <v-select
                                        v-else-if="rule.column === 'type'"
                                        v-model="rule.condition" :items="productTypes" item-title="product_type_name" item-value="product_type_id"
                                        label="Product Type" density="compact" variant="outlined"
                                    />
                                    <v-text-field
                                        v-else
                                        v-model="rule.condition" label="Value" density="compact" variant="outlined"
                                    />
                                    <v-select
                                        v-if="idx === 0"
                                        v-model="rule.join_type" :items="[{value:'and',label:'Match ALL (AND)'},{value:'or',label:'Match ANY (OR)'}]"
                                        item-title="label" item-value="value"
                                        label="Combine rules" density="compact" variant="outlined" style="max-width:180px"
                                    />
                                    <v-btn icon="mdi-close" size="small" variant="text" color="red" @click="removeRule(idx)" />
                                </div>

                                <v-btn variant="outlined" prependIcon="mdi-plus" size="small" @click="addRule">
                                    Add Condition
                                </v-btn>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer />
                                <v-btn variant="text" @click="showRulesDialog = false">Cancel</v-btn>
                                <v-btn color="primary" @click="saveRules" :loading="rulesLoading">Save & Re-sync Products</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Manual assign dialog -->
                    <v-dialog v-model="showAssignDialog" max-width="600">
                        <v-card>
                            <v-card-title>Assign Products to "{{ assigningLabel?.label }}"</v-card-title>
                            <v-card-text>
                                <v-autocomplete
                                    v-model="assignSearch"
                                    :items="productOptions"
                                    item-title="title"
                                    item-value="product_id"
                                    label="Search products"
                                    chips
                                    multiple
                                    closable-chips
                                    variant="underlined"
                                    density="comfortable"
                                    no-filter
                                    @update:search="onProductSearch"
                                />
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer />
                                <v-btn variant="text" @click="showAssignDialog = false">Cancel</v-btn>
                                <v-btn color="primary" @click="saveAssign" :loading="assignLoading">Save</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Delete confirm -->
                    <v-dialog v-model="deleteDialog" max-width="400">
                        <v-card>
                            <v-card-title>Delete Label?</v-card-title>
                            <v-card-text>This removes it from every product it's on. This can't be undone.</v-card-text>
                            <v-card-actions>
                                <v-spacer />
                                <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                                <v-btn color="red" @click="deleteLabel" :loading="deleteLoading">Delete</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import axios from "axios";
import {VFileUpload} from "vuetify/labs/components";

const COLUMN_OPTIONS = [
    { value: 'tag', label: 'Tag' },
    { value: 'type', label: 'Product Type' },
    { value: 'brand', label: 'Brand' },
    { value: 'title', label: 'Title' },
];

const RELATIONS_BY_COLUMN = {
    tag: [{ value: 'equals', label: 'Has tag' }],
    type: [{ value: 'equals', label: 'Is' }, { value: 'not_equals', label: 'Is not' }],
    brand: [{ value: 'equals', label: 'Is' }, { value: 'not_equals', label: 'Is not' }],
    title: [{ value: 'contains', label: 'Contains' }, { value: 'not_contains', label: "Doesn't contain" }],
};

export default {
    name: "ProductLabelManager",
    components:{VFileUpload},
    data() {
        return {
            cdn:this.$store.state.cdn,
            labels: [],
            labelsHeaders:[
                {title:'Preview',value:'preview'},
                {title:'Position',value:'position'},
                {title:'Mode',value:'mode'},
                {title:'Products',value:'products'},
                {title:'Status',value:'status'},
                {title:'Actions',value:'actions'},
            ],
            positions: [],
            brands: [],
            productTypes: [],
            columnOptions: COLUMN_OPTIONS,

            showLabelDialog: false,
            isEditMode: false,
            defaultLabel: this.getDefaultLabel(),
            imageFile: null,
            saveLoading: false,

            showRulesDialog: false,
            rulesLabel: null,
            rules: [],
            rulesLoading: false,

            showAssignDialog: false,
            assigningLabel: null,
            assignSearch: [],
            productOptions: [],
            assignLoading: false,

            deleteDialog: false,
            deleteLabelId: null,
            deleteLoading: false,
        };
    },
    mounted() {
        this.getAllLabels();
        this.searchProducts();
    },
    methods: {
        mediaUrl(path) {
            return `${this.$mediaUrl || ''}${path}`; // adjust to however your app exposes mediaUrl
        },
        getDefaultLabel() {
            return {
                id: null,
                label: '',
                use_label: true,
                color: '#FFFFFF',
                bg_color: '#16A34A',
                style: 'solid',
                image: null,
                position: 'top-left',
                is_active: true,
            };
        },
        positionLabel(value) {
            return this.positions.find(p => p.value === value)?.label || value;
        },
        relationOptionsFor(column) {
            return RELATIONS_BY_COLUMN[column] || [];
        },
        async getAllLabels() {
            try {
                const res = await axios.get('/sadmin/product-label/list');
                if (res.data.success) {
                    this.labels = res.data.labels;
                    this.positions = res.data.positions;
                    this.brands = res.data.brands;
                    this.productTypes = res.data.product_types;
                }
            } catch (e) {
                console.error(e);
            }
        },
        async searchProducts(search = '') {
            try {
                const res = await axios.get('/sadmin/product-label/products', { params: { search } });
                if (res.data.success) {
                    const selected = this.productOptions.filter(p => this.assignSearch.includes(p.product_id));
                    const newOnes = res.data.products.filter(p => !selected.some(s => s.product_id === p.product_id));
                    this.productOptions = [...selected, ...newOnes];

                }
            } catch (e) {
                console.error(e);
            }
        },
        onProductSearch(val) {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => {
                this.searchProducts(val);
            }, 300);
        },

        openAdd() {
            this.isEditMode = false;
            this.defaultLabel = this.getDefaultLabel();
            this.imageFile = null;
            this.showLabelDialog = true;
        },
        editLabel(l) {
            this.isEditMode = true;
            this.defaultLabel = { ...l };
            this.imageFile = null;
            this.showLabelDialog = true;
        },
        async saveLabel() {
            this.saveLoading = true;
            try {
                const form = new FormData();
                Object.entries(this.defaultLabel).forEach(([key, val]) => {
                    if (key === 'image') return; // handled below via file input
                    if (val !== null && val !== undefined) form.append(key, val === true ? 1 : val === false ? 0 : val);
                });
                if (this.imageFile) {
                    form.append('image', this.imageFile);
                }
                const res = await axios.post('/sadmin/product-label/save', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                if (res.data.success) {
                    this.showLabelDialog = false;
                    await this.getAllLabels();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.saveLoading = false;
            }
        },

        openRules(l) {
            this.rulesLabel = l;
            this.rules = (l.rules || []).map(r => ({
                ...r,
                condition: ['brand', 'type'].includes(r.column) ? Number(r.condition) : r.condition,
            }));
            this.showRulesDialog = true;
        },
        addRule() {
            this.rules.push({ column: 'tag', relation: 'equals', condition: '', join_type: 'and' });
        },
        onColumnChange(rule) {
            rule.condition = '';
            rule.relation = this.relationOptionsFor(rule.column)[0]?.value ?? 'equals';
        },

        removeRule(idx) {
            this.rules.splice(idx, 1);
        },
        async saveRules() {
            this.rulesLoading = true;
            try {
                const res = await axios.post('/sadmin/product-label/save-rules', {
                    id: this.rulesLabel.id,
                    rules: this.rules,
                });
                if (res.data.success) {
                    this.showRulesDialog = false;
                    await this.getAllLabels();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.rulesLoading = false;
            }
        },

        openAssign(l) {
            this.assigningLabel = l;
            this.assignSearch = [];
            this.showAssignDialog = true;
        },
        async saveAssign() {
            this.assignLoading = true;
            try {
                const res = await axios.post('/sadmin/product-label/assign-products', {
                    id: this.assigningLabel.id,
                    product_ids: this.assignSearch,
                });
                if (res.data.success) {
                    this.showAssignDialog = false;
                    await this.getAllLabels();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.assignLoading = false;
            }
        },

        confirmDelete(id) {
            this.deleteLabelId = id;
            this.deleteDialog = true;
        },
        async deleteLabel() {
            this.deleteLoading = true;
            try {
                const res = await axios.post('/sadmin/product-label/delete', { id: this.deleteLabelId });
                if (res.data.success) {
                    this.deleteDialog = false;
                    this.deleteLabelId = null;
                    await this.getAllLabels();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.deleteLoading = false;
            }
        },
    },
};
</script>

<style scoped>
</style>
