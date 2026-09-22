<template>
    <div>
        <!-- Current image, with Remove/Replace/Library as three separate buttons -->
        <div v-if="modelValue && !preview" class="mt-2">
            <v-img :src="cdn + modelValue" :max-width="maxWidth" />
            <div class="d-flex ga-1 mt-1 flex-wrap">
                <v-btn size="x-small" color="red" variant="outlined" @click="remove">Remove</v-btn>
                <v-btn size="x-small" variant="outlined" @click="triggerUpload">Replace</v-btn>
                <v-btn size="x-small" color="success" variant="outlined" @click="showPicker = true">Use Library</v-btn>
            </div>
            <input ref="fileInput" type="file" accept="image/*" class="d-none" @change="handleFileSelect" />
        </div>

        <!-- Local preview while uploading -->
        <div v-else-if="preview" class="mt-2">
            <v-img :src="preview" :max-width="maxWidth" />
            <div class="text-caption text-medium-emphasis mt-1">Uploading...</div>
        </div>

        <!-- Empty state -->
        <div v-else>
            <v-file-input
                :label="label"
                :hint="hint"
                :persistent-hint="!!hint"
                accept="image/*"
                density="compact"
                variant="underlined"
                @change="handleVFileInput"
            />
            <v-btn size="small" variant="tonal" class="mt-1" @click="showPicker = true">
                Choose from Library
            </v-btn>
        </div>

        <media-library-picker
            v-model="showPicker"
            :cdn-base="cdn"
            @select="onLibrarySelected"
        />
    </div>
</template>

<script>
import axios from "axios";
import MediaLibraryPicker from "@/components/MediaLibraryPicker.vue";

export default {
    name: "SectionImagePicker",

    components: { MediaLibraryPicker },

    props: {
        modelValue: { type: String, default: "" },
        stypeSlug: { type: String, default: "" },
        fieldName: { type: String, default: "image" }, // "image" (desktop/default) or "mimage" (slideshow mobile)
        label: { type: String, default: "Upload Image" },
        hint: { type: String, default: "" },
        maxWidth: { type: [String, Number], default: 150 },
    },

    emits: ["update:modelValue"],

    data() {
        return {
            cdn: this.$store.state.cdn,
            preview: "",
            showPicker: false,
        };
    },

    methods: {
        triggerUpload() {
            this.$refs.fileInput.click();
        },

        handleVFileInput(e) {
            const file = e.target?.files?.[0] ?? e;
            this.uploadFile(Array.isArray(file) ? file[0] : file);
        },

        handleFileSelect(e) {
            const file = e.target?.files?.[0];
            this.uploadFile(file);
        },

        async uploadFile(file) {
            if (!file) return;

            this.preview = URL.createObjectURL(file);

            const formData = new FormData();
            formData.append(this.fieldName, file);
            formData.append("stype", this.stypeSlug);

            try {
                const { data } = await axios.post(
                    "/sadmin/homepage/section/himage/upload-url",
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );
                this.$emit("update:modelValue", data.url);
            } catch (err) {
                console.error("Image upload failed", err);
                window.Toast.error("Image upload failed");
            } finally {
                this.preview = "";
            }
        },

        onLibrarySelected(mediaFile) {
            this.$emit("update:modelValue", mediaFile.path);
        },

        remove() {
            this.$emit("update:modelValue", "");
        },
    },
};
</script>
