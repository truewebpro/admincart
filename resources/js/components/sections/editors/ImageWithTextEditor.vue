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
            <!-- Image Upload -->
            <v-file-input
                label="Upload Image" hint="Ratio Should be 600px / 400px"
                accept="image/*" persistent-hint
                @change="handleFileSelect" variant="underlined"
                v-if="!localModel.stype_json.image_url && !preview"
            />
            <!-- Preview Existing (CDN) -->
            <div v-if="!preview && localModel.stype_json.image_url" class="mt-2">
                <v-img :src="cdn + localModel.stype_json.image_url" max-width="200" />
                <v-btn size="small" color="red" variant="outlined" class="mt-1"
                       @click="removeImage">
                    Remove Image
                </v-btn>
                <v-file-input
                    label="Replace Image" hint="Ratio Should be 600px / 400px"
                    accept="image/*" persistent-hint
                    @change="handleFileSelect"
                    class="mt-2" variant="underlined"
                />
            </div>

            <!-- Preview Local Upload -->
            <div v-else-if="preview" class="mt-2">
                <v-img :src="preview" max-width="150" />
                <v-btn size="small" color="red" variant="outlined" class="mt-1"
                       @click="cancelPreview">
                    Cancel
                </v-btn>
            </div>

            <!-- Hidden URL input -->
            <v-img v-if="preview" :src="preview" max-width="200"/>
            <v-text-field variant="underlined" class="d-none"
                          v-model="localModel.stype_json.image_url"
                          label="Image URL"
            />
        </v-col>
    </v-row>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";
import axios from "axios";

export default {
    name: "ImageWithTextEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ product_ids: [] }) },
        availableProducts: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
            cdn:this.$store.state.cdn,
            preview:"",
        }
    },
    methods:{
        async handleFileSelect(e) {
            const file = e.target?.files?.[0];
            if (!file) return;
            this.preview = URL.createObjectURL(file);
            console.log('file',file,'this.preview',this.preview)
            const formData = new FormData();
            formData.append("image", file);
            formData.append("stype", this.localModel.stype_slug);
            try {
                const { data } = await axios.post(
                    "/sadmin/homepage/section/himage/upload-url",
                    formData,
                    {headers: { "Content-Type": "multipart/form-data" }}
                );
                this.localModel.stype_json.image_url = data.url;
                this.preview = "";
            } catch (err) {
                console.error("Image upload failed", err);
                window.Toast.error("Image upload failed");
            }
        },
        removeImage() {
            this.localModel.stype_json.image_url = "";
            this.preview = "";
        },

        cancelPreview() {
            this.preview = "";
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

