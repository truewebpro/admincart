<template>
    <div>
        <v-form v-model="mavalid" @submit.prevent="saveMenu">
            <v-container>
                <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
                    <v-col cols="12" md="6">
                        <h2 class="text-h6">
                            <v-btn :to="{name:'MenusList'}" icon variant="tonal" density="compact">
                                <v-icon>mdi-menu</v-icon>
                            </v-btn>
                            Add menu
                        </h2>
                    </v-col>
                    <v-col cols="12" md="6" class="text-end">
                        <v-btn type="submit" :disabled="isFormInvalid" @click="submitForm"
                               density="comfortable" class="text-none"
                               color="green" variant="elevated" prepend-icon="mdi-plus-circle-outline">Save</v-btn>
                    </v-col>
                </v-row>
                <v-row justify="center">
                    <v-col cols="9">
                        <v-card class="border-sm">
                            <v-card-text>
                                <v-text-field v-model="menu_name" label="Name" placeholder="Sidebar menu"
                                              variant="outlined" density="compact" :rules="titleRule"
                                              persistent-placeholder></v-text-field>
                                <div><span class="font-weight-medium me-2">Handle</span>{{menu_slug}}</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="9">
                        <v-card class="border-sm">
                            <v-card-title>Menu Items</v-card-title>
                            <v-card-text>
                                <RecursiveMenu
                                    :items="menuItems"
                                    :level="0"
                                    @update:items="menuItems = $event"
                                    @open-add-dialog="handleOpenAddDialog"
                                />
                                <v-btn
                                    @click="handleOpenAddRootDialog" prepend-icon="mdi-plus-circle-outline"
                                    variant="outlined" color="primary" class="text-none font-weight-bold">
                                    Add menu item
                                </v-btn>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
                <v-row>
                    <v-dialog v-model="addChildOrParent" max-width="600">
                        <v-card>
                            <v-card-actions class="position-fixed bg-white w-100" style="z-index: 1">
                                <v-btn v-if="step === 1" class="font-weight-medium">Search and Menu</v-btn>
                                <v-btn v-if="step === 2" class="font-weight-medium">{{ selectedGroup.type }} ({{selectedGroup.items.length}})</v-btn>
                                <v-spacer/>
                                <v-btn v-if="step === 1" icon="mdi-close" @click="addChildOrParent = false"></v-btn>
                                <v-btn v-if="step === 2" @click="goBack" color="red">Back</v-btn>
                            </v-card-actions>
                            <v-card-text class="mt-6" style="max-height: 400px;min-height: 350px">
                                <div v-if="step === 1">
                                    <v-text-field variant="underlined" v-model="search" hide-details
                                                  placeholder="Search brands, pages, products..."
                                                  @input="fetchGroupedResults" clearable
                                    />
                                    <v-list nav density="compact">
                                        <v-list-item append-icon="mdi-chevron-right" variant="outlined"
                                                     v-for="group in groupedResults" :key="group.type" @click="goToStep2(group)">
                                            <v-list-item-title class="text-capitalize">
                                                {{ group.type }} ({{ group.count }})
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </div>
                                <div v-if="step === 2">
                                    <v-list v-if="selectedGroup" density="compact">
                                        <v-list-item v-for="item in selectedGroup.items" :key="item.id"
                                                     variant="text"
                                                     @click="addMenuNode({item, type: selectedGroup.type, asChild: addAsChild, toParent: selectedParent})">
                                            <v-list-item-title>
                                                {{ item.title || item.brand_name || item.cat_name || item.page_title || item.blog_title || item.policy_name }}
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-dialog>
                </v-row>
            </v-container>
        </v-form>
    </div>
</template>
<script>
import axios from "axios";
import RecursiveMenu from "@/admin/menus/RecursiveMenu.vue";

export default {
    name:"MenuAdd",
    components: {RecursiveMenu},
    data(){
        return {
            addChildOrParent:false,
            addAsChild:false,
            selectedParent: null,
            selectedChild: null,
            mavalid:false,
            isAddLoading:false,
            step: 1,
            search: '',
            groupedResults: [],
            selectedGroup : null,
            customUrl: '',
            menu_name:'',
            menuItems: [],
            menu_status:'active',
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"
            ],
            errors:{},

        }
    },
    watch: {
        menuItems: {
            deep: true,
            handler(newValue) {
                // console.log("menuItems changed:", JSON.parse(JSON.stringify(newValue)));
            }
        }
    },
    computed:{
        menu_slug(){
            return this.menu_name
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "_")
                .replace(/^-+|-+$/g, "");
        },
        formErrors(){
            const errors = {};
            if(!this.mavalid){errors.form = "Label is Missing";}
            if(!this.menuItems.length){errors.form = "Please fix errors in the Menu Items";}
            const validateItems = (items, parentIndex = '') => {
                items.forEach((item,idx) => {
                    const indexKey = parentIndex === '' ? `${idx}` : `${parentIndex}_${idx}`;
                    if (!item.label) {
                        errors[`label_${indexKey}`] = "Label is required";
                    }
                    if (!item.url) {
                        errors[`url_${indexKey}`] = "URL is required";
                    }
                    if (item.isEdit === true) {
                        errors[`edit_${indexKey}`] = "Confirm the Menu Name";
                    }

                    if (item.children && item.children.length > 0) {
                        validateItems(item.children, indexKey);
                    }
                });
            };
            validateItems(this.menuItems);
            return errors;
        },
        isFormInvalid() {
            return Object.keys(this.formErrors).length > 0;
        },
    },
    methods:{
        handleOpenAddDialog(parentItem) {
            this.addChildOrParent = true;
            this.selectedParent = parentItem;
            this.selectedLevel = parentItem.level || 0;
            this.addAsChild = !!parentItem;
            this.search = '';
            this.groupedResults = [];
        },
        handleOpenAddRootDialog() {
            this.addChildOrParent = true;
            this.selectedParent = null;
            this.addAsChild = false; // for root-level add
            this.search = '';
            this.groupedResults = [];
        },
        findNodeById(items, id) {
            for (const item of items) {
                if (item.id === id) return item;
                if (item.children) {
                    const found = this.findNodeById(item.children, id);
                    if (found) return found;
                }
            }
            return null;
        },
        addMenuNode({ item, type, asChild, toParent }){
            const newNode = {
                id:Date.now(),
                label: item.label || item.title || item.brand_name || item.cat_name || item.page_title || item.blog_title || item.policy_name,
                url: item.url || item.handle || item.brand_slug || item.cat_slug || item.page_slug || item.blog_slug || item.policy_slug,
                type,
                children: [],
                parent_id:null,
            };
            if(asChild && toParent){
                const reactiveToParent = this.findNodeById(this.menuItems, toParent.id);
                if (reactiveToParent) {
                    newNode.parent_id = toParent.id;
                    if (!reactiveToParent.children) reactiveToParent.children = [];
                    reactiveToParent.children.push(newNode);
                    this.menuItems = [...this.menuItems]; // trigger reactivity
                }
            } else {
                this.menuItems = [...this.menuItems, newNode];
            }
            this.addChildOrParent = false;
        },
        submitForm() {
            if (this.isFormInvalid) {
                const firstError = Object.values(this.formErrors)[0];
                window.Toast.error(firstError);
                return;
            }
        },
        async searchAll(query){
            const res = await axios.get(`/sadmin/search-all?q=${encodeURIComponent(query)}`);
            return await res.data;
        },
        async fetchGroupedResults(){
            if(!this.search.trim()){
                this.groupedResults = [];
                return
            }
            const data = await this.searchAll(this.search);
            this.groupedResults = Object.entries(data)
                .filter(([key,group])=> group.count > 0)
                .map(([key,group])=>({
                    type:key,
                    count:group.count,
                    items:group.items
                }));
        },
        goToStep2(group){
            this.selectedGroup = group;
            this.customUrl = !group ? this.search : '';
            this.step = 2;
        },
        goBack(){
            this.step = 1;
            this.handleOpenAddDialog();
        },
        saveMenu(){
            this.isAddLoading = true
            const mmenu = {
                'menu_name':this.menu_name,
                'menu_slug':this.menu_slug,
                'menu_status':this.menu_status,
                'menu_items':this.menuItems,
            }
            const transformed = {
                menu_name: mmenu.menu_name,
                menu_slug: mmenu.menu_slug,
                menu_status: mmenu.menu_status,
                menu_items: mmenu.menu_items.map((item, index) => ({
                    id: item.id,
                    label: item.label,
                    type: item.type,
                    url: item.url,
                    children:item.children
                }))
            };
            axios.post('/sadmin/menu/add',transformed)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.isAddLoading = false;
                    this.$router.push({name:'MenusList'});
                })
        }
    }
}

</script>

<style scoped>

</style>
