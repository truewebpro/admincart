<template>
    <div>
        <v-row class="mt-4">
            <v-col cols="12" lg="12" v-for="(scat, sdx) in localModel.stype_json" :key="sdx">
                <div>
                    <v-row>
                        <v-col cols="12" md="6">
                            <div>
                                <h4>Desktop Image 1200 x 400</h4>
                                <section-image-picker
                                    v-model="scat.image_url"
                                    :stype-slug="localModel.stype_slug"
                                    field-name="image"
                                    label="Upload Desktop Image"
                                    max-width="150"
                                />
                            </div>
                            <div class="mt-3">
                                <h4>Mobile Image 600 x 600</h4>
                                <section-image-picker
                                    v-model="scat.mimage_url"
                                    :stype-slug="localModel.stype_slug"
                                    field-name="mimage"
                                    label="Upload Mobile Image"
                                    max-width="150"
                                />
                            </div>

                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select variant="underlined" :items="['center','left','right']"
                                      density="compact" v-model="scat.setting.content_position"
                                      label="Content Position" />
                            <v-text-field variant="underlined" density="compact" v-model="scat.heading" label="Heading"/>
                            <v-text-field variant="underlined" density="compact" v-model="scat.subheading" label="Subheading"/>
                            <v-text-field variant="underlined" density="compact" v-model="scat.promo_text" label="Promo Text"/>
                            <v-text-field variant="underlined" density="compact" v-model="scat.button_text" label="Button Text"/>
                            <v-text-field variant="underlined" density="compact" v-model="scat.button_link" label="Button Link"/>
                        </v-col>
                    </v-row>
                </div>
                <v-btn icon density="compact" variant="outlined" color="red" @click="removeItem(sdx)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-btn class="mt-2" color="primary" density="compact" @click="addItem">+ Add More</v-btn>
    </div>
</template>

<script>
import SectionImagePicker from "@/components/sections/editors/SectionImagePicker.vue";

export default {
    name: "SlideShowEditor",
    components: {SectionImagePicker},
    props: {
        modelValue: { type: Object, default: () => ({ scats: [] }) },
        categories: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
        }
    },
    methods: {
        addItem() {
            this.localModel.stype_json.push({
                image_url: "",
                preview:"",
                mimage_url: "",
                mpreview:"",
                heading:"",
                subheading: "",
                promo_text: "",
                button_text:"",
                button_link:"",
                setting:{
                    content_position:'center'
                }
            })
        },
        removeItem(sdx) {
            this.localModel.stype_json.splice(sdx, 1)
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
