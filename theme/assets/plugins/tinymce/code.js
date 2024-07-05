tinymce.PluginManager.add('code', function(editor, url) {

    function open() {

        var viewPort = tinymce.util.Tools.resolve('tinymce.dom.DOMUtils').DOM.getViewPort();

        if (typeof ajaxurl === 'undefined') {
            ajaxurl = '/wp-admin/admin-ajax.php';
        }

        var dialog = editor.windowManager.open({
            title: 'Source Code',
            url: ajaxurl + '?action=wysiwyg_code_editor',
            minWidth: Math.min(viewPort.w - 200, 800),
            minHeight: Math.min(viewPort.h - 200, 400),
            width: Math.min(viewPort.w - 100, 1000),
            height: Math.min(viewPort.h - 100, 800),
            resizable: true,
            maximizable: true,
            fullScreen: false,
            buttons: [{
                    text: 'Apply',
                    subtype: 'primary',
                    onclick: function() {
                        var doc = document.querySelectorAll('.mce-container-body > iframe')[0];
                        doc.contentWindow.submit();
                        dialog.close();
                    }
                },
                {
                    text: 'Cancel',
                    onclick: 'close'
                }
            ]
        });

    }

    editor.addButton('code', {
        title: 'Source Code',
        icon: 'wp_code',
        onclick: open
    });

    editor.addMenuItem('code', {
        icon: 'wp_code',
        text: 'Source Code',
        context: 'tools',
        onclick: open
    });

    document.onkeydown = function(e) {

        var isEscape = (e.keyCode === 27);

        if ("key" in e) {
            isEscape = (e.key === "Escape" || e.key === "Esc");
        }

        if (isEscape) {
            editor.windowManager.close();
        }

    };

});