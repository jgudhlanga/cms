<script setup lang="ts">
import { onBeforeUnmount, shallowRef, reactive, toRefs } from "vue";
import { Editor, Toolbar } from "@wangeditor/editor-for-vue";
import {
    IDomEditor,
    IEditorConfig,
    IToolbarConfig,
    i18nChangeLanguage,
} from "@wangeditor/editor";


const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ (e: "update:modelValue", value: string): void }>();

i18nChangeLanguage("en");

const editorRef = shallowRef<IDomEditor>();

const state = reactive({
    toolbarConfig: {
        toolbarKeys: [
            'headerSelect',
            'blockquote',
            '|',
            'bold',
            'underline',
            'italic',
            {
                key: 'group-more-style',
                title: 'More style',
                menuKeys: ['through', 'code', 'sup', 'sub', 'clearStyle']
            },
            'color',
            'bgColor',
            '|',
            'fontSize',
            'fontFamily',
            'lineHeight',
            '|',
            'bulletedList',
            'numberedList',
            'todo',
            {
                key: 'group-justify',
                title: 'Justify',
                menuKeys: ['justifyLeft', 'justifyRight', 'justifyCenter', 'justifyJustify']
            },
            {
                key: 'group-indent',
                title: 'Indent',
                menuKeys: ['indent', 'delIndent']
            },
            '|',
            'emotion',
            'insertLink',
            {
                key: 'group-image',
                title: 'Image',
                menuKeys: ['insertImage', 'uploadImage']
            },
            {
                key: 'group-video',
                title: 'Video',
                menuKeys: ['insertVideo', 'uploadVideo']
            },
            'insertTable',
            'codeBlock',
            'divider',
            '|',
            'undo',
            'redo'
            // 'fullscreen' is intentionally omitted
        ]
    } as IToolbarConfig,
    editorConfig: {
        placeholder: "Write something amazing...",
        customAlert: () => {},
        scroll: true,
        readOnly: false,
        autoFocus: true,
        hoverbarKeys: {},
        lang: "en",
    } as IEditorConfig,
    defaultHtml: props.modelValue,
    mode: "default",
});

const { toolbarConfig, editorConfig, defaultHtml, mode } = toRefs(state);

const handleCreated = (editor: IDomEditor) => {
    editorRef.value = editor;
    editor.setHtml(props.modelValue);
};

function handleChange(editor: IDomEditor) {
    emit("update:modelValue", editor.getHtml());
}

onBeforeUnmount(() => {
    const editor = editorRef.value;
    if (!editor) return;
    editor.destroy();
});

</script>

<template>
    <div class="editor-container">
        <Toolbar
            :editor="editorRef"
            :defaultConfig="toolbarConfig"
            class="editor-toolbar"
            :mode="mode"
        />
        <Editor
            :defaultConfig="editorConfig"
            v-model="defaultHtml"
            @on-change="handleChange"
            @on-created="handleCreated"
            class="editor-content"
            :mode="mode"
        />
    </div>
</template>

<style src="@wangeditor/editor/dist/css/style.css"></style>
<style scoped>
.editor-container {
    max-width: 100%;
    margin: auto;
    min-height: 400px;
    /*border-radius: 16px;*/
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    /*border: 1px solid #e2e8f0;*/
}

.editor-toolbar {
    border-bottom: 1px solid #e2e8f0;
    background-color: #f9fafb;
    padding: 8px 12px;
    box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.05);
    /*border-radius: 16px 16px 0 0;*/
}

.editor-content {
    height: 70vh;
    padding: 20px;
    min-height: 300px;
    overflow-y: auto;
    font-family: "Inter", sans-serif;
    font-size: 16px;
    color: #1f2937;
    line-height: 1.6;
    background-color: #ffffff;
    border-radius: 0 0 16px 16px;
    transition: all 0.2s ease;
}

.editor-content:focus-within {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
    outline: none;
}
</style>
