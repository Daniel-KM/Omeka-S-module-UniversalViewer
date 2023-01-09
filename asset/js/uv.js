// Prepare multiple uv4.
document.addEventListener('DOMContentLoaded', function (event) {

    // The config is defined inside the html.
    if (typeof uvConfigs === 'undefined') {
        return;
    }

    uvConfigs.forEach(function (uvConfig) {
        const urlAdapter = new UV.IIIFURLAdapter();

        var data = {
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

        uv = UV.init(uvConfig.id, data);
        urlAdapter.bindTo(uv);

        if (uvConfig.configUri) {
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
