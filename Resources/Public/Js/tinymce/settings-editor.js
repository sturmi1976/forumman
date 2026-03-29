tinymce.init({
    selector: '#profilbeschreibung,#signature',
    height: 300,

    license_key: 'gpl',
    language: 'de',

    menubar: false,

    plugins: [
        'link',
        'lists',
        'code'
    ],

    toolbar: 'undo redo | bold italic | bullist numlist | link | code',

    toolbar_mode: 'wrap', 

    setup: function (editor) {
        editor.on('change', function () {
            editor.save();
        });
    }
});