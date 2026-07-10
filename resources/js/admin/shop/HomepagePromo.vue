<template>
    <v-container>
        <v-card class="mb-5">
            <v-card-title><span class="text-capitalize">{{ position }}</span> Promo</v-card-title>
            <v-card-text>
                <v-row dense>
                    <v-col cols="12" md="6">
                        <v-text-field variant="outlined" density="compact"
                            label="Heading" persistentPlaceholder
                            v-model="promo.heading"
                        />
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-text-field variant="outlined" density="compact"
                            label="Sub Heading" persistentPlaceholder
                            v-model="promo.subheading"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select variant="underlined"
                            label="Style"
                            :items="['style1','style1a','style1b','style2','style3']"
                            v-model="promo.style"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field variant="plain"
                            type="color"
                            label="Background"
                            v-model="promo.bg_color"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field variant="plain"
                            type="color"
                            label="Title"
                            v-model="promo.title_color"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field variant="plain"
                            type="color"
                            label="Subtext"
                            v-model="promo.subtext_color"
                        />
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="primary" variant="outlined" @click="updatePromo">
                    Save Settings
                </v-btn>
            </v-card-actions>
        </v-card>
        <v-row dense class="mb-3">
            <v-col>
                <h3>Promo Items</h3>
            </v-col>
            <v-col cols="auto">
                <v-btn
                    color="success"
                    prepend-icon="mdi-plus"
                    @click="showAddDialog"
                >
                    Add Item
                </v-btn>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col v-for="item in promo.items" :key="item.id" cols="12" md="3">
                <v-card density="compact" :variant="promo?.style === 'style1b' ? 'text' : 'elevated'">
                    <v-card-text v-if="promo.style === 'style1'" class="d-flex ga-1 align-center pa-1 justify-center">
                        <i v-if="item.media_type==='icon'" class="iconify"
                            :data-icon="item.media_value" style="font-size: 20px"
                        />
                        <div v-else-if="item.media_type==='svg'" v-html="item.media_value"/>
                        <v-img v-else :src="item.media_value" height="20" contain/>
                        <div class="text-body-1">
                            {{ item.title }}
                        </div>
                        <div class="text-body-2">
                            {{ item.subtext }}
                        </div>
                    </v-card-text>
                    <v-card-text v-if="promo.style === 'style1a'" class="d-flex ga-3 align-center justify-center">
                        <i v-if="item.media_type==='icon'" class="iconify"
                           :data-icon="item.media_value" style="font-size: 32px"
                        />
                        <div v-else-if="item.media_type==='svg'" v-html="item.media_value"/>
                        <v-img v-else :src="item.media_value" height="32" contain/>
                        <div>
                            <div class="text-body-1 font-weight-semibold">
                                {{ item.title }}
                            </div>
                            <div class="text-body-2">
                                {{ item.subtext }}
                            </div>
                        </div>

                    </v-card-text>
                    <v-card-text v-if="promo.style === 'style1b'" class="d-flex ga-3 align-center justify-center">
                        <i v-if="item.media_type==='icon'" class="iconify"
                           :data-icon="item.media_value" style="font-size: 32px"
                        />
                        <div v-else-if="item.media_type==='svg'" v-html="item.media_value"/>
                        <v-img v-else :src="item.media_value" height="32" contain/>
                        <div>
                            <div class="text-body-1 font-weight-semibold">
                                {{ item.title }}
                            </div>
                            <div class="text-body-2">
                                {{ item.subtext }}
                            </div>
                        </div>

                    </v-card-text>
                    <v-card-text v-else-if="promo.style === 'style2'" class="text-center">
                        <i v-if="item.media_type==='icon'" class="iconify"
                           :data-icon="item.media_value" style="font-size: 36px"
                        />
                        <div v-else-if="item.media_type==='svg'" v-html="item.media_value"/>
                        <v-img v-else :src="item.media_value" height="60" contain/>

                        <div class="text-h6 mt-1">
                            {{ item.title }}
                        </div>
                        <div class="text-body-2">
                            {{ item.subtext }}
                        </div>
                    </v-card-text>
                    <v-card-text v-else-if="promo.style === 'style3'" class="text-center">
                    <i v-if="item.media_type==='icon'" class="iconify"
                       :data-icon="item.media_value" style="font-size: 36px"
                    />
                    <div v-else-if="item.media_type==='svg'" v-html="item.media_value"/>
                    <v-img v-else :src="item.media_value" height="60" contain/>

                    <div class="text-h6 mt-1">
                        {{ item.title }}
                    </div>
                    <div class="text-body-2">
                        {{ item.subtext }}
                    </div>
                    </v-card-text>
                </v-card>
                <v-card-actions>
                    <v-btn variant="elevated" color="info" size="small" @click="editPromo(item)">
                        Edit
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="outlined" size="small" color="error" @click="delPromo(item)">
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-col>
        </v-row>
        <v-dialog v-model="addPromoDialog" maxWidth="600" zIndex="1021">
            <v-card>
                <v-card-title>Add New Promo Item</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Default Title"
                                  v-model="defaultItem.title" label="Title"></v-text-field>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Default Title"
                                  v-model="defaultItem.subtext" label="Subtext"></v-text-field>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="mdi:truck-fast"
                                  v-model="defaultItem.media_value" label="Icon"></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="addPromo" :loading="addLoading" color="green" variant="elevated" density="comfortable">Save</v-btn>
                    <v-btn @click="addPromoDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="editPromoDialog" maxWidth="600" zIndex="1021">
            <v-card>
                <v-card-title>Edit Promo Item</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Default Title"
                                  v-model="editedItem.title" label="Title"></v-text-field>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Default Title"
                                  v-model="editedItem.subtext" label="Subtext"></v-text-field>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="mdi:truck-fast"
                                  v-model="editedItem.media_value" label="Icon"></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="updatePromoItem" :loading="editLoading" color="green" variant="elevated" density="comfortable">Save</v-btn>
                    <v-btn @click="editPromoDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="deletePromoDialog" maxWidth="300" zIndex="1021">
            <v-card>
                <v-card-title>Delete Promo Item</v-card-title>
                <v-card-text class="text-center">
                    Are you sure to delete Promo Item<br/>
                    <span class="font-weight-medium">{{editedItem.title}}</span>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="deletePromo" :loading="deleteLoading" color="green" variant="elevated" density="comfortable">Delete</v-btn>
                    <v-btn @click="deletePromoDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script>
export default {
    name: "HomepagePromo",
    props: {
        homepage_id: [String, Number],
        position: {
            type: String,
            default: "top"
        }
    },
    data(){
        return{
            promo: {
                items: []
            },

            loading:false,

            addPromoDialog:false,
            editPromoDialog:false,
            deletePromoDialog:false,

            addLoading:false,
            editLoading:false,
            deleteLoading:false,

            status:true,
            heading:"",
            subheading:"",
            style:"style1",
            bg_color:"#ffffff",
            title_color:"#000000",
            subtext_color:"#666666",

            defaultItem: {
                media_type: "icon",
                media_value: "mdi:account",
                title: "Free Delivery",
                subtext: "Orders over £50"
            },

            editedItem:{},
        }
    },
    mounted() {
        this.getHomePagePromo();
    },
    // watch: {
    //     homepage_id: {
    //         immediate: true,
    //         handler() {
    //             this.getHomePagePromo();
    //         }
    //     }
    // },
    methods:{
        getHomePagePromo(){
            this.loading = true;
            axios.get(`/sadmin/homepage/promo`,{
                params: {
                    homepage_id: this.homepage_id,
                    position: this.position
                }
            })
                .then((resp)=>{
                    const respData = resp.data;
                    this.promo = respData.promo;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        showAddDialog() {
            this.defaultItem = {
                media_type: "icon",
                media_value: "mdi:account",
                title: "Free Delivery",
                subtext: "Orders over £50",
            };
            this.addPromoDialog = true;
        },
        async addPromo() {
            this.addLoading = true;
            const adata = {
                home_promo_id: this.promo.id,
                ...this.defaultItem,
            }
            await axios.post(`/sadmin/homepage/add-promo-item`,adata)
                .then((resp)=>{
                    this.getHomePagePromo();
                    this.addPromoDialog = false;
                    window.Toast.success(resp.data.message);
                })
                .finally(()=>{
                    this.addLoading = false;
                })

        },
        editPromo(item){
            this.editedItem = {...item};
            this.editPromoDialog = true;
        },
        delPromo(item){
            this.editedItem = {...item};
            this.deletePromoDialog = true;
        },
        updatePromoItem() {
            this.editLoading = true;
            axios.post('/sadmin/homepage/update-promo-item', this.editedItem)
                .then((resp) => {
                    window.Toast.success(resp.data.message);
                    this.editPromoDialog = false;
                    this.getHomePagePromo();
                })
                .finally(() => {
                    this.editLoading = false;
                });
        },
        updatePromo(){
            this.editLoading = true;
            const edata = {
                id: this.promo.id,
                heading: this.promo.heading,
                subheading: this.promo.subheading,
                style: this.promo.style,
                bg_color: this.promo.bg_color,
                title_color: this.promo.title_color,
                subtext_color: this.promo.subtext_color,
                status: this.promo.status
            }
            axios.post(`/sadmin/homepage/update-promo`,edata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                })
                .finally(()=>{
                    this.editLoading = false;
                })
        },
        deletePromo(){
            this.deleteLoading = true;
            const ddata = {
                id: this.editedItem.id
            }
            axios.post(`/sadmin/homepage/delete-promo-item`,ddata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.deletePromoDialog = false;
                    this.getHomePagePromo();
                })
                .finally(()=>{
                    this.deleteLoading = false;
                })
        }
    }

}
</script>

<style scoped>

</style>
