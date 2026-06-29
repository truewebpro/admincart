<template>
    <div>
        <v-row class="faqs">
            <v-col cols="12" md="6">
                <h2>Frequently Asked Questions</h2>
            </v-col>
            <v-col cols="12" md="6">
                <v-btn @click="showAddDialog" color="success" variant="elevated" density="comfortable">Add Faq</v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col v-for="(faq,index) in faqs" :key="index" cols="12" md="6">
                <v-card class="mb-2">
                    <v-card-title class="bg-grey-lighten-3">
                      {{faq.question}}
                    </v-card-title>
                    <v-card-text class="mt-2" v-html="faq.answer">
                    </v-card-text>
                    <v-card-actions>
                        <v-btn color="primary" variant="outlined" density="compact">Edit</v-btn>
                        <v-btn color="red" variant="outlined" density="compact">Remove</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="addFaqDialog" maxWidth="600" zIndex="100">
            <v-card>
                <v-card-title>Add New Question</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Question about the product"
                                  v-model="question" label="Question"></v-text-field>
                    <RichTextEditor v-model="answer"/>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="addFaq" :loading="addLoading" color="green" variant="elevated" density="comfortable">Save</v-btn>
                    <v-btn @click="addFaqDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import RichTextEditor from "@/components/RichTextEditor.vue";

export default {
    name: "ProductFaqs",
    components: {RichTextEditor},
    props: {
        faqs: Array,
        product_id: [String, Number],
    },
    data(){
        return{
            question: "",
            answer:"",
            sort_order:0,
            status:true,
            addFaqDialog:false,
            addLoading:false,
        }
    },
    methods:{
        showAddDialog(){
            this.addFaqDialog = true;
        },
        async addFaq() {
            this.addLoading = true;
            const adata = {
                product_id: this.product_id,
                question: this.question,
                answer: this.answer,
                sort_order: this.sort_order,
                status: this.status
            }
            console.log('adata',adata);
            await axios.post(`/sadmin/product/add-faq`,adata)
                .then((resp)=>{
                    this.$emit('refresh-faqs');
                    this.addFaqDialog = false;
                    this.question = '';
                    this.answer = '';
                    this.sort_order = 0;
                    this.status = true;
                    window.Toast.success(resp.data.message);
                })
                .finally(()=>{
                    this.addLoading = false;
                })

        }
    }
}
</script>

<style scoped>

</style>
