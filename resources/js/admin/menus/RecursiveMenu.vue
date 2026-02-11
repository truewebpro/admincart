<template>
    <ul style="list-style: none;position: relative">
        <draggable v-model="localItems" item-key="id" @end="onDragEnd">
            <template #item="{ element, index }">
                <li class="d-grid group" :class="'group'+level">
                    <div class="d-flex w-100 rounded-lg px-3 py-1 align-center" :class="'level'+level">
                        <v-icon class="me-3">mdi-drag</v-icon>
                        <v-text-field variant="plain" hide-details density="compact" v-model="localItems[index].label"
                                      @input="emitUpdate" />
                        <v-spacer/>
                        <span class="ms-4 linxurl">{{localItems[index].url}}</span>
                        <v-spacer/>
                        <v-icon class="ms-5" @click="deleteItem(index)">mdi-trash-can-outline</v-icon>
                    </div>
                    <!-- Recursive call with children -->
                    <RecursiveMenu
                        v-if="element.children && element.children.length"
                        :items="element.children"
                        :level="level + 1"
                        @update:items="childrenUpdated(index, $event)"
                        @open-add-dialog="$emit('open-add-dialog', $event)"
                    />
                    <div class="d-flex mbtn">
                        <v-icon v-if="level < 2" color="grey">mdi-arrow-right-bottom</v-icon>
                        <v-btn @click="$emit('open-add-dialog', element)"
                               v-if="level < 2"
                               color="primary"
                               variant="outlined"
                               density="compact" class="text-none ms-8 mb-2">
                            Add menu item to {{element.label}}</v-btn>
                    </div>
                </li>
            </template>
        </draggable>
    </ul>
</template>
<script>
import draggable from "vuedraggable"
export default {
    name: 'RecursiveMenu',
    components:{draggable},
    props: {
        items: Array,
        level: { type: Number, default: 0 },
        emits: ['update:items', 'open-add-dialog'],
    },
    emits: ['update:items'],
    data() {
        return {
            localItems: JSON.parse(JSON.stringify(this.items)) // deep clone to local state
        };
    },
    watch: {
        items: {
            handler(newVal) {
                this.localItems = JSON.parse(JSON.stringify(newVal)); // sync when prop changes
            },
            deep: true
        }
    },
    methods: {
        openAddChildDialog(parentItem) {
            this.selectedParent = parentItem;
            this.addAsChild = true;
            this.addChildOrParent = true;
            this.search = '';
            this.groupedResults = [];
            this.$emit('open-add-dialog', parentItem);
        },
        emitUpdate() {
            this.$emit('update:items', JSON.parse(JSON.stringify(this.localItems)));
        },
        deleteItem(index) {
            this.localItems.splice(index, 1);
            this.emitUpdate();
        },
        childrenUpdated(index, newChildren) {
            this.localItems[index].children = newChildren;
            this.emitUpdate();
        },
        onDragEnd() {
            // After drag-and-drop reorder, emit the update to parent
            this.emitUpdate();
        }
    }
};
</script>
<style scoped>
li.group {
    margin-inline-start: 33px;
    & div.level0 {
        background: #fafafa;
        margin-bottom: 10px;
        border: 1px solid #a0a0a0;
        box-shadow: 1px 1px 3px 0 rgba(0,0,0,0.1);
    }
    & div.level1 {
        background: #fafafa;
        margin-bottom: 10px;
        border: 1px solid #a0a0a0;
        box-shadow: 1px 1px 3px 0 rgba(0,0,0,0.1);
        position: relative;
        &::before {
            content: "";
            position: absolute;
            left: -16px;
            display: block;
            width: 16px;
            border-top: 2px solid #a0a0a0;
            top:20px;
        }
    }
    & div.level2 {
        background: #fafafa;
        margin-bottom: 10px;
        border: 1px solid #a0a0a0;
        box-shadow: 1px 1px 3px 0 rgba(0,0,0,0.1);
        position: relative;
        &::before {
            content: "";
            position: absolute;
            left: -16px;
            display: block;
            width: 16px;
            border-top: 2px solid #a0a0a0;
            top: 20px;
        }
    }
    & li.group {
        position: inherit;
        z-index: 2;
        &.group1::before{
            content:'';
            position: absolute;
            top: -10px;
            left: 16px;
            display: block;
            height: 100%;
            border-left: 2px solid #a0a0a0;
            z-index: 1;
        }
        &.group2::before{
            content:'';
            position: absolute;
            top: -10px;
            left: 16px;
            display: block;
            height: 100%;
            border-left: 2px solid #a0a0a0;
            z-index: 1;
        }
    }
}
.mbtn {
    margin-left: 12px;
}

</style>
