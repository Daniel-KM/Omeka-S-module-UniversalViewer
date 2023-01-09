// Prepare multiple uv3.
document.addEventListener('DOMContentLoaded', function(event) {

    // The config is defined inside the html.
    if (typeof uv === 'undefined') {
        return;
    }

    window.addEventListener('uvLoaded', function (e) {
        var uvElement;
        uv.forEach(function (config, index) {
            var urlDataProvider = new UV.URLDataProvider();
            config['collectionIndex'] = Number(urlDataProvider.get('c', 0)),
            config['manifestIndex'] = Number(urlDataProvider.get('m', 0)),
            config['sequenceIndex'] = Number(urlDataProvider.get('s', 0)),
            config['canvasIndex'] = Number(urlDataProvider.get('cv', 0));
            config['xywh'] = Number(urlDataProvider.get('xywh', ''));
            uvElement = createUV('#' + config.id, config, urlDataProvider);
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
