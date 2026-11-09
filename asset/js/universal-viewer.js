// Prepare multiple uv3.
(function () {

    function initUV() {
        // The config is set in the data attribute of each viewer, or in a
        // global variable by the templates of old themes.
        var configs = typeof uv === 'undefined' ? [] : uv.slice();
        document.querySelectorAll('[data-uv-config]').forEach(function (element) {
            configs.push(JSON.parse(element.dataset.uvConfig));
        });
        var uvElement;
        configs.forEach(function (config, index) {
            var urlDataProvider = new UV.URLDataProvider();
            config['collectionIndex'] = Number(urlDataProvider.get('c', 0)),
            config['manifestIndex'] = Number(urlDataProvider.get('m', 0)),
            config['sequenceIndex'] = Number(urlDataProvider.get('s', 0)),
            config['canvasIndex'] = Number(urlDataProvider.get('cv', 0));
            config['xywh'] = Number(urlDataProvider.get('xywh', ''));
            uvElement = createUV('#' + config.id, config, urlDataProvider);
        });
    }

    // Handle race condition: uvLoaded may fire before this
    // deferred script registers its listener.
    if (typeof UV !== 'undefined' && typeof createUV === 'function') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initUV);
        } else {
            initUV();
        }
    } else {
        window.addEventListener('uvLoaded', initUV, false);
    }

})();
