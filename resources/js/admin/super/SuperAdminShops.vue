<template>
    <v-container class="pa-1">
        <v-row dense align="center">
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-item>
                        <template #title><div class="text-h5 font-weight-bold">Shops List</div></template>
                        <template #append>
                            <v-btn color="primary" density="comfortable" @click="openCreate">Add New Shop</v-btn>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col cols="12" md="12">
                <v-card>
                    <v-text-field density="compact" variant="outlined" class="pa-2" hide-details
                                  placeholder="Search Shop..." persistentPlaceholder label="Search Shop"></v-text-field>
                    <v-data-table :items="allshops" :search="shopsearch" :headers="allshopsHeader" hover mobileBreakpoint="sm"
                                  itemsPerPage="50" :hideDefaultFooter="allshops?.length < 50">
                        <template #item.shop_name="{item}">
                            <div class="d-flex flex-column ga-1 py-1">
                                <div>
                                    <div v-if="item?.maindomain">
                                        <div class="font-weight-bold text-primary">{{item.maindomain}}</div>
                                    </div>
                                    <div v-else>
                                        <div class="font-weight-medium text-info">{{item.subdomain}}</div>
                                    </div>
                                </div>
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
                        <template #item.users="{item}">
                            <div v-if="item?.users?.length">
                                <div v-for="(user) in item.users" :key="user.id" class="border-b-sm my-1">
                                    <div class="mb-1 d-flex ga-1">
                                        <div class="d-flex flex-column align-start">
                                            <span class="font-weight-medium">{{user.name}}</span>
                                            <span>{{user.email}}</span>
                                        </div>
                                        <v-chip size="small" density="compact" class="font-weight-medium" color="success">{{ user?.pivot?.role }}</v-chip>
                                    </div>
                                </div>
                            </div>

                           <v-btn @click="openAddDialog(item)" density="compact" color="success" variant="tonal" size="small"
                                  prependIcon="mdi-account-plus" class="mb-1">
                               add User
                           </v-btn>
                        </template>
                        <template #item.mailtrap_list="{item}">
                            <div v-if="item?.mailtrap_list">
                                <div class="font-weight-medium">ID: {{item?.mailtrap_list?.list_id}}</div>
                                <div>{{item?.mailtrap_list?.list_name}}</div>
                            </div>
                            <div v-else class="text-grey">
                                Mailtrap not Active
                                <v-btn @click="createMailTrapList(item)"
                                       :loading="mailLoading"
                                       density="comfortable" variant="outlined"
                                       color="success" prependIcon="mdi-email-sync-outline">
                                    Add Mailtrap
                                </v-btn>
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
        <v-dialog v-model="addUserDialog" maxWidth="400">
            <v-card>
                <v-form v-model="addValid" @submit.prevent="addNewUser">
                    <v-card-title>Add User {{selectedShop.shop_name}}</v-card-title>
                    <v-card-text>
                        <v-text-field v-model="addForm.name" :rules="nameRule" label="Name" variant="underlined" density="comfortable"/>
                        <v-text-field v-model="addForm.email" :rules="emailRule" label="Email" variant="underlined" density="comfortable"/>
                        <v-select v-model="addForm.role" :items="roles" :rules="[rules.required]" label="Role" variant="underlined" density="comfortable"/>
                        <v-text-field v-model="addForm.password" :rules="passwordRule" label="Password" variant="underlined" density="comfortable"/>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn @click="addUserDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                        <v-btn type="submit" :disabled="!addValid" color="success" variant="elevated" density="comfortable">Create</v-btn>
                    </v-card-actions>
                </v-form>
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
            addValid: false,
            addUserDialog: false,
            mailLoading: false,
            selectedShop: null,
            allshopsHeader:[
                {title:'Shop',key:'shop_name'},
                {title:'Users / Role',key:'users'},
                {title:'Mailtrap',key:'mailtrap_list'},
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
            },
            nameRule: [
                v => !!v || 'Name is Required',
                v => (v && v.length >= 3) || 'Name must be at least 3 characters'
            ],
            emailRule: [
                v => !!v || 'Email is Required',
                v => /.+@.+\..+/.test(v) || 'Email must be valid'
            ],
            passwordRule: [
                v => !!v || 'Password is Required',
                v => (v && v.length >= 5) || 'Password must be at least 5 characters'
            ],
            roles:['owner','staff','developer'],
            addForm: {
                email:"",
                name:"",
                role:"staff",
                password:"",
            }
        }
    },
    computed:{
        allshops() {
            return this.$store.state.allshops;
        },
        mlists(){
            return this.$store.state.mlists;
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
        this.fetchShops();
        this.fetchmLists();
    },
    methods:{
        fetchShops() {
            this.$store.dispatch('fetchAllShops')
        },
        fetchmLists(){
            this.$store.dispatch('fetchMailtrapList')
        },
        async createMailTrapList(shop){
            this.mailLoading = true;
            const cdata = {
                shop_id:shop.shop_id,
                name:shop.shop_slug,
            }

            try {
                const resp = await axios.post('/superadmin/mailtrap/contacts/list',cdata);
                window.Toast.success('Mailtrap active')
                this.fetchShops();
                this.fetchmLists();
            } catch (e) {
                console.log('error:',e)
            } finally {
                this.mailLoading = false;
            }
            console.log('cdata',cdata)
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
        },
        openAddDialog(item){
            this.selectedShop = item;
            this.addUserDialog = true;
        },
        addNewUser(){
            const adata = {
                shop_id:this.selectedShop.shop_id,
                name:this.addForm.name,
                email:this.addForm.email,
                role:this.addForm.role,
                password:this.addForm.password,
            }
            axios.post('/superadmin/shops/assign-user',adata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.addUserDialog = false;
                    this.addForm.name = "";
                    this.addForm.email = "";
                    this.addForm.role = "";
                    this.addForm.password = "";
                    this.fetchShops();
                })
        },
        async syncCustomers(){
            try {
                const resp = await axios.get('/superadmin/sync-mailtrap-customers');
                window.Toast.success(`${resp.data.synced} synced`);
            } catch (e) {
                window.Toast.error('Sync failed');
            }
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
