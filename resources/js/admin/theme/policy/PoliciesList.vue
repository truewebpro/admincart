<template>
    <v-container class="Policies-list">
        <v-row class="position-sticky top-0 bg-grey-lighten-3" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn icon variant="tonal" density="compact" color="primary">
                        <v-icon size="small">mdi-file-document-check-outline</v-icon>
                    </v-btn>
                    Policies
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
<!--                <v-btn link :to="{name:'PoliciesNew'}" variant="elevated" color="grey-darken-4" density="compact" class="text-none"-->
<!--                       prepend-icon="mdi-plus">Add Policy</v-btn>-->
            </v-col>
        </v-row>
        <v-row class="mt-0">
            <v-col cols="12" md="12">
                <v-card elevation="0" class="border-sm">
                    <v-data-table :items="apolicies" :headers="apoliciesHeader" hover hide-default-footer>
                        <template v-slot:item.policy_name="{item}">
                            <div class="title d-flex align-center justify-space-between">
                                <router-link :to="'/theme/policies/'+item.policy_id" :title="item.policy_name"
                                             class="font-weight-medium text-grey-darken-4 linkd">
                                    <v-icon v-if="item.policy_slug === 'terms'">mdi-file-document-check-outline</v-icon>
                                    <v-icon v-if="item.policy_slug === 'privacy'">mdi-lock-outline</v-icon>
                                    <v-icon v-if="item.policy_slug === 'refund-policy'">mdi-credit-card-refund-outline</v-icon>
                                    <v-icon v-if="item.policy_slug === 'shipping-policy'">mdi-truck-outline</v-icon>
                                    {{item.policy_name}}
                                </router-link>
                                <a :href="'https://'+domain+'/policies/'+item.policy_slug" target="_blank">
                                    <v-icon class="opacity-0" title="View Policy Online">mdi-eye-outline</v-icon>
                                </a>
                            </div>
                        </template>
                        <template v-slot:item.policy_status="{item}">
                            <v-btn v-if="item.policy_status === 'active'" variant="tonal" color="success" density="compact" class="rounded-pill text-capitalize">{{item.policy_status}}</v-btn>
                            <v-btn v-else color="red" variant="tonal" density="compact" class="rounded-pill text-capitalize">{{item.policy_status}}</v-btn>
                        </template>
                        <template v-slot:item.actions="{item}">
                            <v-btn :to="'/theme/policies/'+item.policy_id" variant="tonal" density="compact" color="info">Edit</v-btn>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";

export default {
    name:"PoliciesList",
    data(){
        return{
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            apolicies:[],
            apoliciesHeader:[
                {title:'Title',value:'policy_name'},
                {title:'Visibility',value:'policy_status'},
                {title:'Actions',value:'actions'},
            ]
        }
    },
    created() {
        this.getAllPolicies();
    },
    methods:{
        getAllPolicies(){
            axios.get('/sadmin/policies/list')
                .then((resp)=>{
                    this.apolicies = resp.data.policies;
                })
        }
    }
}

</script>

<style scoped>
.linkd {text-decoration: none;}
.linkd:hover {text-decoration: underline;}
.title:hover{
    span { text-decoration: underline !important;}

    .opacity-0 {opacity: 1 !important;}
}
</style>
