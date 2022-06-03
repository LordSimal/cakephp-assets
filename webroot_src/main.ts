import "./scss/styles.scss";

uploadImports();

async function uploadImports()
{
    if (!document.querySelector('[data-vue-upload-field]')) {
        return;
    }

    const { default: initUploadField } = await import('./components/upload-field/initUploadField');
    initUploadField();
}
