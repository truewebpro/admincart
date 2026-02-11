<template>
    <div>
        <v-row class="mt-4">
            <v-col cols="12" lg="12" v-for="(scat, sdx) in localModel.stype_json" :key="sdx">
                <div>
                    <v-row>
                        <v-col cols="12" md="6">
                            <div>
                                <h4>Desktop Image 1200 x 400</h4>
                                <v-text-field variant="underlined" class="d-none" density="compact"
                                              v-model="scat.image_url"
                                              label="Slide Image" />
                                <!-- Show CDN or uploaded preview -->
                                <div v-if="scat.image_url && !scat.preview" class="mt-2">
                                    <v-img :src="cdn + scat.image_url" max-width="150" />
                                    <v-btn size="x-small" color="red" variant="outlined" class="mt-1"
                                           @click="removeImage(scat)">
                                        Remove
                                    </v-btn>
                                    <v-file-input
                                        label="Replace Image" variant="underlined"
                                        accept="image/*"
                                        density="compact"
                                        @change="handleFileSelect($event, scat)"
                                        class="mt-2"
                                    />
                                </div>
                                <!-- Local preview before upload -->
                                <div v-else-if="scat.preview" class="mt-2">
                                    <v-img :src="scat.preview" max-width="150" />
                                    <v-btn size="small" color="red" variant="outlined" class="mt-1"
                                           @click="cancelPreview(scat)">
                                        Cancel / Loading
                                    </v-btn>
                                </div>

                                <!-- Upload when empty -->
                                <v-file-input v-else label="Upload Desktop Image" accept="image/*"
                                              density="compact" variant="underlined" @change="handleFileSelect($event, scat)"
                                />
                            </div>
                            <div>
                                <h4>Mobile Image 600 x 600</h4>
                                <v-text-field variant="underlined" class="d-none" density="compact"
                                              v-model="scat.mimage_url"
                                              label="Slide Image" />
                                <!-- Show CDN or uploaded preview -->
                                <div v-if="scat.mimage_url && !scat.mpreview" class="mt-2">
                                    <v-img :src="cdn + scat.mimage_url" max-width="150" />
                                    <v-btn size="x-small" color="red" variant="outlined" class="mt-1"
                                           @click="mremoveImage(scat)">
                                        Remove
                                    </v-btn>
                                    <v-file-input
                                        label="Replace Image" variant="underlined"
                                        accept="image/*"
                                        density="compact"
                                        @change="mhandleFileSelect($event, scat)"
                                        class="mt-2"
                                    />
                                </div>
                                <!-- Local preview before upload -->
                                <div v-else-if="scat.mpreview" class="mt-2">
                                    <v-img :src="scat.mpreview" max-width="150" />
                                    <v-btn size="small" color="red" variant="outlined" class="mt-1"
                                           @click="mcancelPreview(scat)">
                                        Cancel / Loading
                                    </v-btn>
                                </div>

                                <!-- Upload when empty -->
                                <v-file-input v-else label="Upload Mobile Image" accept="image/*"
                                              density="compact" variant="underlined" @change="mhandleFileSelect($event, scat)"
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
import axios from "axios";

export default {
    name: "SlideShowEditor",
    props: {
        modelValue: { type: Object, default: () => ({ scats: [] }) },
        categories: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
            cdn:this.$store.state.cdn,
        }
    },
    methods: {
        async handleFileSelect(e,scat){
            const file = e.target?.files?.[0];
            if (!file) return;
            scat.preview = URL.createObjectURL(file);
            const formData = new FormData();
            formData.append("image", file);
            formData.append("stype", this.localModel.stype_slug);
            try {
                const { data } = await axios.post(
                    "/sadmin/homepage/section/himage/upload-url",
                    formData,
                    {headers: { "Content-Type": "multipart/form-data" }}
                );
                scat.image_url = data.url;
                scat.preview = "";
            } catch (err) {
                console.error("Image upload failed", err);
                window.Toast.error("Image upload failed");
            }
        },
        removeImage(scat) {
            scat.image_url = "";
            scat.preview = "";
        },

        cancelPreview(scat) {
            scat.preview = "";
        },
        async mhandleFileSelect(e,scat){
            const file = e.target?.files?.[0];
            if (!file) return;
            scat.mpreview = URL.createObjectURL(file);
            const formData = new FormData();
            formData.append("mimage", file);
            formData.append("stype", this.localModel.stype_slug);
            try {
                const { data } = await axios.post(
                    "/sadmin/homepage/section/himage/upload-url",
                    formData,
                    {headers: { "Content-Type": "multipart/form-data" }}
                );
                scat.mimage_url = data.url;
                scat.mpreview = "";
            } catch (err) {
                console.error("Image upload failed", err);
                window.Toast.error("Image upload failed");
            }
        },
        mremoveImage(scat) {
            scat.mimage_url = "";
            scat.mpreview = "";
        },

        mcancelPreview(scat) {
            scat.mpreview = "";
        },
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


