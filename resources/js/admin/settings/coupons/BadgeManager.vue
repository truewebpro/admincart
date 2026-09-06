<template>
    <v-card flat>
        <v-card-text>
            <v-card>
                <v-card-title class="d-flex ga-2 justify-space-between">
                    Badges
                    <v-btn color="primary" prependIcon="mdi-plus" density="comfortable" @click="openAddBadge()">
                        Add Badge
                    </v-btn>
                </v-card-title>
                <v-table>
                    <thead>
                    <tr>
                        <th width="150px">Preview</th>
                        <th>Position</th>
                        <th>Style</th>
                        <th>Coupons</th>
                        <th>Rules</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="b in badges" :key="b.id">
                        <td style="min-width: 150px">
                            <span
                                class="px-2 py-1 rounded text-caption font-weight-medium"
                                :style="{
                                    backgroundColor: b.style === 'outline' ? 'white': b.bg_color || '#111827',
                                    color:b.style === 'outline' ? b.bg_color : b.color || '#ffffff',
                                    borderStyle:b.style === 'outline' ? 'solid' : 'dashed',
                                    border:'1px ' + 'solid' + ' ' + b.bg_color
                                }"
                            >
                                {{ b.use_label ? b.label : '(uses coupon/rule label)' }}
                            </span>
                        </td>
                        <td>{{ positionLabel(b.position) }}</td>
                        <td>{{ b.style || '—' }}</td>
                        <td>
                            <v-chip size="small" variant="outlined">{{ b.coupons_count }}</v-chip>
                        </td>
                        <td>
                            <v-chip size="small" variant="outlined">{{ b.rules_count }}</v-chip>
                        </td>
                        <td>
                            <v-chip :color="b.is_active ? 'green' : 'grey'" density="compact" class="font-weight-medium">
                                {{ b.is_active ? 'Active' : 'Inactive' }}
                            </v-chip>
                        </td>
                        <td>
                            <div class="d-flex ga-2">
                                <v-btn color="info" variant="outlined" icon="mdi-pencil"
                                       @click="editBadge(b)" density="comfortable" />
                                <v-btn color="secondary" variant="outlined" icon="mdi-link-variant"
                                       @click="openAssign(b)" density="comfortable" />
                                <v-btn color="red" variant="outlined" icon="mdi-delete"
                                       @click="confirmDelete(b.id)" density="comfortable" />
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </v-table>
            </v-card>
        </v-card-text>

        <!-- Create / Edit dialog -->
        <v-dialog v-model="showBadgeDialog" max-width="600">
            <v-card>
                <v-card-title>{{ isEditMode ? 'Edit Badge' : 'Create Badge' }}</v-card-title>
                <v-card-text>
                    <v-form ref="defaultBadge">
                        <v-text-field
                            variant="underlined" density="comfortable" persistentPlaceholder
                            v-model="defaultBadge.label" label="Badge Text"
                            placeholder="e.g. Buy 4 Get 1 Free"
                        />
                        <v-switch
                            v-model="defaultBadge.use_label" color="primary" density="comfortable"
                            label="Show this text (off = use the coupon's/rule's own label instead)"
                        />

                        <v-select
                            variant="underlined" density="comfortable" persistentPlaceholder
                            v-model="defaultBadge.position"
                            :items="positions"
                            item-title="label"
                            item-value="value"
                            label="Position"
                        />

                        <v-row dense>
                            <v-col cols="6">
                                <v-text-field
                                    variant="underlined" density="comfortable" persistentPlaceholder
                                    v-model="defaultBadge.color" label="Text Color" placeholder="#FFFFFF"
                                >
                                    <template #append-inner>
                                        <span
                                            class="d-inline-block rounded"
                                            style="width:20px;height:20px;border:1px solid #ccc"
                                            :style="{ backgroundColor: defaultBadge.color || 'transparent' }"
                                        />
                                    </template>
                                </v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    variant="underlined" density="comfortable" persistentPlaceholder
                                    v-model="defaultBadge.bg_color" label="Background Color" placeholder="#16A34A"
                                >
                                    <template #append-inner>
                                        <span
                                            class="d-inline-block rounded"
                                            style="width:20px;height:20px;border:1px solid #ccc"
                                            :style="{ backgroundColor: defaultBadge.bg_color || 'transparent' }"
                                        />
                                    </template>
                                </v-text-field>
                            </v-col>
                        </v-row>

                        <v-combobox
                            variant="underlined" density="comfortable" persistentPlaceholder
                            v-model="defaultBadge.style"
                            :items="['solid', 'outline', 'ribbon']"
                            label="Style"
                            hint="Pick one or type your own"
                        />

                        <v-switch
                            v-model="defaultBadge.is_active" color="primary" density="comfortable"
                            label="Active"
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showBadgeDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveBadge">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Assign coupons/rules dialog -->
        <v-dialog v-model="showAssignDialog" max-width="600">
            <v-card>
                <v-card-title>Assign "{{ assigningBadge?.label }}"</v-card-title>
                <v-card-text>
                    <v-autocomplete
                        v-model="assignCouponIds"
                        :items="couponsWithLabel"
                        item-title="display_title_or_code"
                        item-value="coupon_id"
                        label="Coupons"
                        chips
                        multiple
                        closable-chips
                        variant="underlined"
                        density="comfortable"
                        hint="Only auto-applied coupons can show a badge"
                        persistent-hint
                    />
                    <v-autocomplete
                        v-model="assignRuleIds"
                        :items="rules"
                        item-title="name"
                        item-value="id"
                        label="Pricing Rules"
                        chips
                        multiple
                        closable-chips
                        variant="underlined"
                        density="comfortable"
                        class="mt-4"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showAssignDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveAssign" :loading="assignLoading">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete confirm -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Delete Badge?</v-card-title>
                <v-card-text>This removes it from every coupon/rule it's attached to. This can't be undone.</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="red" @click="deleteBadge" :loading="deleteLoading">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-card>
</template>

<script>
import axios from "axios";

export default {
    name: "BadgeManager",
    data() {
        return {
            badges: [],
            coupons: [],
            rules: [],
            positions: [],

            showBadgeDialog: false,
            isEditMode: false,
            defaultBadge: this.getDefaultBadge(),

            showAssignDialog: false,
            assigningBadge: null,
            assignCouponIds: [],
            assignRuleIds: [],
            assignLoading: false,

            deleteDialog: false,
            deleteBadgeId: null,
            deleteLoading: false,
        };
    },
    computed: {
        couponsWithLabel() {
            return this.coupons.map(c => ({
                ...c,
                display_title_or_code: c.display_title || c.title || c.code,
            }));
        },
    },
    mounted() {
        this.getAllBadges();
    },
    methods: {
        getDefaultBadge() {
            return {
                id: null,
                label: '',
                use_label: true,
                color: '#FFFFFF',
                bg_color: '#16A34A',
                style: 'solid',
                position: 'top-left',
                is_active: true,
            };
        },
        positionLabel(value) {
            return this.positions.find(p => p.value === value)?.label || value;
        },
        async getAllBadges() {
            try {
                const res = await axios.get('/sadmin/badge/list');
                if (res.data.success) {
                    this.badges = res.data.badges;
                    this.coupons = res.data.coupons;
                    this.rules = res.data.rules;
                    this.positions = res.data.positions;
                }
            } catch (e) {
                console.error(e);
            }
        },
        openAddBadge() {
            this.isEditMode = false;
            this.defaultBadge = this.getDefaultBadge();
            this.showBadgeDialog = true;
        },
        editBadge(b) {
            this.isEditMode = true;
            this.defaultBadge = { ...b };
            this.showBadgeDialog = true;
        },
        async saveBadge() {
            try {
                const res = await axios.post('/sadmin/badge/save', this.defaultBadge);
                if (res.data.success) {
                    this.showBadgeDialog = false;
                    await this.getAllBadges();
                }
            } catch (e) {
                console.error(e);
            }
        },
        openAssign(b) {
            this.assigningBadge = b;
            this.assignCouponIds = [...(b.coupon_ids || [])];
            this.assignRuleIds = [...(b.rule_ids || [])];
            this.showAssignDialog = true;
        },
        async saveAssign() {
            this.assignLoading = true;
            try {
                const res = await axios.post('/sadmin/badge/assign', {
                    id: this.assigningBadge.id,
                    coupon_ids: this.assignCouponIds,
                    rule_ids: this.assignRuleIds,
                });
                if (res.data.success) {
                    this.showAssignDialog = false;
                    await this.getAllBadges();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.assignLoading = false;
            }
        },
        confirmDelete(id) {
            this.deleteBadgeId = id;
            this.deleteDialog = true;
        },
        async deleteBadge() {
            this.deleteLoading = true;
            try {
                const res = await axios.post('/sadmin/badge/delete', { id: this.deleteBadgeId });
                if (res.data.success) {
                    this.deleteDialog = false;
                    this.deleteBadgeId = null;
                    await this.getAllBadges();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.deleteLoading = false;
            }
        },
    },
};
</script>

<style scoped>
</style>
