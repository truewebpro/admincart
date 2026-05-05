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
            <v-col cols="12" lg="3" v-for="(alink, adx) in localModel.stype_json.alinks" :key="adx">
                <v-text-field v-model="alink.ctitle" variant="underlined" density="compact" label="Custom Title" persistentPlaceholder></v-text-field>
                <v-autocomplete
                    v-if="localModel.stype_json.style !== 'style3'"
                    variant="underlined"
                    :items="alinks"
                    item-title="name"
                    return-object
                    density="compact"
                    v-model="alink.selected"
                    @update:modelValue="val => onLinkSelect(alink, val)"
                    label="Title"
                >
                    <template v-slot:item="{ props, item }">
                        <v-list-item
                            density="compact"
                            v-bind="props"
                            :subtitle="item.raw.ltype"
                            :title="item.raw.name"
                        />
                    </template>
                </v-autocomplete>
                <v-text-field variant="underlined" class="d-none"
                              density="compact"
                              v-model="alink.image_url"
                              label="Link Image" />
                <!-- Show CDN or uploaded preview -->
                <div v-if="alink.image_url && !alink.preview" class="mt-2">
                    <v-img :src="cdn + alink.image_url" max-width="150" />
                    <v-btn size="x-small" color="red" variant="outlined" class="mt-1"
                           @click="removeImage(alink)">
                        Remove
                    </v-btn>
                    <v-file-input
                        label="Replace Image" variant="underlined"
                        accept="image/*"
                        density="compact"
                        @change="handleFileSelect($event, alink)"
                        class="mt-2"
                    />
                </div>
                <!-- Local preview before upload -->
                <div v-else-if="alink.preview" class="mt-2">
                    <v-img :src="alink.preview" max-width="150" />
                    <v-btn size="small" color="red" variant="outlined" class="mt-1"
                           @click="cancelPreview(alink)">
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
                    @change="handleFileSelect($event, alink)"
                />

                <v-text-field variant="underlined" class="d-none"
                              density="compact"
                              v-model="alink.link"
                              label="Link" />
                <v-btn icon density="compact" variant="outlined" color="red" @click="removeItem(adx)">
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
    name: "FeaturedLinkEditor",
    components: {SubtextEditor},
    props: {
        modelValue: { type: Object, default: () => ({ alinks: [] }) },
        alinks: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
            cdn:this.$store.state.cdn,
        }
    },
    methods: {
        async handleFileSelect(e,alink){
            const file = e.target?.files?.[0];
            if (!file) return;
            alink.preview = URL.createObjectURL(file);
            const formData = new FormData();
            formData.append("image", file);
            formData.append("stype", this.localModel.stype_slug);
            try {
                const { data } = await axios.post(
                    "/sadmin/homepage/section/himage/upload-url",
                    formData,
                    {headers: { "Content-Type": "multipart/form-data" }}
                );
                alink.image_url = data.url;
                alink.preview = "";
            } catch (err) {
                console.error("Image upload failed", err);
                window.Toast.error("Image upload failed");
            }
        },
        removeImage(alink) {
            alink.image_url = "";
            alink.preview = "";
        },

        cancelPreview(alink) {
            alink.preview = "";
        },
        addItem() {
            this.localModel.stype_json.alinks.push({
                selected: null,
                ctitle:"",
                name: "",
                title: "",
                link: "",
                ltype: "",
                image_url: "",
                preview: ""
            })
        },
        removeItem(adx) {
            this.localModel.stype_json.alinks.splice(adx, 1)
        },
        onLinkSelect(alink, selected) {
            if (!selected) return

            alink.ctitle = selected.ctitle
            alink.name = selected.name
            alink.title = selected.name
            alink.link = selected.link
            alink.ltype = selected.ltype
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


