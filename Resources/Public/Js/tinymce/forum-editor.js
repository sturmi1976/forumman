document.addEventListener('DOMContentLoaded', function () {

tinymce.init({
    selector: '#content',
    height: 500,

    license_key: 'gpl',
    language: 'de',

    menubar: 'file edit view insert format tools table help',

    plugins: [
        'advlist',
        'autolink',
        'lists',
        'link',
        'image',
        'charmap',
        'preview',
        'anchor',
        'searchreplace',
        'visualblocks',
        'code',
        'fullscreen',
        'insertdatetime',
        'media',
        'table',
        'help',
        'wordcount',
        'emoticons',
        'codesample',
        'paste'
    ],

    codesample_languages: [
    { text: 'HTML/XML', value: 'markup' },
    { text: 'JavaScript', value: 'javascript' },
    { text: 'CSS', value: 'css' },
    { text: 'PHP', value: 'php' },
    { text: 'SQL', value: 'sql' },

    // ⭐ Eigene Sprache
    { text: 'TypoScript', value: 'typoscript' }
],

    toolbar:
        'undo redo | blocks | ' +
        'bold italic underline strikethrough | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | ' +
        'link image media table | ' +
        'emoticons charmap | ' +
        'codesample code | ' +
        'fullscreen preview',

    toolbar_mode: 'sliding',

    contextmenu: 'link image table',

    content_style:
        'body { font-family: Helvetica, Arial, sans-serif; font-size:16px }',

    setup: function(editor) {
        editor.on('change', function () {
            editor.save();
        });
    }

});

});


document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('forumMessageModal');
    const closeBtn = document.querySelector('.forum-modal-close');
    const receiverInput = document.getElementById('receiverUid');

    document.querySelectorAll('.forum-message-btn').forEach(button => {

        button.addEventListener('click', function () {

            const uid = this.dataset.user;

            receiverInput.value = uid;

            modal.style.display = 'block';

            // TinyMCE nur einmal initialisieren
            if (!tinymce.get('messageContent')) {

                tinymce.init({
                    selector: '#messageContent',
                    height: 300,
                    license_key: 'gpl',
                    language: 'de',

                    menubar: false,

                    plugins: [
                        'advlist',
                        'autolink',
                        'lists',
                        'link',
                        'charmap',
                        'preview',
                        'searchreplace',
                        'visualblocks',
                        'code',
                        'fullscreen',
                        'emoticons',
                        'codesample'
                    ],

                    toolbar:
                        'undo redo | bold italic underline | ' +
                        'alignleft aligncenter alignright | ' +
                        'bullist numlist | link | code',

                    setup: function(editor) {
                        editor.on('change', function () {
                            editor.save();
                        });
                    }

                });

            }

        });

    });

    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

});