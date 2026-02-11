<template>
    <div>
        <SubtextEditor v-model="localModel.stype_json.raw"/>
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "DefaultSectionEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({}) }
    },
    data() {
        return {
            localModel: JSON.stringify(this.modelValue, null, 2)
        }
    },
    watch: {
        localModel(val) {
            try {
                const parsed = JSON.parse(val)
                this.$emit("update:modelValue", parsed)
            } catch (e) {
                // ignore invalid JSON
            }
        }
    }
}
</script>

