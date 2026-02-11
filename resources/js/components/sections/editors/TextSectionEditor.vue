<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <v-text-field variant="underlined" density="compact"
                      v-model="localModel.stype_json.subheading"
                      label="Subheading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext" />
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "FeaturedProductsEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ product_ids: [] }) },
        availableProducts: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
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

