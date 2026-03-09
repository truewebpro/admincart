<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <v-select v-model="localModel.stype_json.style" label="Style" density="compact" variant="underlined"
                  :items="['style1','style1b','style2','style3','style4','style5','style5b','style6']" class="mb-2"
                  hint="Style1 with Name shape & style1b transparent with button, Style2 With no Name Seen, Style3 with no Link, style4 links/name under image, style5 links/name in top & style5b bottom center of image, style6 circle with link/name" persistent-hint></v-select>
        <SubtextEditor v-model="localModel.stype_json.subtext" />
        <v-row class="mt-4">
            <v-col cols="12" lg="3" v-for="(scat, sdx) in localModel.stype_json.scats" :key="sdx">
                <v-autocomplete v-if="localModel.stype_json.style !== 'style3'" variant="underlined"
                                :items="categories" item-title="cat_name" item-value="cat_id"
                                density="compact"
                                v-model="scat.cat_name"
                                @update:modelValue="onCategorySelect(scat, $event)"
                                label="Cat Name" />
                <v-text-field variant="underlined" class="d-none"
                              density="compact"
                              v-model="scat.image_url"
                              label="Cat Image" />
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
                <v-file-input
                    v-else
                    label="Upload Image"
                    accept="image/*"
                    density="compact"
                    variant="underlined"
                    @change="handleFileSelect($event, scat)"
                />

                <v-text-field variant="underlined" class="d-none"
                              density="compact"
                              v-model="scat.cat_slug"
                              label="Cat Link" />
                <v-btn icon density="compact" variant="outlined" color="red" @click="removeItem(sdx)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-btn class="mt-2" color="primary" density="compact" @click="addItem">+ Add More</v-btn>
    </div>
</template>

<script>
import SubtextEditor from "@/components/sections/editors/SubtextEditor.vue";
import axios from "axios";

export default {
    name: "FeaturedCollectionsEditor",
    components: {SubtextEditor},
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
        addItem() {
            this.localModel.stype_json.scats.push({
                cat_id:null,
                cat_name: "",
                image_url: "",
                cat_slug: "collections",
                preview:""
            })
        },
        removeItem(sdx) {
            this.localModel.stype_json.scats.splice(sdx, 1)
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


