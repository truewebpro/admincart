<template>
    <div>
        <SemiRichTextEditor v-model="localValue"/>
    </div>
</template>

<script>
import SemiRichTextEditor from "@/components/SemiRichTextEditor.vue";

export default {
    name: "SubtextEditor",
    components: {SemiRichTextEditor},
    props: {
        modelValue: { type: String, default: "" }
    },
    data() {
        return {
            localValue: this.modelValue
        }
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(value) {
                this.localValue = value || "";
            }
        },

        localValue(value) {
            this.$emit("update:modelValue", value);
        }
    }
}
</script>

