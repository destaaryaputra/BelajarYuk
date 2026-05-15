/** Shared admin rich text editors. */
let matQuill = null;
let submatQuill = null;

function initQuillEditors() {
    if (typeof Quill === 'undefined') return;
    const toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'header': [2, 3, false] }],
        ['link', 'blockquote', 'code-block'],
        ['clean']
    ];
    
    if (document.getElementById('mat-content-editor') && !matQuill) {
        matQuill = new Quill('#mat-content-editor', {
            theme: 'snow',
            placeholder: 'Tuliskan isi materi pembelajaran di sini...',
            modules: { toolbar: toolbarOptions }
        });
    }
    if (document.getElementById('submat-content-editor') && !submatQuill) {
        submatQuill = new Quill('#submat-content-editor', {
            theme: 'snow',
            placeholder: 'Tuliskan rangkuman atau penjelasan episode ini...',
            modules: { toolbar: toolbarOptions }
        });
    }
}

