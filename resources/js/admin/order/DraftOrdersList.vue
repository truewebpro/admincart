<template>
    <v-container class="pa-1 pa-lg-3">
        <v-row dense align="end">
            <v-col cols="12" md="8">
                <div style="font-size:20px; font-weight:600;">Draft Orders</div>
                <div class="muted">Showing latest {{ items.length }} draft orders</div>
            </v-col>
            <v-col cols="12" md="4" class="text-lg-end">
                <v-btn color="primary" variant="tonal" @click="goToCreate" prependIcon="mdi-plus">
                    Create draft order
                </v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="12">
                <v-card class="section-card">
                    <v-text-field v-model="search" rounded="lg" variant="outlined" density="compact" class="pa-2"
                                  prependInnerIcon="mdi-magnify"
                                  hide-details  placeholder="Search draft number or customer"></v-text-field>
                    <v-data-table
                        hover
                        :items="items"
                        :headers="headers"
                        :search="search"
                        :loading="loading"
                        hide-default-footer
                        :items-per-page="-1"
                        density="comfortable"
                        @click:row="(_, { item }) => goToEdit(item.id)"
                        class="draft-orders-table"
                    >
                        <template #item.draft_number="{ item }">
                            <span class="font-weight-semibold">{{ item.draft_number }}</span>
                        </template>

                        <template #item.customer="{ item }">
                            <span v-if="item.customer" class="font-weight-medium text-capitalize">{{ item.customer.name }}</span>
                            <span v-else class="muted">—</span>
                        </template>

                        <template #item.status="{ item }">
                            <v-chip size="small" :color="statusColor(item.status)"
                                    class="font-weight-medium"
                                    density="comfortable" variant="outlined">
                                {{ item.status }}
                            </v-chip>
                        </template>

                        <template #item.payment_status="{ item }">
                            <v-chip size="small" :color="paymentStatusColor(item.payment_status)"
                                    class="font-weight-medium"
                                    variant="outlined" density="comfortable">
                                {{ paymentStatusLabel(item.payment_status) }}
                            </v-chip>
                        </template>

                        <template #item.total="{ item }">
                            £{{ Number(item.total || 0).toFixed(2) }}
                        </template>

                        <template #item.created_at="{ item }">
                            {{ formatDate(item.created_at) }}
                        </template>

                        <template #item.actions="{ item }">
                            <v-menu>
                                <template #activator="{ props }">
                                    <v-btn icon size="small" variant="text" v-bind="props" @click.stop>
                                        <v-icon>mdi-dots-vertical</v-icon>
                                    </v-btn>
                                </template>
                                <v-list density="compact">
                                    <v-list-item @click="goToEdit(item.id)">
                                        <v-list-item-title>View / edit</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="duplicate(item)">
                                        <v-list-item-title>Duplicate</v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="confirmDelete(item)">
                                        <v-list-item-title class="text-error">Delete</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </template>

                        <template #no-data>
                            <div class="py-8 text-center muted">No draft orders yet.</div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="deleteDialog.show" max-width="380">
            <v-card>
                <v-card-title class="py-3 px-4">Delete draft order?</v-card-title>
                <v-divider></v-divider>
                <v-card-text class="pa-4">
                    This will delete <strong>{{ deleteDialog.item?.draft_number }}</strong>. This can't be undone from here.
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" size="small" @click="deleteDialog.show = false">Cancel</v-btn>
                    <v-btn color="error" size="small" :loading="deleting" @click="deleteDraft">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</v-snackbar>
    </v-container>
</template>
<script>
export default {
    name: "DraftOrdersList",
    data(){
        return{
            items: [],
            loading: false,
            search: '',
            deleteDialog: { show: false, item: null },
            deleting: false,
            snackbar: { show: false, text: '', color: 'success' },
            headers: [
                { title: 'Draft #', key: 'draft_number' },
                { title: 'Created', key: 'created_at' },
                { title: 'Customer', key: 'customer' },
                { title: 'Total', key: 'total', align: 'end' },
                { title: 'Payment', key: 'payment_status' },
                { title: 'Status', key: 'status' },
                { title: 'Items', key: 'item_count', align: 'center' },
                { title: '', key: 'actions', sortable: false, align: 'end', width: 56 },
            ],
        }
    },
    created() {
        this.fetchDraftOrders();
    },
    methods:{
        async fetchDraftOrders() {
            this.loading = true;
            try {
                const { data } = await axios.get('/sadmin/draft-orders', {
                    params: { per_page: 50, sort_by: 'created_at', sort_dir: 'desc' },
                });
                this.items = data.data ?? data;
            } catch (e) {
                this.showSnackbar('Failed to load draft orders', 'error');
            } finally {
                this.loading = false;
            }
        },
        goToCreate() {
            this.$router.push({ name: 'DraftOrderCreate' });
        },
        goToEdit(id) {
            this.$router.push({ name: 'DraftOrderEdit', params: { id } });
        },
        async duplicate(item) {
            try {
                const { data } = await axios.post(`/sadmin/draft-orders/${item.id}/duplicate`);
                const copy = data.data ?? data;
                this.showSnackbar('Draft duplicated', 'success');
                this.goToEdit(copy.id);
            } catch (e) {
                this.showSnackbar('Failed to duplicate draft', 'error');
            }
        },

        confirmDelete(item) {
            this.deleteDialog = { show: true, item };
        },
        async deleteDraft() {
            this.deleting = true;
            try {
                await axios.delete(`/sadmin/draft-orders/${this.deleteDialog.item.id}`);
                this.items = this.items.filter(i => i.id !== this.deleteDialog.item.id);
                this.deleteDialog.show = false;
                this.showSnackbar('Draft order deleted', 'success');
            } catch (e) {
                this.showSnackbar('Failed to delete draft order', 'error');
            } finally {
                this.deleting = false;
            }
        },
        statusColor(status) {
            const map = { draft: 'grey-darken-1', confirmed: 'success', processing: 'info', completed: 'success', cancelled: 'error' };
            return map[status] || 'grey';
        },

        paymentStatusLabel(status) {
            const map = { unpaid: 'Unpaid', partially_paid: 'Partially Paid', paid: 'Paid', refunded: 'Refunded' };
            return map[status] || 'Unpaid';
        },

        paymentStatusColor(status) {
            const map = { unpaid: 'red', partially_paid: 'red', paid: 'success', refunded: 'error' };
            return map[status] || 'grey';
        },

        formatDate(value) {
            if (!value) return '—';
            return new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        showSnackbar(text, color) {
            this.snackbar = { show: true, text, color };
        },
    }
}

</script>

<style scoped>
.section-card { border: 1px solid #e1e3e6; box-shadow: 0 1px 2px rgba(16,24,40,0.04); border-radius: 8px; }
.muted { color: #6b7177; font-size: 13px; }
.draft-orders-table :deep(tbody tr) { cursor: pointer; }
</style>
