<template>
    <v-row>
        <v-col cols="12">
            <v-card class="mb-3" v-if="livePages?.length">
                <v-card-text>
                    <h2>Total: {{livePages?.length || 0}}</h2>
                    <h3>Pages Created: {{apages?.length || 0}} / {{livePages?.length || 0}} </h3>
                    <v-btn v-if="livePages?.length" class="mt-2" variant="tonal" color="success"
                           density="compact" @click="syncPagesSeo" prependIcon="mdi-sync">Sync Seo</v-btn>
                </v-card-text>
            </v-card>
        </v-col>
        <v-col cols="12" md="12">
            <v-card>
                <v-data-table
                    :items="livePages"
                    :headers="liveHeaders"
                    itemsPerPage="-1"
                    mobileBreakpoint="sm"
                >
                    <template #item.title="{item}">
                       <div class="font-weight-bold">{{ item.title }}</div>
                        <div>{{item.handle}}</div>
                    </template>
                    <template #item.handle="{item}">
                        <v-btn icon="mdi-eye" density="compact" :href="'https://'+shopifyDomain+'/pages/'+item.handle" target="_blank">
                        </v-btn>
                    </template>
                    <template #item.body_html="{item}">
                        <div class="body-html" v-html="item.body_html"></div>
                    </template>
                    <template #item.actions="{item}">
                        <v-chip
                            v-if="isAlreadySaved(item.id)"
                            size="small"
                            color="success"
                            variant="tonal"
                            prepend-icon="mdi-check"
                        >
                            Saved
                        </v-chip>

                        <v-btn
                            v-else
                            size="small"
                            variant="tonal"
                            @click="createSingle(item)"
                        >
                            Create
                        </v-btn>
                    </template>
                </v-data-table>
                <v-snackbar v-model="showResultSnackbar" :timeout="4000">
                    {{ resultMessage }}
                </v-snackbar>
            </v-card>
        </v-col>
    </v-row>
</template>
<script>
import dayjs from "dayjs";
import axios from "axios";

export default {
    name: "ShopifyPages",
    computed: {
        dayjs() {
            return dayjs
        },
        savedPagesIds() {
            return new Set(this.apages.map((s) => String(s.thirdparty_id)));
        },
    },
    props:{
        shopifyDomain:[String]
    },
    data(){
        return{
            shop_id:this.$store.state.shop_id,
            livePages:[],
            loading: false,
            showResultSnackbar: false,
            resultMessage: '',
            liveHeaders:[
                {title:'Title',key:'title',sortable: false,width: 360},
                {title:'Link',key:'handle',sortable: false},
                {title:'Body Html',key:'body_html',width:190,sortable: false},
                {title:'Template',key:'template_suffix',align:'end'},
                {title: '', key: 'actions', sortable: false, align: 'end' },
            ],

            apages:[],
            aloading:false,
            atotal:0,
            syncLoading:false,
        }
    },
    mounted() {
        this.getLivePages();
        this.getAllPages();
    },
    methods:{
        isAlreadySaved(pageId) {
            return this.savedPagesIds.has(String(pageId));
        },
        getLivePages(){
            this.loading = true;
            return axios.get(`/superadmin/shopify/${this.shop_id}/pages/live`)
                .then((resp)=>{
                    const respData = resp.data;
                    this.livePages = respData.pages || [];
                })
                .finally(()=>{
                    this.loading = false;
                })
        },
        async createSingle(item) {
            try {
                const { data } = await axios.post(
                    `/superadmin/shopify/${this.shop_id}/pages/${item.id}/create`
                );
                this.resultMessage = data.message || 'Page created.';
                await this.getAllPages();
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Failed to create page.';
            } finally {
                this.showResultSnackbar = true;
            }
        },
        getAllPages(){
            return axios.get('/sadmin/pages/list')
                .then((resp)=>{
                    this.apages = resp.data.pages;
                })
        },
        syncPagesSeo(){
            this.syncLoading = true;
            return axios.get(`/superadmin/shopify/${this.shop_id}/pages/sync-seo`)
                .then((resp)=>{
                    if(resp.data.success){
                        window.Toast.success('Seo Sync Success'+ resp.data?.updated)
                    }
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        }
    }
}
</script>

<style scoped>
.body-html {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
}
</style>
