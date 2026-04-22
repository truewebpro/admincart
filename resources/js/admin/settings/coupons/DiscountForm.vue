<template>
    <div>
        <v-row dense>
            <v-col cols="12" md="6">
                <v-text-field variant="outlined" density="compact"
                              v-model="coupon.code" placeholder="Any Code"
                              label="Coupon Code" persistentPlaceholder
                />
            </v-col>
            <v-col cols="12" md="6">
                <!-- Type -->
                <v-select variant="outlined" density="compact"
                          v-model="coupon.type"
                          :items="['fixed','percentage']"
                          label="Coupon Type"
                />
            </v-col>
            <v-col cols="12" md="6">
                <v-number-input
                    variant="outlined"
                    density="compact"
                    v-model="coupon.value"
                    :prefix="coupon.type === 'fixed' ? '£' : ''"
                    :suffix="coupon.type === 'percentage' ? '%' : ''"
                    :label="coupon.type === 'percentage' ? 'Discount (%)' : 'Discount Amount (£)'"
                />
            </v-col>
            <v-col cols="12" md="6">
                <!-- Applies To -->
                <v-select variant="outlined" density="compact"
                          v-model="coupon.applies_to"
                          :items="['entire_order','products','cats']"
                          label="Apply Discount To"
                />
            </v-col>
            <v-col cols="12" md="12" v-if="coupon.applies_to === 'products'">
                <!-- Products -->
                <v-autocomplete density="comfortable" variant="underlined"
                                v-if="coupon.applies_to === 'products'"
                                v-model="coupon.products"
                                :items="products"
                                item-title="title"
                                item-value="product_id"
                                multiple chips closable-chips
                                label="Products" clearable
                />
            </v-col>
            <v-col cols="12" md="12" v-if="coupon.applies_to === 'cats'">
                <!-- Categories -->
                <v-autocomplete density="comfortable" variant="underlined"
                                v-if="coupon.applies_to === 'cats'"
                                v-model="coupon.cats"
                                :items="cats"
                                item-title="cat_name"
                                item-value="cat_id"
                                multiple chips closable-chips
                                label="Categories" clearable
                />
            </v-col>
            <v-col cols="12" md="6">
                <!-- Min order -->
                <v-number-input variant="outlined" density="compact"
                                v-model="coupon.min_order_amount"
                                prefix="£" persistentPlaceholder
                                label="Min Order Amount"
                /></v-col>
            <v-col cols="12" md="6">
                <!-- Limits -->
                <v-number-input variant="outlined" density="compact"
                                v-model="coupon.usage_limit" persistentPlaceholder
                                label="Total Usage Limit"
                />
            </v-col>
            <v-col cols="12" md="6">
                <v-number-input variant="outlined" density="compact"
                                v-model="coupon.per_customer_limit" persistentPlaceholder
                                label="Per Customer Limit"
                />
            </v-col>
            <v-col cols="12" md="6">
                <!-- Priority -->
                <v-number-input variant="outlined" density="compact"
                                v-model="coupon.priority"
                                label="Priority"
                />
            </v-col>
            <v-col cols="12" md="6">
                <!-- Stackable -->
                <v-switch color="success"
                          v-model="coupon.is_stackable"
                          label="Stackable"
                />
            </v-col>

            <v-col cols="12" md="6">
                <!-- Active -->
                <v-switch color="success" v-model="coupon.is_active" label="Active"/>
            </v-col>
            <!-- Dates -->
            <v-col cols="12" md="6">
                <v-date-input variant="underlined" density="compact" persistentPlaceholder
                              v-model="coupon.starts_at" label="Start Date" clearable/>
            </v-col>
            <v-col cols="12" md="6">
                <v-date-input variant="underlined" density="compact" persistentPlaceholder
                              v-model="coupon.expires_at" label="Expiry Date" clearable/>
            </v-col>
            <v-col cols="12" md="6">
                <v-checkbox
                    v-model="coupon.enableConditions" hide-details
                    :disabled="coupon.applies_to === 'entire_order'"
                    label="Activate Coupon When (Condition)"/>
            </v-col>
        </v-row>
        <v-row dense v-if="coupon.enableConditions">
            <v-col cols="12" md="12">
                <v-divider class="my-3"/>
                <h3 class="text-sutitle-2 font-weight-bold">Conditions</h3>
            </v-col>
            <v-col cols="12" md="12">
                <v-alert type="info" variant="tonal" class="mb-2" density="compact">
                    Extra Conditions control when coupon is valid like min quantity or specific products or cats.
                    Discount will still apply only to selected items above.
                </v-alert>
            </v-col>
            <v-col cols="12" md="12">
                <v-number-input variant="outlined" density="compact"
                                v-model="coupon.condition_min_qty" label="Min Cart Qty"/>
            </v-col>
            <v-col cols="12" md="12">
                <v-autocomplete density="comfortable" variant="underlined" persistentPlaceholder
                                v-model="coupon.condition_products"
                                :items="products"
                                item-title="title"
                                item-value="product_id"
                                multiple chips closable-chips
                                label="Required Products in Cart" clearable
                />
            </v-col>
            <v-col cols="12" md="12">
                <v-autocomplete density="comfortable" variant="underlined" persistentPlaceholder
                                v-model="coupon.condition_cats"
                                :items="cats"
                                item-title="cat_name"
                                item-value="cat_id"
                                multiple chips closable-chips
                                label="Required Categories in Cart" clearable/>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { VDateInput} from "vuetify/labs/VDateInput";
export default {
    name: "DiscountForm",
    components:{
        VDateInput
    },
    props: {
        coupon: {
            type: Object,
            required: true
        },
        products: Array,
        cats: Array
    }
}
</script>

<style scoped>

</style>
