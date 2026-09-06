<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-item>
                        <template #prepend>
                            <v-icon>mdi-sale</v-icon>
                        </template>
                        <template #title>
                            <div class="text-h5 font-weight-bold">Discount Coupons and Offers</div>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-tabs v-model="mtabs" density="compact" color="primary" selectedClass="bg-lblue"
                        bgColor="grey-lighten-3" sliderColor="primary"
                        class="my-2">
                    <v-tab value="coups" class="bg-white">Coupons</v-tab>
                    <v-tab value="prule" class="bg-white">Pricing Rule (Products / Cats )</v-tab>
                    <v-tab value="badges" class="bg-white">Badges</v-tab>
                </v-tabs>
                <v-tabs-window v-model="mtabs">
                    <v-tabs-window-item value="coups">
                        <v-card flat>
                            <v-card-text>
                                <v-card>
                                    <v-card-title class="d-flex ga-2 justify-space-between">
                                        Coupons
                                        <div class="d-flex mb-3 ga-3">
                                            <v-btn color="primary" density="comfortable"
                                                   @click="openAddCoupon('fixed')">+ Amount off Order/Products</v-btn>
                                            <v-btn color="success" density="comfortable"
                                                   @click="openAddCoupon('bogo')">+ Buy X Get Y</v-btn>
                                            <v-btn color="info" density="comfortable"
                                                   @click="openAddCoupon('bundle')">+ Bundle</v-btn>
                                        </div>
                                    </v-card-title>
                                    <v-table>
                                        <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                            <th>Applies To</th>
                                            <th>Usage</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="c in allcoupons" :key="c.coupon_id">
                                            <td>{{ c.code }} <br/> {{ c.display_title || c.code || c.title }}</td>
                                            <td>{{ c.type }}</td>
                                            <td>
                                                <v-chip class="mt-2 font-weight-medium" color="primary" variant="outlined" density="compact">
                                                    {{ c.label }}
                                                </v-chip>
                                            </td>
                                            <td>{{ c.applies_to }}</td>
                                            <td>
                                                {{ c.used || 0 }} / {{ c.usage_limit || '∞' }}
                                            </td>
                                            <td>
                                                <v-chip :color="c.is_active ? 'green' : 'grey'">
                                                    {{ c.is_active ? 'Active' : 'Inactive' }}
                                                </v-chip>
                                            </td>
                                            <td>
                                                <div class="d-flex ga-2">
                                                    <v-btn color="info" variant="outlined" icon="mdi-pencil"
                                                           @click="editCoupon(c)" density="comfortable" />
                                                    <v-btn color="red" variant="outlined" icon="mdi-delete"
                                                           @click="confirmDeleteCoupon(c.coupon_id)" density="comfortable" />
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </v-table>
                                </v-card>
                            </v-card-text>
                        </v-card>
                    </v-tabs-window-item>
                    <v-tabs-window-item value="prule">
                        <v-card flat>
                            <v-card-text>
                                <v-card>
                                    <v-card-title class="d-flex ga-2 justify-space-between">
                                        Pricing Rules
                                        <v-btn color="primary" prependIcon="mdi-plus" density="comfortable" @click="openRuleForm()">Add Rule</v-btn>
                                    </v-card-title>
                                    <v-table>
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Scope</th>
                                            <th>Min Qty</th>
                                            <th>Value</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="rule in rules" :key="rule.id">
                                            <td>{{ rule.name }} <br/>{{rule.label}}</td>
                                            <td>{{ rule.type }}</td>
                                            <td>{{ rule.scope }}</td>
                                            <td>{{ rule.min_qty }}</td>
                                            <td>
                                                      <span v-if="rule.type === 'bundle'">
                                                        £{{ rule.price }}
                                                      </span>
                                                <span v-else>
                                                        {{ rule.discount_percent }}%
                                                      </span>
                                            </td>
                                            <td>
                                                <v-chip :color="rule.is_active ? 'green' : 'grey'"
                                                        density="compact" class="font-weight-medium">
                                                    {{ rule.is_active ? 'Active' : 'Inactive' }}
                                                </v-chip>
                                            </td>
                                            <td>
                                                <div class="d-flex ga-2">
                                                    <v-btn color="info" variant="outlined" icon="mdi-pencil"
                                                           @click="editRule(rule)" density="comfortable" />
                                                    <v-btn color="red" variant="outlined" icon="mdi-delete"
                                                           @click="confirmDelete(rule.id)" density="comfortable" />
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </v-table>
                                </v-card>
                            </v-card-text>
                        </v-card>
                    </v-tabs-window-item>
                    <v-tabs-window-item value="badges">
                        <BadgeManager/>
                    </v-tabs-window-item>
                </v-tabs-window>
            </v-col>
        </v-row>
        <v-dialog v-model="showRuleForm" max-width="700">
            <v-card>
                <v-card-title>
                    {{ isRuleEditMode ? 'Edit Pricing Rule' : 'Create Pricing Rule' }}
                </v-card-title>
                <v-card-text>
                    <v-form ref="defaultRule">
                        <v-text-field variant="underlined" density="comfortable" persistentPlaceholder
                                      v-model="defaultRule.name" label="Rule Name"/>
                        <!-- Type -->
                        <v-select variant="underlined" density="comfortable" persistentPlaceholder
                            v-model="defaultRule.type"
                            :items="['bundle','volume']"
                            label="Rule Type"
                        />

                        <!-- Scope -->
                        <v-select variant="underlined" density="comfortable" persistentPlaceholder
                            v-model="defaultRule.scope"
                            :items="['all','products','cats']"
                            label="Apply To"
                        />

                        <!-- Min Qty -->
                        <v-number-input variant="outlined" density="compact" persistentPlaceholder
                            v-model="defaultRule.min_qty"
                            label="Minimum Quantity"
                        />

                        <!-- Bundle -->
                        <v-number-input variant="outlined" density="compact" persistentPlaceholder
                            v-if="defaultRule.type === 'bundle'" :precision="2"
                            v-model="defaultRule.price"
                            prefix="£"
                            label="Bundle Price"
                        />

                        <!-- Volume -->
                        <v-number-input variant="outlined" density="compact" persistentPlaceholder
                            v-if="defaultRule.type === 'volume'"
                            v-model="defaultRule.discount_percent"
                            suffix="%"
                            label="Discount %"
                        />

                        <!-- Products -->
                        <v-autocomplete variant="underlined" label="Products" density="compact" persistentPlaceholder
                            v-if="defaultRule.scope === 'products'" :items="products" multiple
                                        item-title="title" item-value="product_id"
                                        chips closable-chips
                            v-model="defaultRule.products" clearable
                        />

                        <!-- Categories -->
                        <v-autocomplete variant="underlined" label="Cats" persistentPlaceholder
                            v-if="defaultRule.scope === 'cats'" :items="cats" multiple
                                        item-title="cat_name" item-value="cat_id"
                            v-model="defaultRule.cats"
                        />

                        <!-- Priority -->
                        <v-number-input variant="outlined" density="compact" persistentPlaceholder
                            v-model="defaultRule.priority"
                            label="Priority (lower = higher priority)"
                        />

                        <!-- Dates -->
                        <v-row>
                            <v-col>
                                <v-date-input variant="underlined" density="compact" persistentPlaceholder
                                    v-model="defaultRule.starts_at"
                                    label="Start Date"></v-date-input>
                            </v-col>

                            <v-col>
                                <v-date-input variant="underlined" density="compact" persistentPlaceholder
                                              v-model="defaultRule.expires_at"
                                              label="Expiry Date"></v-date-input>
                            </v-col>
                        </v-row>
                        <!-- Active -->
                        <v-switch color="green" v-model="defaultRule.is_active" label="Active"/>
                    </v-form>
                </v-card-text>

                <v-card-actions>
                    <v-btn @click="addRuleForm" color="success">Save</v-btn>
                    <v-btn @click="showRuleForm=false">Cancel</v-btn>
                </v-card-actions>

            </v-card>
        </v-dialog>
        <v-dialog v-model="deleteRuleDialog" max-width="400">
            <v-card>
                <v-card-title class="text-h6 text-center">
                    Delete Pricing Rule
                </v-card-title>
                <v-card-text class="text-center">
                    Are you sure you want to delete <br/>
                    <strong>{{ rules.find(r => r.id === deleteRuleId)?.name }}</strong> ?
                </v-card-text>
                <v-card-actions class="justify-end">
                    <v-btn variant="text" @click="deleteRuleDialog = false" density="comfortable">
                        Cancel
                    </v-btn>
                    <v-btn :loading="deleteRuleLoading" color="red" @click="deleteRule" variant="elevated" density="comfortable">
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="deleteCouponDialog" max-width="400">
            <v-card>
                <v-card-title class="text-h6 text-center">
                    Delete Coupon
                </v-card-title>
                <v-card-text class="text-center">
                    Are you sure you want to delete <br/>
                    <strong>{{ allcoupons.find(c => c.coupon_id === deleteCouponId)?.code }}</strong> ?
                </v-card-text>
                <v-card-actions class="justify-end">
                    <v-btn variant="text" @click="deleteCouponDialog = false" density="comfortable">
                        Cancel
                    </v-btn>
                    <v-btn :loading="deleteCouponLoading" color="red" @click="deleteCoupon" variant="elevated" density="comfortable">
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="showCouponDialog" :key="defaultCoupon.coupon_id || 'new'" maxWidth="700">
            <v-card>
                <v-card-title>
                    {{ dialogTitle }}
                </v-card-title>
                <v-card-text>
                    <DiscountForm
                        v-if="activeDialog === 'fixed'"
                        :coupon="defaultCoupon"
                        :products="products"
                        :cats="cats"
                    />
                    <BogoForm v-if="activeDialog === 'bogo'"
                              :coupon="defaultCoupon"
                              :products="products"
                              :cats="cats"/>
                    <BundleForm v-if="activeDialog === 'bundle'"
                              :coupon="defaultCoupon"
                              :products="products"
                              :cats="cats"/>
                </v-card-text>
                <v-card-actions>
                    <v-btn text @click="showCouponDialog = false">Cancel</v-btn>
                    <v-btn variant="elevated" color="primary" @click="saveCoupon">{{ isEditMode ? 'Update Coupon' : 'Save Coupon' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script>
import axios from "axios";
import { VDateInput} from "vuetify/labs/VDateInput";
import DiscountForm from "../settings/coupons/DiscountForm.vue";
import BogoForm from "../settings/coupons/BogoForm.vue";
import BundleForm from "../settings/coupons/BundleForm.vue";
import BadgeManager from "../settings/coupons/BadgeManager.vue";

export default {
    name:"SettingsMarketing",
    components:{
        BadgeManager,
        BundleForm,
        BogoForm,
        DiscountForm,
        VDateInput
    },
    computed:{
        isEditMode(){
            return !!this.defaultCoupon?.coupon_id;
        },
        dialogTitle(){
            if(this.activeDialog === 'bogo'){
                return this.isEditMode ? 'Edit Buy X Get Y Offer' : 'Create Buy X Get Y Offer';
            }

            if(this.activeDialog === 'bundle'){
                return this.isEditMode ? 'Edit Bundle Offer' : 'Create Bundle Offer';
            }

            return this.isEditMode ? 'Edit Discount Coupon' : 'Create Discount Coupon';
        }
    },
    data(){
        return{
            mtabs:"coups",
            rules: [],
            showRuleForm: false,
            deleteRuleDialog: false,
            deleteRuleLoading:false,
            isRuleEditMode: false,
            deleteRuleId: null,
            defaultRule: {
                name:"",
                type: 'bundle',
                scope: 'all',
                min_qty: 2,
                price: null,
                discount_percent: null,
                products: [],
                cats: [],
                priority: 0,
                starts_at: null,
                expires_at: null,
                is_active: true
            },
            allcoupons:[],
            deleteCouponDialog: false,
            deleteCouponLoading:false,
            deleteCouponId:null,
            enableConditions: false,
            activeDialog: null, // 'fixed' | 'bogo' | 'bundle'
            showCouponDialog: false,
            defaultCoupon: this.getDefaultCoupon(),
            products:[],
            cats:[],
        }
    },
    watch: {
        'defaultRule.scope'(val){
            if (val === 'products') this.defaultRule.cats = [];
            if (val === 'cats') this.defaultRule.products = [];
            if (val === 'all') {
                this.defaultRule.products = [];
                this.defaultRule.cats = [];
            }
        },
        'defaultCoupon.type'(val){
            // reset type-specific fields
            this.defaultCoupon.buy_qty = null;
            this.defaultCoupon.get_qty = null;
            this.defaultCoupon.bundle_qty = null;
            this.defaultCoupon.bundle_price = null

            // 🔥 important
            this.defaultCoupon.value = 0;

            // 🔥 clear conditions if not needed
            this.enableConditions = false;
            this.resetConditions();

            // 🔥 if BOGO → force products
            if (val === 'bogo') {
                this.defaultCoupon.applies_to = 'products';
            }
        },
        'defaultCoupon.applies_to'(val){
            if (val !== 'products') {
                this.defaultCoupon.products = [];
            }

            if (val !== 'cats') {
                this.defaultCoupon.cats = [];
            }

            if (val === 'entire_order') {
                this.defaultCoupon.products = [];
                this.defaultCoupon.cats = [];
            }
            // 🔥 optional: reset conditions
            this.defaultCoupon.condition_products = [];
            this.defaultCoupon.condition_cats = [];
        },
        'defaultCoupon.code'(val){
            if (!val) return;
            const cleaned = val.replace(/\s+/g, '').toUpperCase();
            if (cleaned !== val) {
                this.defaultCoupon.code = cleaned;
            }
        },
        enableConditions(val){
            if (!val) {
                this.resetConditions();
            }
        }
    },
    async mounted(){
        await this.fetchRules();
    },
    created() {
        this.getAllCoupons();
    },
    methods:{
        resetConditions(){
            this.defaultCoupon.condition_min_qty = null;
            this.defaultCoupon.condition_products = [];
            this.defaultCoupon.condition_cats = [];
        },
        async fetchRules(){
            try {
                const respo = await axios.get('/sadmin/pricing-rules/list');
                if(respo.data.success){
                    this.rules = respo.data.rules;
                }
            } catch (e) {
                console.error(e);
            }
        },
        getDefaultRule() {
            return {
                name: "",
                type: 'bundle',
                scope: 'all',
                min_qty: 2,
                price: null,
                discount_percent: null,
                products: [],
                cats: [],
                priority: 0,
                starts_at: null,
                expires_at: null,
                is_active: true
            };
        },
        openRuleForm(){
            this.isRuleEditMode = false;
            this.defaultRule = this.getDefaultRule();
            this.showRuleForm = true;
        },
        async addRuleForm(){
            try {
                const payload = { ...this.defaultRule };
                // format dates (important)
                if (payload.starts_at) {
                    payload.starts_at = new Date(payload.starts_at).toISOString();
                }
                if (payload.expires_at) {
                    payload.expires_at = new Date(payload.expires_at).toISOString();
                }
                const res = await axios.post('/sadmin/pricing-rule/save', payload);
                if (res.data.success) {
                    this.showRuleForm = false;
                    this.isRuleEditMode = false;
                    await this.fetchRules();
                }
            } catch (e) {
                console.error(e);
            }
        },
        editRule(rule){
            const r = JSON.parse(JSON.stringify(rule));
            if (r.starts_at) r.starts_at = new Date(r.starts_at);
            if (r.expires_at) r.expires_at = new Date(r.expires_at);
            this.defaultRule = r;
            this.isRuleEditMode = true;
            // this.defaultRule = { ...rule };
            this.showRuleForm = true;
        },
        confirmDelete(id){
            this.deleteRuleId = id;
            this.deleteRuleDialog = true;
        },
        async deleteRule(){
            this.deleteRuleLoading = true;
            try {
                const res = await axios.post('/sadmin/pricing-rule/delete', {
                    id: this.deleteRuleId
                });

                if (res.data.success) {
                    this.deleteRuleDialog = false;
                    this.deleteRuleId = null;
                    await this.fetchRules();
                }

            } catch (e) {
                console.error(e);
            } finally {
                this.deleteRuleLoading = false;
            }
        },
        getAllCoupons(){
            axios.get('/sadmin/coupons/list')
                .then((resp)=>{
                    this.allcoupons = resp.data.coupons;
                    this.cats = resp.data.cats;
                    this.products = resp.data.products;
                })
        },
        getCouponLabel(c){
            if(c.type === 'bogo'){
                return `Buy ${c.buy_qty || 0} Get ${c.get_qty || 0} Free`;
            }
            if(c.type === 'bundle'){
                return `${c.bundle_qty || 0} for £${c.bundle_price || 0}`;
            }
            if(c.type === 'percentage'){
                return `${c.value || 0}% OFF`;
            }
            return `£${c.value || 0} OFF`;
        },
        getDefaultCoupon(){
            return {
                coupon_id: null,
                code: '',
                title: '',
                display_title: '',
                type: 'fixed',
                value: 0,
                applies_to: 'entire_order',
                products: [],
                cats: [],
                min_order_amount: null,
                usage_limit: null,
                per_customer_limit: null,
                is_active: true,
                is_stackable: false,
                priority: 0,
                starts_at: null,
                expires_at: null,
                conditions: {},
                buy_qty: null,
                get_qty: null,
                bundle_qty: null,
                bundle_price: null,
            }
        },
        openAddCoupon(type){
            this.defaultCoupon = this.getDefaultCoupon();
            if(type === 'percentage'){
                this.activeDialog = 'fixed';
                this.defaultCoupon.type = 'percentage';
            } else {
                this.activeDialog = type;
                this.defaultCoupon.type = type;
            }
            this.showCouponDialog = true;

            // set type
            this.defaultCoupon.type = type;

            // auto rules
            if(['bogo','bundle'].includes(type)){
                this.defaultCoupon.is_auto = true;
                this.defaultCoupon.code = ''; // no manual code
            }
        },
        editCoupon(c){
            const coupon = JSON.parse(JSON.stringify(c));
            console.log('edit coupon',coupon);
            const cond = coupon.conditions || {};
            // 🔥 TYPE-SPECIFIC MAPPING

            // BOGO
            if (coupon.type === 'bogo') {
                coupon.buy_qty = cond.buy_qty || null;
                coupon.get_qty = cond.get_qty || null;
            }

            // Bundle
            if (coupon.type === 'bundle') {
                coupon.bundle_qty = cond.bundle_qty || null;
                coupon.bundle_price = cond.bundle_price || null;
            }

            // 🔥 ADVANCED CONDITIONS
            if (cond.min_qty || cond.product_ids || cond.category_ids) {
                coupon.enableConditions = true;
                coupon.condition_min_qty = cond.min_qty || null;
                coupon.condition_products = cond.product_ids || [];
                coupon.condition_cats = cond.category_ids || [];
            } else {
                coupon.enableConditions = false;
                coupon.condition_min_qty = null;
                coupon.condition_products = [];
                coupon.condition_cats = [];
            }

            // 🔥 NORMALIZE ARRAYS (IMPORTANT)
            coupon.products = coupon.products || [];
            coupon.cats = coupon.cats || [];

            // 🔥 assign to form
            this.defaultCoupon = coupon;

            if (coupon.type === 'percentage') {
                this.activeDialog = 'fixed'; // reuse same form
            } else {
                this.activeDialog = coupon.type; // 'fixed', 'percentage', 'bogo', 'bundle'
            }

            this.showCouponDialog = true;
        },
        async saveCoupon(){
            try {
                const coupon = { ...this.defaultCoupon };
                coupon.code = coupon.code.toUpperCase();
                // ✅ build conditions
                let conditions = {};
                // 🔥 bundle
                if(coupon.type === 'bundle'){
                    conditions.bundle_qty = coupon.bundle_qty;
                    conditions.bundle_price = coupon.bundle_price;
                }

                // 🔥 bogo
                if(coupon.type === 'bogo'){
                    conditions.buy_qty = coupon.buy_qty;
                    conditions.get_qty = coupon.get_qty;
                }
                // 🔥 advanced conditions (can work WITH bundle/bogo)
                if(coupon.enableConditions){
                    if(coupon.condition_min_qty){
                        conditions.min_qty = coupon.condition_min_qty;
                    }

                    if(coupon.condition_products?.length){
                        conditions.product_ids = coupon.condition_products;
                    }

                    if(coupon.condition_cats?.length){
                        conditions.category_ids = coupon.condition_cats;
                    }
                }
                // final assign
                coupon.conditions = Object.keys(conditions).length ? conditions : null;

                // ❌ cleanup UI-only fields
                delete coupon.bundle_qty;
                delete coupon.bundle_price;
                delete coupon.buy_qty;
                delete coupon.get_qty;

                delete coupon.enableConditions;
                delete coupon.condition_min_qty;
                delete coupon.condition_products;
                delete coupon.condition_cats;

                // ✅ type cleanup
                if(['bogo','bundle'].includes(coupon.type)){
                    coupon.value = 0;
                }



                // if (coupon.type === 'bogo' && (!coupon.buy_qty || !coupon.get_qty)) {
                //     alert('BOGO requires buy & get qty');
                //     return;
                // }
                //
                // if (coupon.type === 'bundle' && (!coupon.bundle_qty || !coupon.bundle_price)) {
                //     alert('Bundle requires qty & price');
                //     return;
                // }

                console.log('coupon',coupon);

                const res = await axios.post('/sadmin/coupon/save', coupon);

                if (res.data.success) {
                    this.showCouponDialog = false;
                    this.getAllCoupons();
                }

            } catch (e) {
                console.error(e);
            }
        },
        confirmDeleteCoupon(id){
            this.deleteCouponId = id;
            this.deleteCouponDialog = true;
        },
        async deleteCoupon(){
            this.deleteCouponLoading = true;
            try {
                const res = await axios.post('/sadmin/coupon/delete', {
                    coupon_id: this.deleteCouponId
                });

                if (res.data.success) {
                    this.deleteCouponDialog = false;
                    this.deleteCouponId = null;
                    await this.getAllCoupons();
                }

            } catch (e) {
                console.error(e);
            } finally {
                this.deleteCouponLoading = false;
            }
        },
    }
}

</script>

<style scoped>

</style>
