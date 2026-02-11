<template>
    <div>
        <quill-editor ref="quillRef"
            v-model="localValue"
            @text-change="onEditorChange"
        />
    </div>
</template>

<script>
export default {
    name: "SubtextEditor",
    props: {
        modelValue: { type: String, default: "" }
    },
    data() {
        return {
            localValue: this.modelValue
        }
    },
    mounted(){
        const quill = this.$refs.quillRef.getQuill();
        quill.root.innerHTML = this.modelValue || "";
    },
    watch: {
        modelValue(newVal) {
            if (newVal !== this.localValue) {
                this.localValue = newVal;
                const quill = this.$refs.quillRef.getQuill();
                if (quill.root.innerHTML !== newVal) {
                    quill.root.innerHTML = newVal || "";
                }
            }
        }
    },
    methods: {
        onEditorChange(delta, oldDelta, source) {
            const quill = this.$refs.quillRef.getQuill();
            this.localValue = quill.root.innerHTML;
            console.log('his.localValue',this.localValue)
            this.$emit("update:modelValue", this.localValue)
        }
    }
}
</script>

