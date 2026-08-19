<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-item>
                        <template #prepend>
                            <v-icon>mdi-star</v-icon>
                        </template>
                        <template #title>
                            <div class="text-h5 font-weight-bold">Reviews</div>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-card class="border-sm">
                    <v-data-table :items="areviews" :headers="reviewsHeaders" items-per-page="20" mobileBreakpoint="sm">
                        <template v-slot:item.review_title="{item}">
                            <div>
                                <div class="font-weight-bold">{{ item.review_title }}</div>
                                <div>{{ item.review_text }}</div>
                            </div>
                        </template>
                        <template v-slot:item.rating="{item}">
                            <v-rating :modelValue="item.rating" readonly density="compact" color="orange"></v-rating>
                        </template>
                        <template v-slot:item.review_status="{item}">
                            <v-btn v-if="item.review_status === 'pending'" color="warning" density="compact" class="text-none" variant="text">{{ item.review_status }}</v-btn>
                            <v-btn v-if="item.review_status === 'verified'" color="success" density="compact" class="text-none" variant="text">{{ item.review_status }}</v-btn>
                            <v-btn v-if="item.review_status === 'rejected'" color="red" density="compact" class="text-none" variant="text">{{ item.review_status }}</v-btn>
                        </template>
                        <template v-slot:item.product_id="{item}">
                            <router-link :to="{name:'ProductEdit',params:{product_id:item.product_id}}">
                                <v-img :src="cdn+item.featured_image" max-width="75"></v-img>
                            </router-link>
                        </template>
                        <template v-slot:item.actions="{item}">
                            <v-btn @click="editItem(item)" color="info" density="compact" variant="outlined">Edit</v-btn>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="editReviewDialog" max-width="500">
            <v-form v-model="reviewValid" @submit.prevent="editReview">
                <v-card>
                    <v-card-text>
                        <v-rating v-model="editedItem.rating" color="orange" hover></v-rating>
                        <v-combobox v-model="editedItem.review_title" :items="rtitles"
                                    label="Review Title" variant="outlined" :rules="titleRule"
                                    density="compact" clearable
                                    class="mb-3" persistent-placeholder></v-combobox>
                        <v-textarea v-model="editedItem.review_text" label="Review Text" variant="outlined"
                                    density="compact" :placeholder="editedItem.review_title + ' ' + editedItem.title"
                                    :rules="rtextRule"
                                    class="mb-2" persistent-placeholder></v-textarea>
                        <div>
                            <v-radio-group inline v-model="editedItem.review_status">
                                <v-radio label="Pending" value="pending" color="info"></v-radio>
                                <v-radio label="Verified" value="verified" color="success"></v-radio>
                                <v-radio label="Rejected" value="rejected" color="red"></v-radio>
                            </v-radio-group>
                        </div>
                        <div class="d-flex ga-3">
                            <v-spacer/>
                            <v-btn :disabled="!reviewValid" type="submit" color="success" density="comfortable">Update</v-btn>
                            <v-btn @click="editReviewDialog  = false" color="red" density="comfortable">Cancel</v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-form>
        </v-dialog>
    </v-container>
</template>
<script>
import axios from "axios";
import {VDateInput} from "vuetify/labs/VDateInput";

export default {
    name:"SettingsReview",
    components: {VDateInput},
    data(){
        return{
            cdn:this.$store.state.cdn,
            areviews:[],
            reviewsHeaders:[
                {title:'Title',value:'review_title'},
                {title:'Rating',key:'rating'},
                {title:'Status',key:'review_status'},
                {title:'Product',value:'product_id'},
                {title:'Actions',value:'actions'},
            ],
            reviewValid:false,
            editReviewDialog:false,
            editedIndex:-1,
            editedItem:{
                proreview_id: '',
                review_title: '',
                review_text: '',
                review_status: '',
                rating: '',
                reviewer_id: '',
                shop_id: '',
                product_id: '',
            },
            titleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 5) || "Minimum 5 characters required",
                (v) => (v && v.length <= 50) || "Maximum 50 characters allowed",
            ],
            rtextRule:[
                (v) => !!v || "Text is required",
                (v) => (v && v.length >= 20) || "Minimum 20 characters required",
                (v) => (v && v.length <= 140) || "Maximum 140 characters allowed",
            ],
            rtitles:['Great service','Highly impressed!','Would buy again!','Great product!',
                'Very happy with my purchase!','Awesome product!','Great value for money!','Highly recommended!'],
        }
    },
    created() {
        this.getAllReviews();
    },
    methods:{
        getAllReviews(){
            axios.get('/sadmin/reviews/list')
                .then((resp)=>{
                    this.areviews = resp.data.reviews;
                })
        },
        editItem(item){
            this.editedIndex = this.areviews.indexOf(item);
            this.editedItem = Object.assign({},item);
            this.editReviewDialog = true;
        },
        editReview(){
            const udata = {
                proreview_id:this.editedItem.proreview_id,
                review_title:this.editedItem.review_title,
                review_text:this.editedItem?.review_text || this.editedItem.review_title,
                rating:this.editedItem.rating || 5,
                review_status:this.editedItem.review_status || 'verified',
                reviewer_id:this.editedItem.reviewer_id,
                shop_id:this.editedItem.shop_id,
                product_id:this.editedItem.product_id,
            }
            axios.post('/sadmin/review/update',udata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.editReviewDialog = false;
                    this.getAllReviews();
                })
        }
    }
}

</script>

<style scoped>

</style>
