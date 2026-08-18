<template>
    <div>
        <v-card class="border-sm">
            <v-card-title>Product Features</v-card-title>
            <v-card v-if="!highs.length">
                <v-empty-state
                    icon="mdi-star"
                    title="No Product Features added yet"
                    text="Highlight what makes this product stand out with icon-based feature callouts."
                >
                    <template #actions>
                        <v-btn @click="addHighDialog = true"
                               color="success"
                               variant="tonal"
                               density="default">Add Highlight</v-btn>
                    </template>
                </v-empty-state>
            </v-card>
            <v-data-table v-if="highs.length" :items="highs" :headers="highHeaders" density="compact" id="hid" items-per-page="20" hide-default-footer>
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
            <v-card-text v-if="highs.length">
                <v-btn @click="addHighDialog = true" variant="tonal" color="success" density="default">Add Highlight</v-btn>
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
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ProductHighlights",
    props: {
        highs: Array,
        features: Array,
        product_id: [String, Number],
        cdn: String,
    },
    data(){
        return{
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
        }
    },
    methods:{
        addHighlight(){
            const hdata = {
                fvalue:this.fvalue,
                position:1,
                feature_id:this.selectedFeature.feature_id,
                product_id:this.product_id,
            }
            axios.post('/sadmin/product/highlight/update',hdata)
                .then((resp)=>{
                    this.$emit('refresh');
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
                    this.$emit('refresh');
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
                    this.$emit('refresh');
                    this.deleteHighDialog = false;
                    window.Toast.success('Highlight Deleted Success')
                })
        },
    }
}
</script>

<style scoped>

</style>
