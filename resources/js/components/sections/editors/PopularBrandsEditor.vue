<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext" />
        <v-autocomplete class="mt-3" variant="underlined" density="compact"
            v-model="localModel.stype_json.brands" return-object
            :items="brands" item-title="brand_name" hide-selected
            label="Select Brands" chips closable-chips
            multiple
        />
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "PopularBrandsEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ brands: [] }) },
        brands: { type: Array, default: () => [] }
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue))
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

