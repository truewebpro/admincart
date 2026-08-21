<template>
    <div>
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-title>
                        Product Specifics
                    </v-card-title>
                    <v-card v-if="!specifics.length">
                        <v-empty-state
                            icon="mdi-format-list-text"
                            title="No Product Specifics added yet"
                            text="List technical details like battery, capacity, or dimensions as attribute rows."
                        >
                            <template #actions>
                                <v-btn @click="addSpecDialog = true"
                                       color="success"
                                       variant="tonal" prependIcon="mdi-plus"
                                       density="default">Add Specifics</v-btn>
                            </template>
                        </v-empty-state>
                    </v-card>
                    <v-card-text v-if="specifics.length" >
                        <v-btn density="default" variant="tonal" color="success" prepend-icon="mdi-plus" @click="addSpecDialog = true">Add Specifics</v-btn>
                    </v-card-text>
                    <v-dialog v-model="addSpecDialog" max-width="400">
                        <v-card>
                            <v-card-text>
                                <v-text-field v-model="defaultSpec.stitle" label="Specific Title"
                                              density="comfortable" variant="underlined" class="mb-1"
                                              counter="20" persistent-counter/>
                                <v-textarea v-model="defaultSpec.svalue" label="Specific Value" density="comfortable"
                                            variant="underlined" counter="200" persistent-counter/>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer/>
                                <v-btn @click="addSpecific" density="comfortable" color="success" variant="elevated">Add</v-btn>
                                <v-btn density="comfortable" color="red" @click="addSpecDialog = false">Cancel</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                    <v-dialog v-model="editSpecDialog" max-width="400">
                        <v-card>
                            <v-card-text>
                                <v-text-field v-model="editedSpec.stitle" label="Specific Title"
                                              density="comfortable" variant="underlined" class="mb-1"/>
                                <v-textarea v-model="editedSpec.svalue" label="Specific Value" density="comfortable" variant="underlined"/>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer/>
                                <v-btn @click="editSpecific" density="comfortable" color="success" variant="elevated">Update</v-btn>
                                <v-btn density="comfortable" color="red" @click="editSpecDialog = false">Cancel</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                    <v-dialog v-model="deleteSpecDialog" max-width="400">
                        <v-card>
                            <v-card-text class="text-center">
                                Are you sure to delete this Product Specific
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer/>
                                <v-btn @click="deleteSpecific" density="comfortable" color="success" variant="elevated">Yes</v-btn>
                                <v-btn density="comfortable" color="red" @click="deleteSpecDialog = false">No</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-card>
            </v-col>
        </v-row>
        <v-row v-if="specifics.length" dense>
            <v-col v-for="(spec,sdx) in specifics" :key="sdx" cols="6" md="3">
                <v-card class="mb-2">
                    <v-card-item>
                        <template #prepend>
                            <v-icon>mdi-chevron-triple-right</v-icon>
                        </template>
                        <template #append>
                            <div class="d-flex flex-column ga-2">
                                <v-icon color="info" @click="editSpec(spec)">mdi-pencil</v-icon>
                                <v-icon color="red" @click="deleteSpec(spec)">mdi-delete</v-icon>
                            </div>
                        </template>
                        <template #title>
                            <div class="text-h6 font-weight-bold">{{ spec.stitle }}</div>
                            <div class="text-body-1">{{ spec.svalue }}</div>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>
<script>
import axios from "axios";

export default {
    name: "ProductSpecifics",
    props: {
        specifics: Array,
        product_id: [String, Number],
        cdn: String,
    },
    data(){
        return{
            addSpecDialog:false,
            editSpecDialog:false,
            deleteSpecDialog:false,
            defaultSpec:{
                stitle:'',
                svalue:''
            },
            editedSpecIndex:-1,
            editedSpec:{
                specific_id:'',
                stitle:'',
                svalue:'',
                shop_id:''
            },
            deletedSpec:{
                specific_id:'',
            },
        }
    },
    methods:{
        addSpecific(){
            const asdata = {
                stitle:this.defaultSpec.stitle,
                svalue:this.defaultSpec.svalue,
                product_id:this.product_id,
            }
            axios.post('/sadmin/specific/update',asdata)
                .then((resp)=>{
                    this.addSpecDialog = false;
                    window.Toast.success(resp.data.message);
                    this.$emit('refresh');
                    this.defaultSpec.stitle = "";
                    this.defaultSpec.svalue = "";
                })
        },
        editSpec(spec){
            this.editedSpecIndex = this.specifics.indexOf(spec);
            this.editedSpec = Object.assign({},spec);
            this.editSpecDialog = true;
        },
        editSpecific(){
            const esdata = {
                specific_id:this.editedSpec.specific_id,
                stitle:this.editedSpec.stitle,
                svalue:this.editedSpec.svalue,
                product_id:this.product_id,
                shop_id:this.editedSpec.shop_id,
            }
            axios.post('/sadmin/specific/update',esdata)
                .then((resp)=>{
                    this.editSpecDialog = false;
                    window.Toast.success(resp.data.message);
                    this.$emit('refresh');
                })
        },
        deleteSpec(spec){
            this.editedSpecIndex = this.specifics.indexOf(spec);
            this.deletedSpec = Object.assign({},spec);
            this.deleteSpecDialog = true;
        },
        deleteSpecific(){
            const dsdata = {
                specific_id:this.deletedSpec.specific_id,
            }
            axios.post('/sadmin/specific/delete',dsdata)
                .then((resp)=>{
                    this.deleteSpecDialog = false;
                    window.Toast.success(resp.data.message);
                    this.$emit('refresh');
                })
        },
    }
}
</script>

<style scoped>

</style>
