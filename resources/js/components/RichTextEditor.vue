<template>
    <HugerteEditor
        v-model="content"
        :init="editorConfig"
    />
</template>

<script setup>
import {computed} from 'vue'
import HugerteEditor from '@hugerte/hugerte-vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const content = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value)
})

const editorConfig = {
    height: 400,
    menubar: false,
    statusbar:false,
    relative_urls: false,
    remove_script_host: true,
    convert_urls: false,

    plugins: [
        'advlist',
        'autolink',
        'lists',
        'link',
        'image',
        'media',
        'table',
        'code',
        // 'fullscreen',
        'searchreplace',
        'wordcount'
    ],

    toolbar:
        'blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media table | code fullscreen | undo redo'
}
</script>
