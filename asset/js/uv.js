// Prepare multiple uv4.
document.addEventListener('DOMContentLoaded', function (event) {

    // The config is defined inside the html.
    if (typeof uvConfigs === 'undefined') {
        return;
    }

    uvConfigs.forEach(function (uvConfig) {
        const urlAdapter = new UV.IIIFURLAdapter();

        var params = {
            manifest: uvConfig.manifest,
            embedded: uvConfig.embedded,
            collectionIndex: urlAdapter.get('c') !== undefined ? Number(urlAdapter.get('c')) : undefined,
            manifestIndex: Number(urlAdapter.get('m', 0)),
            canvasIndex: Number(urlAdapter.get('cv', 0)),
            rotation: Number(urlAdapter.get('r', 0)),
            rangeId: urlAdapter.get('rid', ''),
            xywh: urlAdapter.get('xywh', ''),
            target: urlAdapter.get('target', ''),
        };

        // Deprecated.
        if (uvConfig.configUri && uvConfig.configUri) {
            uv = UV.init(uvConfig.id, params);
            urlAdapter.bindTo(uv);

            uv.on('configure', function ({ config, cb }) {
                cb(
                    // To increase loading speed, just use the specific settings you require.
                    // {options: { footerPanelEnabled: false, }}
                    // Full config:
                    // @see https://github.com/UniversalViewer/universalviewer/wiki/UV-Examples
                    new Promise(function (resolve) {
                        fetch(uvConfig.configUri).then(function (response) {
                            resolve(response.json());
                        });
                    })
                );
           });
       } else {
            var urlAdaptor = new UV.IIIFURLAdaptor();
            const data = urlAdaptor.getInitialData(params);
            uv = UV.init(uvConfig.id, data);
            urlAdaptor.bindTo(uv);
            if (uvConfig.config && Object.keys(uvConfig.config).length) {
                // Override config using an inline json object.
                uv.on("configure", function ({ config, cb }) {
                    cb(uvConfig.config);
                });
            }
       }

        if (!uvConfig.embedded) {
            return;
        }

        const $UV = document.getElementById(uvConfig.id);

        function resize() {
            $UV.setAttribute('style', 'width:' + window.innerWidth + 'px');
            $UV.setAttribute('style', 'height:' + window.innerHeight + 'px');
        }

        document.addEventListener('resize', function () {
            resize();
        });

        resize();
    });

});
