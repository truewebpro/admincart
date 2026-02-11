<template>
    <v-row>
        <v-col cols="12" md="7">
            <v-text-field variant="underlined" density="compact" v-model="localModel.stype_json.heading" label="Heading"/>
            <SubtextEditor v-model="localModel.stype_json.subtext" />

            <div class="d-flex ga-2 mt-3">
                <v-text-field variant="underlined" v-model="localModel.stype_json.button_text" label="Button Text"/>
                <v-text-field variant="underlined" v-model="localModel.stype_json.button_link" label="Button Link" hint="/collections or /products or /brands" persistent-hint/>
            </div>
        </v-col>
        <v-col cols="12" md="5">
            <!-- Video Upload -->
            <v-file-input
                label="Upload Video" hint="Recommended: MP4, Max 100MB, 16:9 ratio"
                accept="video/*" persistent-hint
                @change="handleFileSelect" variant="underlined"
                v-if="!localModel.stype_json.video_url && !preview"
                prepend-icon="mdi-video-plus"
            />
            <!-- Preview Existing Video (CDN) -->
            <div v-if="!preview && localModel.stype_json.video_url" class="mt-2">
                <video controls preload="metadata" class="w-100 h-100 v-img__img--cover">
                    <source :src="cdn + localModel.stype_json.video_url" type="video/mp4">
                    <source :src="cdn + localModel.stype_json.video_url.replace('.mp4', '.webm')" type="video/mp4">
                </video>
                <v-btn size="small" color="red" variant="outlined" class="mt-1"
                       @click="removeVideo">
                    Remove Video
                </v-btn>
                <v-file-input
                    label="Replace Video" hint="Recommended: MP4, Max 100MB, 16:9 ratio"
                    accept="video/*" persistent-hint
                    @change="handleFileSelect"
                    class="mt-2" variant="underlined"
                    prepend-icon="mdi-video-plus"
                />
            </div>
            <!-- Preview Local Upload -->
            <div v-else-if="preview" class="mt-2">
                <video controls preload="metadata" class="w-100 h-100 v-img__img--cover">
                    <source :src="preview" type="video/mp4">
                    <source :src="preview.replace('.mp4', '.webm')" type="video/mp4">
                </video>
                <v-btn size="small" color="red" variant="outlined" class="mt-1" @click="cancelPreview">Loading...</v-btn>
            </div>

            <!-- Hidden URL input -->
            <v-text-field variant="underlined" class="d-none" v-model="localModel.stype_json.video_url" label="Video URL"/>
        </v-col>
    </v-row>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";
import axios from "axios";
import {VVideo} from 'vuetify/labs/components';

export default {
    name: "VideoWithTextEditor",
    components: {SubtextEditor,VVideo},
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
            if (!file.type.startsWith('video/')) {
                window.Toast.error("Please select a video file");
                return;
            }

            if (file.size > 1000 * 1024 * 1024) { // 1000MB limit
                window.Toast.error("Video size must be less than 100MB");
                return;
            }
            this.preview = URL.createObjectURL(file);
            console.log('Video file',file,'this.preview',this.preview)
            const formData = new FormData();
            formData.append("video", file);
            formData.append("stype", this.localModel.stype_slug);
            try {
                const { data } = await axios.post(
                    "/sadmin/homepage/section/video/upload-url",
                    formData,
                    {headers: { "Content-Type": "multipart/form-data" }}
                );
                this.localModel.stype_json.video_url = data.url;
                this.preview = "";
            } catch (err) {
                console.error("Video upload failed", err);
                window.Toast.error("Video upload failed");
                this.preview = "";
            }
        },
        removeImage() {
            this.localModel.stype_json.video_url = "";
            this.preview = "";
        },

        cancelPreview() {
            if (this.preview.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }
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
    },
    beforeUnmount() {
        // ✅ Cleanup preview URL on component destroy
        if (this.preview && this.preview.startsWith('blob:')) {
            URL.revokeObjectURL(this.preview);
        }
    }
}
</script>

