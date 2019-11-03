document.addEventListener('DOMContentLoaded', function(event) {

    // The config is defined inside the html.
    if (typeof uv === 'undefined') {
        return;
    }

    window.addEventListener('uvLoaded', function (e) {
        var uvElement;
        uv.forEach(function (config, index) {
            uvElement = createUV('#' + config.id, config, new UV.URLDataProvider());
            /*
            // Check uv loading.
            uvElement.on('created', function(obj) {
                console.log('parsed metadata', uvElement.extension.helper.manifest.getMetadata());
                console.log('raw jsonld', uvElement.extension.helper.manifest.__jsonld);
            });
            */
        });
    }, false);

});
