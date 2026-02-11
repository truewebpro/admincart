<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Main Heading"
        />
        <v-select v-model="localModel.stype_json.style" label="Style" density="compact" variant="underlined"
                  :items="['style1','style2','style3']" class="mb-2"
                  hint="Style1 Accordion with h3, Style2 Accordion default, Style3 without Accordion" persistent-hint></v-select>
        <SubtextEditor v-model="localModel.stype_json.subtext"/>
        <v-row class="mt-3">
            <v-col v-for="(fopt,fdx) in localModel.stype_json.foptions" :key="fdx" cols="12" md="6" lg="4">
                <v-text-field v-model="fopt.title" :label="'Question '+ (fdx +1)" variant="underlined" density="compact"/>
                <SubtextEditor v-model="fopt.ftext"/>
                <v-btn class="mt-1" icon density="compact" variant="outlined" color="red" @click="removeItem(fdx)">
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
    name: "FeaturedOptionsEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ foptions: [] }) },
        availableFoptions: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue))
        }
    },
    methods:{
        addItem() {
            this.localModel.stype_json.foptions.push({ title:"title",ftext:"Option Text"})
        },
        removeItem(qdx) {
            this.localModel.stype_json.foptions.splice(qdx, 1)
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


