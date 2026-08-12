<template>
    <v-container>
        <v-row dense>
            <v-col cols="6" md="6">
                <span class="text-h6">Features List</span></v-col>
            <v-col cols="6" md="6" class="text-end">
                <v-btn class="text-none" variant="tonal" density="compact" color="success" @click="addDialog = true">Add Feature</v-btn>
            </v-col>
            <v-col cols="12" md="12">
                <v-card flat class="border">
                    <v-text-field v-model="fsearch" variant="outlined" density="compact" clearable hide-details
                                  placeholder="Searching all Features" class="ma-2"
                                  prepend-inner-icon="mdi-magnify"
                    ></v-text-field>
                    <div>
                        <v-data-table :items="features" :headers="featuresHeaders" density="comfortable" items-per-page="20"
                                      hover :search="fsearch" :custom-filter="customFilter" :loading="isLoading" mobileBreakpoint="sm">
                            <template v-slot:item.ftitle="{item}">
                                <div class="title d-flex align-center justify-space-between">
                                    <div class="text-decoration-none text-grey-darken-3">
                                        <span>{{item.ftitle}}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:item.fimage="{item}">
                                <v-img v-if="item.fimage != null" :src="cdn+item.fimage" alt="Brand" max-width="40"></v-img>
                                <v-img v-else src="https://dummyimage.com/200x200/000/fff&text=No Image" alt="Feature" max-width="40"></v-img>
                            </template>
                            <template v-slot:item.actions="{item}">
                                <v-btn variant="tonal" color="info" density="compact" @click="editItem(item)">Edit</v-btn>
                                <v-btn variant="tonal" color="red" density="compact" @click="deleteItem(item)" class="ms-1">Delete</v-btn>
                            </template>
                        </v-data-table>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-dialog max-width="600" v-model="editDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="upValid" @submit.prevent="editFeature">
                                <div class="my-2">
                                    <v-text-field v-model="editedItem.ftitle" variant="outlined" density="compact"
                                                  label="Feature Title" :rules="ftitleRule"></v-text-field>
                                </div>
                                <div class="mb-2 d-flex">
                                    <div class="w-25">
                                        <v-img v-if="editedItem.previewImage" :src="editedItem.previewImage"></v-img>
                                    </div>
                                    <div class="w-75">
                                        <v-file-input @change="updatePreviewImage(editedItem.fimage)"
                                                      v-model="editedItem.fimage"
                                                      accept="image/*"
                                                      density="compact"
                                                      :multiple="false"
                                                      title="Feature Image"></v-file-input>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <v-btn type="submit" :loading="upLoading" :disabled="!upValid || upLoading" variant="elevated" density="comfortable" color="success">Update</v-btn>
                                    <v-spacer/>
                                    <v-btn @click="editDialog = false" variant="elevated" density="comfortable" color="red">Cancel</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="400" v-model="deleteDialog">
                    <v-card>
                        <v-card-text>
                                <div class="my-2">
                                    <v-text-field readonly v-model="editedItem.ftitle" variant="outlined" density="compact"
                                                  label="Feature Title" :rules="ftitleRule"></v-text-field>
                                </div>
                                <div class="mb-2 d-flex align-center justify-space-around ga-3">
                                    <v-img v-if="editedItem.previewImage" :src="editedItem.previewImage" max-width="80"></v-img>
                                    <div>
                                        <h3>Are you sure to delete</h3>
                                    </div>
                                </div>
                                <div class="d-flex ga-3">
                                    <v-spacer/>
                                    <v-btn @click="deleteFeature" :loading="upLoading" variant="elevated" density="comfortable" color="success">Yes</v-btn>
                                    <v-btn @click="deleteDialog = false" variant="elevated" density="comfortable" color="red">No</v-btn>
                                </div>
                        </v-card-text>
                    </v-card>
                </v-dialog>
                <v-dialog max-width="600" v-model="addDialog">
                    <v-card>
                        <v-card-text>
                            <v-form v-model="addValid" @submit.prevent="addFeature">
                                <div class="my-2">
                                    <v-text-field
                                        v-model="defaultItem.ftitle"
                                        variant="outlined" density="compact"
                                        label="Feature Title" :rules="ftitleRule">
                                    </v-text-field>
                                </div>
                                <div class="mb-2 d-flex">
                                    <div class="w-50">
                                        <v-file-upload v-model="defaultItem.fimage" density="compact" title="Feature Image"
                                                       clearable icon="mdi-plus" show-size></v-file-upload>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <v-btn type="submit" :loading="addLoading" :disabled="!addValid || addLoading" variant="elevated" density="comfortable" color="success">Add</v-btn>
                                    <v-spacer/>
                                    <v-btn @click="addDialog = false" variant="elevated" density="comfortable" color="red">Cancel</v-btn>
                                </div>
                            </v-form>
                        </v-card-text>
                    </v-card>
                </v-dialog>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import {VFileUpload} from "vuetify/labs/components";
import {mergeProps} from "vue";
import axios from "axios";

export default{
    name:"Features",
    components:{VFileUpload},
    data(){
        return{
            cdn:this.$store.state.cdn,
            shopname:this.$store.state.shop.shop_name || "Shop_name",
            isLoading: false,
            fsearch:'',
            features:[],
            featuresHeaders:[
                {title:'ID',value:'feature_id',width:60},
                {title:'Image',value:'fimage',width:60},
                {title:'Name',key:'ftitle',maxWidth:375},
                {title:'Product Count',key:'pcount'},
                {title:'Actions',value:'actions'},
            ],
            editedIndex:-1,
            defaultItem:{
                ftitle: '',
                fimage: null,
            },
            editedItem:{
                feature_id: '',
                fimage: null,
                previewImage:'',
            },
            upValid:false,
            addValid:false,
            upLoading:false,
            addLoading:false,
            editDialog:false,
            deleteDialog:false,
            addDialog:false,
            ftitleRule:[
                (v) => !!v || "Feature is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicateFeatureName(v) || 'Feature name already exists',
            ],
        }
    },
    mounted() {
        this.getAllFeatures();
    },
    watch:{
        'editedItem.fimage'(newVal){
            this.updatePreviewImage(newVal)
        }
    },
    methods:{
        mergeProps,
        customFilter(value, search, item) {
            if (!search) return true;
            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');
            return searchTerms.every(term => title.includes(term));
        },
        getAllFeatures(){
            this.isLoading = true;
            axios.get('/sadmin/features')
                .then((resp)=>{
                    this.features = resp.data.features;
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        updatePreviewImage(fileList) {
            const file = Array.isArray(fileList) ? fileList[0] : fileList;
            if (file instanceof File) {
                this.editedItem.fimage = file;
                this.editedItem.previewImage = URL.createObjectURL(file);
            } else if (typeof file === 'string' && file !== '') {
                this.editedItem.previewImage = this.cdn + file;
                this.editedItem.fimage = file;
            } else {
                this.editedItem.fimage = null;
                this.editedItem.previewImage = `https://dummyimage.com/1200x630/000/fff&text=${this.shopname}`;
            }
        },
        editFeature(){
            this.upLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const ufeature = {
                feature_id:this.editedItem.feature_id,
                ftitle:this.editedItem.ftitle,
                fimage:this.editedItem.fimage,
            }
            axios.post('/sadmin/feature/update',ufeature,uheaders)
                .then((resp)=>{
                    this.editDialog = false;
                    this.getAllFeatures();
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    this.upLoading = false;
                    window.Toast.success('Feature updated Successfully');
                })
        },
        deleteFeature(){
            this.upLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const dfeature = {
                feature_id:this.editedItem.feature_id,
            }

            axios.post('/sadmin/feature/delete',dfeature,uheaders)
                .then((resp)=>{
                    this.deleteDialog = false;
                    this.getAllFeatures();
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    this.upLoading = false;
                    window.Toast.success('Feature deleted Successfully');
                })
        },
        editItem(item){
            this.editedIndex = this.features.indexOf(item);
            this.editedItem = Object.assign({},item)
            if (item.fimage) {
                this.editedItem.previewImage = this.cdn + item.fimage;
            } else {
                this.editedItem.previewImage = `https://dummyimage.com/200x200/000/fff&text=${encodeURIComponent(this.shopname || 'No Image')}`;
            }
            // this.updatePreviewImage(item.brand_image);
            this.editDialog = true;
        },
        deleteItem(item){
            this.editedIndex = this.features.indexOf(item);
            this.editedItem = Object.assign({},item)
            this.deleteDialog = true;
        },
        addFeature(){
            this.addLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const nfeature = {
                ftitle:this.defaultItem.ftitle,
                fimage:this.defaultItem.fimage,
            }
            axios.post('/sadmin/feature/update',nfeature,uheaders)
                .then((resp)=>{
                    this.addDialog = false;
                    this.getAllFeatures();
                    this.defaultItem = {};
                })
                .catch((error)=>{
                    window.Toast.error(error.message);
                })
                .finally(()=>{
                    this.addLoading = false;
                    window.Toast.success('Feature Added Successfully');
                })
        },
        checkDuplicateFeatureName(name) {
            const nameLower = name.trim().toLowerCase();

            return this.features.some((feature, index) => {
                const isSameName = feature.ftitle.trim().toLowerCase() === nameLower;
                // In case of edit, allow if it's the same index
                if (this.editedIndex !== -1 && index === this.editedIndex) return false;
                return isSameName;
            });
        }

    }
}

</script>

<style scoped>

</style>
