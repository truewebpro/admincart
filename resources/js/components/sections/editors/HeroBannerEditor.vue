<template>
    <v-row>
        <v-col cols="12" md="7">
            <v-text-field variant="underlined"
                          v-model="localModel.stype_json.heading"
                          label="Heading"
            />
            <v-text-field variant="underlined"
                          v-model="localModel.stype_json.promo_text"
                          label="Promo Text"
            />
            <v-text-field variant="underlined"
                          v-model="localModel.stype_json.subheading"
                          label="Subheading"
            />
            <div class="d-flex ga-2 mt-2">
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
                max-width="150"
            />
        </v-col>
    </v-row>
</template>

<script>
import SectionImagePicker from "@/components/sections/editors/SectionImagePicker.vue";

export default {
    name: "HeroBannerEditor",
    components: {SectionImagePicker},
    props: {
        modelValue: { type: Object, default: () => ({}) }
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
