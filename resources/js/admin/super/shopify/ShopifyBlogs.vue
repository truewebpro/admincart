<template>
    <v-row>
        <v-col cols="12">
            <v-card class="mb-3" v-if="liveBlogs?.length">
                <v-card-text>
                    <h2>Total: {{liveBlogs?.length || 0}}</h2>
                    <h3>Articles Created: {{ablogs?.length || 0}} / {{liveArticles?.length || 0}}</h3>
                    <v-btn v-if="liveBlogs?.length" class="mt-2 me-2" variant="tonal" color="success"
                           density="compact" :loading="syncLoading" @click="syncBlogsSeo" prependIcon="mdi-sync">Sync Seo</v-btn>
                    <v-btn v-if="ablogs?.length" class="mt-2" variant="tonal" color="success"
                           density="compact" :loading="syncLoading" @click="backfillThirdpartyIds" prependIcon="mdi-sync">Sync Old Articles</v-btn>
                </v-card-text>
            </v-card>
        </v-col>
        <v-col cols="12" md="12">
            <!-- One tab per live Shopify blog container -->
            <v-tabs v-model="activeBlogId" @update:model-value="onTabChange" class="mb-2" density="compact">
                <v-tab
                    v-for="blog in liveBlogs"
                    :key="blog.id"
                    :value="blog.id" class="bg-white"
                >
                    {{ blog.title }}
                </v-tab>
            </v-tabs>
            <v-card>
                <v-data-table
                    :items="liveArticles"
                    :headers="liveHeaders"
                    itemsPerPage="-1"
                    mobileBreakpoint="sm"
                    hide-default-footer
                >
                    <template #item.title="{item}">
                       <div class="font-weight-bold">{{ item.title }}</div>
                    </template>
                    <template #item.handle="{item}">
                        <v-btn icon="mdi-eye" density="compact"
                               :href="'https://'+shopifyDomain+'/blogs/'+activeBlogHandle+'/'+item.handle" target="_blank">
                        </v-btn>
                        <div>{{item.handle}}</div>
                    </template>
<!--                    <template #item.body_html="{item}">-->
<!--                        <div class="body-html" v-html="item.body_html"></div>-->
<!--                    </template>-->
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
                            :disabled="creatingIds.includes(item.id)"
                            :loading="creatingIds.includes(item.id)"
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
    name: "ShopifyBlogs",
    computed: {
        dayjs() {
            return dayjs
        },
        savedBlogsIds() {
            return new Set(this.ablogs.map((s) => String(s.thirdparty_id)));
        },
    },
    props:{
        shopifyDomain:[String]
    },
    data(){
        return{
            shop_id:this.$store.state.shop_id,
            liveBlogs:[],
            liveArticles:[],

            activeBlogId: null,
            activeBlogHandle: null,

            loading: false,
            creatingIds: [],
            showResultSnackbar: false,
            resultMessage: '',
            liveHeaders:[
                {title:'Title',key:'title',sortable: false,width: 360},
                {title:'Link',key:'handle',sortable: false},
                // {title:'Body Html',key:'body_html',width:190,sortable: false},
                {title:'Template',key:'template_suffix',align:'end'},
                {title: '', key: 'actions', sortable: false, align: 'end' },
            ],

            ablogs:[],
            aloading:false,
            atotal:0,
            syncLoading:false,
        }
    },
    async mounted() {
        await this.getLiveBlogs();
        await this.getAllBlogs();
        if (this.liveBlogs.length) {
            this.activeBlogId = this.liveBlogs[0].id;
            this.activeBlogHandle = this.liveBlogs[0].handle;
            await this.getLiveArticles(this.activeBlogId);
        }

    },
    methods:{
        isAlreadySaved(articleId) {
            return this.savedBlogsIds.has(String(articleId));
        },
        getLiveBlogs(){
            this.loading = true;
            return axios.get(`/superadmin/shopify/${this.shop_id}/blogs/live`)
                .then((resp)=>{
                    const respData = resp.data;
                    this.liveBlogs = respData.blogs || [];
                })
                .finally(()=>{
                    this.loading = false;
                })
        },
        onTabChange(blogId) {
            const blog = this.liveBlogs.find((b) => b.id === blogId);
            this.activeBlogHandle = blog?.handle || null;
            this.getLiveArticles(blogId);
        },
        getLiveArticles(blogId){
            if (!blogId) return;
            this.loading = true;
            console.log('blogId',blogId)
            return axios.get(`/superadmin/shopify/${this.shop_id}/blogs/${blogId}/articles/live`)
                .then((resp)=>{
                    const respData = resp.data;
                    this.liveArticles = respData.articles || [];
                })
                .finally(()=>{
                    this.loading = false;
                })
        },
        async createSingle(item) {
            this.creatingIds.push(item.id);
            try {
                const { data } = await axios.post(
                    `/superadmin/shopify/${this.shop_id}/blogs/${this.activeBlogId}/articles/${item.id}/create`,
                    { blog_handle: this.activeBlogHandle }
                );
                this.resultMessage = data.message || 'Article created.';
                await this.getAllBlogs();
            } catch (e) {
                this.creatingIds = this.creatingIds.filter((id) => id !== item.id);
                this.resultMessage = e.response?.data?.message || 'Failed to create Article.';
            } finally {
                this.showResultSnackbar = true;
            }
        },
        getAllBlogs(){
            return axios.get('/sadmin/blogs/list')
                .then((resp)=>{
                    this.ablogs = resp.data.blogs;
                })
        },
        async syncBlogsSeo(){
            this.syncLoading = true;
            await axios.get(`/superadmin/shopify/${this.shop_id}/blogs/sync-seo`)
                .then((resp)=>{
                    if(resp.data.success){
                        window.Toast.success('Seo Sync Success'+ resp.data?.updated)
                    }
                })
                .finally(()=>{
                    this.syncLoading = false;
                })
        },
        async backfillThirdpartyIds(){
            this.syncLoading = true;
            await axios.post(`/superadmin/shopify/${this.shop_id}/blogs/backfill-thirdparty-ids`)
                .then((resp)=>{
                    const respData = resp.data;
                    if(resp.data.success){
                        window.Toast.success(`Checked ${respData.checked}, matched ${respData.matched}`)
                    }
                    return this.getAllBlogs(); // refresh so newly-backfilled rows show as "Saved" immediately
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
    -webkit-line-clamp: 1;
    overflow: hidden;
}
</style>
