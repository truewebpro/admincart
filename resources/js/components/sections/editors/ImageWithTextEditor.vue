<template>
    <v-row>
        <v-col cols="12" md="7">
            <v-text-field variant="underlined" density="compact"
                          v-model="localModel.stype_json.heading"
                          label="Heading"
            />
            <SubtextEditor v-model="localModel.stype_json.subtext" />

            <div class="d-flex ga-2 mt-3">
                <v-text-field variant="underlined"
                              v-model="localModel.stype_json.button_text"
                              label="Button Text"
                />
                <v-text-field variant="underlined"
                              v-model="localModel.stype_json.button_link"
                              label="Button Link" hint="/collections or /products or /brands" persistent-hint
                />
            </div>
        </v-col>
        <v-col cols="12" md="5">
            <section-image-picker
                v-model="localModel.stype_json.image_url"
                :stype-slug="localModel.stype_slug"
                hint="Ratio Should be 600px / 400px"
                max-width="200"
            />
        </v-col>
    </v-row>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";
import SectionImagePicker from "@/components/sections/editors/SectionImagePicker.vue";

export default {
    name: "ImageWithTextEditor",
    components: {SubtextEditor, SectionImagePicker},
    props: {
        modelValue: { type: Object, default: () => ({ product_ids: [] }) },
        availableProducts: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
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
