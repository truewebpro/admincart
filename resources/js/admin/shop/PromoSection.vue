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
                            :items="['style1','slide_two_line_shadow','slide_two_line_transparent','promo_banner','order_list','grid_promos_rectangular','grid_promos_square','grid_promos_circular']"
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
                <v-switch v-model="promo.status" color="green" :label="`Status: ${promo.status}`"></v-switch>
                <v-spacer />
                <v-btn color="primary" variant="outlined" @click="updatePromo">
                    Save Promo
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
                <v-card density="compact" :variant="promo?.style === 'slide_two_line_transparent' ? 'text' : 'elevated'">
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
                    <v-card-text v-if="promo.style === 'slide_two_line_shadow'" class="d-flex ga-3 align-center justify-center">
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
                    <v-card-text v-if="promo.style === 'slide_two_line_transparent'" class="d-flex ga-3 align-center justify-center">
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
                    <v-card-text v-else-if="promo.style === 'grid_promos_rectangular'" class="text-center">
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
                    <v-card-text v-else-if="promo.style === 'grid_promos_square'" class="text-center">
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
                    <v-card-text v-else-if="promo.style === 'grid_promos_circular'" class="text-center">
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
                    <v-btn variant="elevated" color="info" size="small" @click="editPromoItem(item)">
                        Edit
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="outlined" size="small" color="error" @click="delPromoItem(item)">
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-col>
        </v-row>
        <v-dialog v-model="addPromoItemDialog" maxWidth="600" zIndex="1021">
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
                    <v-btn @click="addPromoItem" :loading="addLoading" color="green" variant="elevated" density="comfortable">Save</v-btn>
                    <v-btn @click="addPromoItemDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="editPromoItemDialog" maxWidth="600" zIndex="1021">
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
                    <v-btn @click="editPromoItemDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="deletePromoItemDialog" maxWidth="300" zIndex="1021">
            <v-card>
                <v-card-title>Delete Promo Item</v-card-title>
                <v-card-text class="text-center">
                    Are you sure to delete Promo Item<br/>
                    <span class="font-weight-medium">{{editedItem.title}}</span>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="deletePromoItem" :loading="deleteLoading" color="green" variant="elevated" density="comfortable">Delete</v-btn>
                    <v-btn @click="deletePromoItemDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
<script>
export default {
    name: "PromoSection",
    props: {
        page_type: {
            type:String,
            required:true
        },
        position: {
            type: String,
            default: "bottom"
        }
    },
    data(){
        return{
            promo: {
                heading: "",
                subheading: "",
                style: "style1",
                bg_color: "#ffffff",
                title_color: "#000000",
                subtext_color: "#666666",
                status: false,
                items: []
            },

            loading:false,

            addPromoItemDialog:false,
            editPromoItemDialog:false,
            deletePromoItemDialog:false,

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
                media_value: "mdi:truck-fast",
                title: "Free UK Delivery",
                subtext: "Orders over £50"
            },

            editedItem:{},
        }
    },
    mounted() {
        this.getPagePromo();
    },
    // watch: {
    //     page_type: {
    //         immediate: true,
    //         handler() {
    //             this.getPagePromo();
    //         }
    //     }
    // },
    methods:{
        getPagePromo(){
            this.loading = true;
            axios.get(`/sadmin/promo`,{
                params: {
                    page_type: this.page_type,
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
            axios.post(`/sadmin/update-promo`,edata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                })
                .finally(()=>{
                    this.editLoading = false;
                })
        },
        showAddDialog() {
            this.defaultItem = {
                media_type: "icon",
                media_value: "mdi:account",
                title: "Free Delivery",
                subtext: "Orders over £50",
            };
            this.addPromoItemDialog = true;
        },
        async addPromoItem() {
            this.addLoading = true;
            const adata = {
                promo_id: this.promo.id,
                ...this.defaultItem,
            }
            await axios.post(`/sadmin/add-promo-item`,adata)
                .then((resp)=>{
                    this.getPagePromo();
                    this.addPromoItemDialog = false;
                    window.Toast.success(resp.data.message);
                })
                .finally(()=>{
                    this.addLoading = false;
                })

        },
        editPromoItem(item){
            this.editedItem = {...item};
            this.editPromoItemDialog = true;
        },
        delPromoItem(item){
            this.editedItem = {...item};
            this.deletePromoItemDialog = true;
        },
        updatePromoItem() {
            this.editLoading = true;
            axios.post('/sadmin/update-promo-item', this.editedItem)
                .then((resp) => {
                    window.Toast.success(resp.data.message);
                    this.editPromoItemDialog = false;
                    this.getPagePromo();
                })
                .finally(() => {
                    this.editLoading = false;
                });
        },
        deletePromoItem(){
            this.deleteLoading = true;
            const ddata = {
                id: this.editedItem.id
            }
            axios.post(`/sadmin/delete-promo-item`,ddata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.deletePromoItemDialog = false;
                    this.getPagePromo();
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
