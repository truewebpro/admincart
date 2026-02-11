<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext" />
<!--        <v-combobox variant="underlined" density="compact"-->
<!--            v-model="localModel.stype_json.reviews"-->
<!--            :items="availableReviews"-->
<!--            label="Select Reviews"-->
<!--            multiple-->
<!--        />-->
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "ReviewSliderEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ reviews: [] }) },
        availableReviews: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
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


