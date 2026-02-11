<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext"/>
        <v-row class="mt-3">
            <v-col v-for="(ques,qdx) in localModel.stype_json.faqs" :key="qdx" cols="12" lg="6">
                <v-text-field v-model="ques.question" :label="'Question '+ (qdx +1)" variant="underlined" density="compact"/>
                <SubtextEditor v-model="ques.answer"/>
                <v-btn class="mt-1" icon density="compact" variant="outlined" color="red" @click="removeItem(qdx)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-btn class="mt-2" color="primary" density="compact" @click="addItem">+ Add More</v-btn>
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";

export default {
    name: "FaqsEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ faqs: [] }) },
        availableFaqs: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue))
        }
    },
    methods:{
        addItem() {
            this.localModel.stype_json.faqs.push({ question:"Question ?",answer:"Answer"})
        },
        removeItem(qdx) {
            this.localModel.stype_json.faqs.splice(qdx, 1)
        },
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


