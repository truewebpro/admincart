<template>
    <v-container>
        <v-row dense align="center">
            <v-col cols="12" md="6">
                <h2>Shops List</h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn color="primary" density="comfortable" @click="openCreate">Add New Shop</v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-text-field density="compact" variant="outlined" class="pa-2" hide-details
                                  placeholder="Search Shop..." persistentPlaceholder label="Search Shop"></v-text-field>
                    <v-data-table :items="allshops" :search="shopsearch" :headers="allshopsHeader"
                                  itemsPerPage="50" :hideDefaultFooter="allshops?.length < 50">
                        <template #item.shop_name="{item}">
                            <div class="d-flex flex-column ga-1 py-1">
                                <div>
                                    Name: <span class="font-weight-medium">{{item.shop_name}}</span>
                                </div>
                                <div>
                                    Slug: <span class="font-weight-medium">{{item.shop_slug}}</span>
                                </div>
                                <div>
                                    Order Prefix: <span class="font-weight-medium">{{item.order_prefix}}</span>
                                </div>

                            </div>
                        </template>
                        <template #item.subdomain="{item}">
                            <div>
                                <div v-if="item?.maindomain">
                                    <v-btn class="text-none" variant="text" color="success" density="compact">{{item.maindomain}}</v-btn>
                                </div>
                                <div v-else>
                                    <v-btn class="text-none" variant="text" color="success" density="compact">{{item.subdomain}}</v-btn>
                                </div>
                            </div>
                        </template>
                        <template #item.shop_status="{item}">
                            {{item.shop_status}}
                            <v-switch
                                title="Change Status"
                                density="compact"
                                :color="'success'"
                                :base-color="'red'"
                                :model-value="item.shop_status === 'Active'"
                                @update:modelValue="(val) => toggleStatus(item, val)"
                            />
                        </template>
                        <template #item.stripe_id="{item}">
                            <div>
                                <div>{{item.plan_slug}}</div>
                                <div v-if="item?.stripe_id">
                                    Stripe Customer Id: {{item.stripe_id}}
                                    <v-chip v-if="item?.plan_slug" color="green" variant="tonal" density="compact" class="font-weight-medium">
                                        {{ item.plan_slug }}
                                    </v-chip>
                                    <v-chip v-else color="green" variant="tonal" density="compact" class="font-weight-medium">
                                        Not Subscribed yet
                                    </v-chip>
                                </div>
                                <div v-else>
                                    <v-chip color="red" variant="outlined" density="compact" class="font-weight-medium">No Subscription</v-chip>
                                </div>
                            </div>
                        </template>
                        <template #item.actions="{item}">
                            <div class="d-flex ga-2 align-center">
                                <v-btn v-if="item.shop_id === $store.state.shop_id" color="success"
                                       variant="outlined" density="compact" appendIcon="mdi-check-circle">
                                   Selected
                                </v-btn>
                                <v-btn v-else :loading="this.$store.state.switchingShop" color="success"
                                       variant="elevated" density="compact" appendIcon="mdi-chevron-right"
                                       @click="switchShop(item.shop_id)">Open</v-btn>
                                <v-btn icon color="info" density="comfortable" variant="outlined" @click="editShop(item)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="shopDialog" max-width="500">
            <v-card>
                <v-card-title>
                    {{ isEdit ? 'Edit Shop' : 'Create Shop' }}
                </v-card-title>

                <v-card-text>
                    <v-text-field variant="underlined" density="comfortable"
                        v-model="shopForm.shop_name"
                        :readonly="isEdit"
                        label="Shop Name"
                        :rules="[rules.required]"
                        :error-messages="errors.shop_name"
                    />

                    <v-text-field variant="underlined" density="comfortable"
                        v-model="shopForm.shop_slug"
                        label="Shop Slug"
                        :readonly="isEdit"
                        :rules="[rules.required, rules.slug]"
                        :error="slugAvailable === false || !!errors.shop_slug"
                        :error-messages="slugAvailable === false ? ['Slug already taken'] : errors.shop_slug"
                        :loading="slugChecking"
                        @input="onSlugInput"
                        :hint="!slugTouched ? 'Auto-generated from name' : 'Custom slug'"
                        persistent-hint
                    />

                    <v-text-field variant="underlined" density="comfortable"
                        :model-value="shopForm.shop_slug + '.truewebcart.com'"
                        label="Subdomain"
                        readonly
                    />

                    <!-- Only in edit -->
                    <v-text-field variant="underlined" density="comfortable"
                        v-if="isEdit"
                        v-model="shopForm.maindomain"
                        label="Custom Domain"
                        :error-messages="errors.maindomain"
                    />
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn @click="shopDialog = false" variant="outlined" color="red" density="comfortable">Cancel</v-btn>
                    <v-btn color="success" density="comfortable"
                           variant="elevated"
                           :disabled="!canSubmit"
                           @click="saveShop">
                        {{ isEdit ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script>
import axios from "axios";

export default {
    name: "SuperAdminShops",
    data(){
        return {
            shopsearch:"",
            shopDialog: false,
            selectedShop: null,
            allshopsHeader:[
                {title:'Shop',key:'shop_name'},
                {title:'Domain',key:'subdomain'},
                {title:'Status',key:'shop_status'},
                {title:'Plan',key:'stripe_id'},
                {title:'Actions',key:'actions'},
            ],
            shopForm: {
                shop_name: '',
                shop_slug: '',
                maindomain: ''
            },
            errors: {},
            slugAvailable: null,
            slugTouched: false,
            slugChecking: false,
            debounceTimer: null,
            rules: {
                required: v => !!v || 'Required',
                slug: v => /^[a-z0-9-]+$/.test(v) || 'Only lowercase, numbers, hyphen'
            }
        }
    },
    computed:{
        allshops() {
            return this.$store.state.allshops
        },
        isEdit() {
            return !!this.selectedShop
        },
        canSubmit() {
            if (!this.shopForm.shop_name || !this.shopForm.shop_slug) return false;

            if (!this.isEdit && this.slugAvailable === false) return false;

            return true
        }
    },
    mounted() {
        this.fetchShops()
    },
    methods:{
        fetchShops() {
            this.$store.dispatch('fetchAllShops')
        },
        openCreate() {
            this.selectedShop = null
            this.shopForm = {
                shop_name: '',
                shop_slug: '',
                maindomain: ''
            }
            this.errors = {}
            this.slugAvailable = null
            this.slugChecking = false
            this.slugTouched = false

            this.shopDialog = true
        },
        editShop(shop) {
            this.selectedShop = shop
            this.shopForm = {
                shop_name: shop.shop_name,
                shop_slug: shop.shop_slug,
                maindomain: shop.maindomain || '',
            }
            this.errors = {}
            this.slugAvailable = true // already valid
            this.slugTouched = true

            this.shopDialog = true
        },
        async switchShop(shopId) {
            await this.$store.dispatch('switchShop', shopId)
        },
        async toggleStatus(item,val) {
            const newStatus = val ? 'Active' : 'Inactive'
            await axios.post(`/superadmin/shops/${item.shop_id}/status`,{
                shop_status: newStatus
            })
            item.shop_status = newStatus;
            this.fetchShops()
        },
        async saveShop() {
            this.errors = {}

            try {
                if (this.isEdit) {
                    await axios.put(`/superadmin/shop/update/${this.selectedShop.shop_id}`, this.shopForm)
                } else {
                    await axios.post('/superadmin/shop/add', this.shopForm)
                }

                this.shopDialog = false
                this.fetchShops()

            } catch (e) {
                if (e.response?.status === 422) {
                    this.errors = e.response.data.errors
                }
            }
        },
        async checkSlug(slug) {
            this.slugChecking = true
            try {
                const res = await axios.get('/superadmin/shops/check-slug', {
                    params: { slug }
                })

                console.log('Slug check response:', res.data) // 👈 debug

                this.slugAvailable = res.data.available

            } catch (e) {
                console.error(e)
                this.slugAvailable = false
            }

            this.slugChecking = false
        },
        onSlugInput(val) {
            this.slugAvailable = null

            if (!val || this.isEdit) return

            clearTimeout(this.debounceTimer)

            this.debounceTimer = setTimeout(() => {
                this.checkSlug(val)
            }, 500)
        }
    },
    watch: {
        'shopForm.shop_name'(val) {
            if (!this.isEdit && !this.slugTouched) {
                this.shopForm.shop_slug = val
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
            }
        },
    }
}
</script>

<style scoped>

</style>
