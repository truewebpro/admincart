<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <SubtextEditor v-model="localModel.stype_json.subtext" />
        <v-row class="mt-4">
            <v-col cols="12" lg="3" v-for="(scat, sdx) in localModel.stype_json.scats" :key="sdx">
                <v-card class="section-card">
                    <v-card-text>
                        <v-autocomplete variant="underlined"
                                        :items="categories" item-title="cat_name" item-value="cat_id"
                                        density="compact"
                                        v-model="scat.cat_name"
                                        @update:modelValue="onCategorySelect(scat, $event)"
                                        label="Cat Name" />
                        <section-image-picker
                            v-model="scat.image_url"
                            :stype-slug="localModel.stype_slug"
                            max-width="50"
                        />
                        <v-text-field variant="underlined"
                                      density="compact" class="d-none"
                                      v-model="scat.cat_slug"
                                      label="Cat Link" />
                        <v-btn class="mt-1" icon density="compact" variant="outlined" color="red" @click="removeItem(sdx)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </v-card-text>
                </v-card>

            </v-col>
        </v-row>
        <v-btn class="mt-2" color="primary" density="compact" @click="addItem">+ Add More</v-btn>
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";
import SectionImagePicker from "@/components/sections/editors/SectionImagePicker.vue";

export default {
    name: "PopularRangeEditor",
    components: {SectionImagePicker, SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ scats: [] }) },
        categories: { type: Array, default: () => [] }
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
        }
    },
    methods: {
        addItem() {
            this.localModel.stype_json.scats.push({
                cat_id:null,
                cat_name: "",
                image_url: "",
                cat_slug: "collections",
                preview:""
            })
        },
        removeItem(idx) {
            this.localModel.stype_json.scats.splice(idx, 1)
        },
        onCategorySelect(scat, selectedCatId) {
            const selected = this.categories.find(c => c.cat_id === selectedCatId)
            if (selected) {
                scat.cat_id = selected.cat_id
                scat.cat_name = selected.cat_name
                scat.cat_slug = selected.cat_slug
                scat.image_url = selected.image_url
            }
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
