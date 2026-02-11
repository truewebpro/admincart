<template>
    <div>
        <v-text-field variant="underlined" density="compact"
                      v-model="localModel.stype_json.heading"
                      label="Heading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext"/>
<!--        <v-combobox-->
<!--            v-model="localModel.stype_json.blogs"-->
<!--            :items="availableBlogs"-->
<!--            label="Select Blogs"-->
<!--            multiple-->
<!--        />-->
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "BlogSliderEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ blogs: [] }) },
        availableBlogs: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
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


