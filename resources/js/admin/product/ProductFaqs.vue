<template>
    <div class="pt-2">
        <v-card class="mb-2">
            <v-card-item>
                <template #title>Frequently Asked Questions</template>
                <template #append v-if="faqs.length"><v-btn @click="showAddDialog" color="success" variant="tonal" density="comfortable">Add Faq</v-btn></template>
            </v-card-item>
        </v-card>
        <v-row>
            <v-col v-if="!faqs.length" cols="12">
                <v-card>
                    <v-empty-state
                        icon="mdi-frequently-asked-questions"
                        title="No Product FAQs added yet"
                        text="Answer the questions customers ask most before they reach out to support"
                    >
                        <template #actions>
                            <v-btn @click="showAddDialog"
                                   color="success"
                                   variant="tonal"
                                   density="default">Add Faq</v-btn>
                        </template>
                    </v-empty-state>
                </v-card>
            </v-col>
            <v-col v-for="(faq) in faqs" :key="faq.id" cols="12" md="6">
                <v-card class="mb-2">
                    <v-card-title class="bg-grey-lighten-3">{{faq.question}}</v-card-title>
                    <v-card-item>
                        <template #append>
                            <div class="d-flex flex-column ga-1">
                                <v-btn @click="editFaq(faq)" color="primary" icon variant="outlined" density="compact">
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn  @click="delFaq(faq)" color="red" icon variant="outlined" density="compact">
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </div>
                        </template>
                        <template #default>
                            <div v-html="faq.answer"></div>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="addFaqDialog" maxWidth="800" zIndex="100">
            <v-card>
                <v-card-title>Add New Question</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Question about the product"
                                  v-model="question" label="Question"></v-text-field>
                    <SemiRichTextEditor v-model="answer"/>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="addFaq" :loading="addLoading" color="green" variant="elevated" density="comfortable">Save</v-btn>
                    <v-btn @click="addFaqDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="editFaqDialog" maxWidth="800" zIndex="100">
            <v-card>
                <v-card-title>Edit Faq</v-card-title>
                <v-card-text>
                    <v-text-field variant="outlined" density="compact" persistentPlaceholder
                                  placeholder="Question about the product"
                                  v-model="editedItem.question" label="Question"></v-text-field>
                    <SemiRichTextEditor v-model="editedItem.answer"/>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="updateFaq" :loading="editLoading" color="green" variant="elevated" density="comfortable">Update</v-btn>
                    <v-btn @click="editFaqDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog v-model="deleteFaqDialog" maxWidth="300" zIndex="100">
            <v-card>
                <v-card-title>Delete Faq</v-card-title>
                <v-card-text class="text-center">
                    Are you sure to delete question<br/>
                    <span class="font-weight-medium">{{editedItem.question}}</span>
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="deleteFaq" :loading="deleteLoading" color="green" variant="elevated" density="comfortable">Delete</v-btn>
                    <v-btn @click="deleteFaqDialog = false" color="red" variant="outlined" density="comfortable">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import SemiRichTextEditor from "@/components/SemiRichTextEditor.vue";

export default {
    name: "ProductFaqs",
    components: {SemiRichTextEditor},
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
            editFaqDialog:false,
            deleteFaqDialog:false,
            addLoading:false,
            editLoading:false,
            deleteLoading:false,
            editedItem:{
                question: "",
                answer:"",
            }
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

        },
        editFaq(faq){
            this.editedItem = {...faq};
            this.editFaqDialog = true;
        },
        delFaq(faq){
            this.editedItem = {...faq};
            this.deleteFaqDialog = true;
        },
        updateFaq(){
            this.editLoading = true;
            const edata = {
                'product_id': this.product_id,
                'id':this.editedItem.id,
                'question':this.editedItem.question,
                'answer':this.editedItem.answer,
            }
            axios.post(`/sadmin/product/edit-faq`,edata)
                .then((resp)=>{
                    this.$emit('refresh-faqs');
                    this.editFaqDialog = false;
                    window.Toast.success(resp.data.message);
                    this.editedItem = {
                        id: null,
                        question: '',
                        answer: ''
                    };
                })
                .finally(()=>{
                    this.editLoading = false;
                })
        },
        deleteFaq(){
            this.deleteLoading = true;
            const ddata = {
                'product_id': this.product_id,
                'id':this.editedItem.id,
            }
            axios.post(`/sadmin/product/delete-faq`,ddata)
                .then((resp)=>{
                    this.$emit('refresh-faqs');
                    this.deleteFaqDialog = false;
                    window.Toast.success(resp.data.message);
                    this.editedItem = {
                        id: null,
                        question: '',
                        answer: ''
                    };
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
