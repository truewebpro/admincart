<template>
    <v-row class="ptiers" align="stretch">
        <v-col cols="12" md="9">
            <v-card class="border-sm h-100">
                <v-card-title>Tier Pricing</v-card-title>
                <v-card-subtitle>Base Price: £{{ price }}</v-card-subtitle>
                <v-card-text>
                    <div v-if="tiers.length === 0" class="text-grey">
                        No tier pricing applied. Product uses base price.
                        <v-empty-state
                            icon="mdi-tag-text-outline"
                            title="No Product Tiers added yet"
                            text="Answer the questions customers ask most before they reach out to support"
                        >
                            <template #actions>
                                <div class="d-flex ga-2">
                                    <v-btn @click="addTier" :disabled="tiers.some(t => !t.price || t.min_qty < 2)"
                                           prependIcon="mdi-plus"
                                           color="primary" density="compact" :class="tiers.length > 2 ? 'd-none' : ''">Add Tier</v-btn>
                                    <v-btn type="submit" :disabled="!tierForm" color="success" density="compact">Save Tier</v-btn>
                                </div>
                            </template>
                        </v-empty-state>
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
                        <div v-if="tiers.length" class="d-flex ga-2">
                            <v-btn @click="addTier" :disabled="tiers.some(t => !t.price || t.min_qty < 2)"
                                   prependIcon="mdi-plus"
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
</template>
<script>
import axios from "axios";

export default {
    name: "ProductTiers",
    props: {
        tiers: Array,
        product_id: [String, Number],
        price: [String,Number],
        cdn: String,
    },
    data(){
        return{
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
                    this.$emit('refresh');
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
                    this.$emit('refresh');
                    window.Toast.success(resp.data.message);
                })
                .catch((err) => {
                    window.Toast.error(err.message);
                })
        },
    }
}
</script>

<style scoped>

</style>
