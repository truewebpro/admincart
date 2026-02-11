<template>
    <div>
        <SubtextEditor v-model="localModel.stype_json.subtext"/>
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "CustomTextEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object,required:true},
    },
    computed: {
        localModel: {
            get() {
                return this.modelValue
            },
            set(val) {
                this.$emit("update:modelValue", val)
            }
        }
    }
}
</script>


