<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext" />
        <v-autocomplete class="mt-3" variant="underlined" density="compact"
            v-model="localModel.stype_json.cat_id" :items="categories" item-title="cat_name" item-value="cat_id"
            label="Collection"
        />
        <v-radio-group inline v-model="localModel.stype_json.type">
            <v-checkbox-btn label="Slider" value="slider"></v-checkbox-btn>
            <v-checkbox-btn label="Grid" value="grid"></v-checkbox-btn>
        </v-radio-group>
<!--        <v-combobox-->
<!--            v-model="localModel.product_ids"-->
<!--            :items="categories"-->
<!--            label="Select Products"-->
<!--            multiple-->
<!--        />-->
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "FeaturedProductsEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ product_ids: [] }) },
        availableProducts: { type: Array, default: () => [] }, // [{id:1, name:"Product A"}]
        categories: { type: Array, default: () => [] }
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
        }
    },
    watch: {
        localModel: {
            handler(val) {
                this.$emit("update:modelValue", val)
            },
            deep: true
        }
    }
}
</script>

