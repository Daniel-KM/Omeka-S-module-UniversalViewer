'use strict';

// Prepare multiple uv4.
document.addEventListener('DOMContentLoaded', function (event) {

    // The config is defined inside the html.
    if (typeof uvConfigs === 'undefined') {
        return;
    }

    uvConfigs.forEach(function (uvConfig) {
        var uv;
        const urlAdapter = new UV.IIIFURLAdapter(true);

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
            options: uvConfig.options ? uvConfig.options : {},
            modules: uvConfig.modules ? uvConfig.modules : {},
            locales: uvConfig.locales ? uvConfig.locales : [],
        };

        // Deprecated.
        if (uvConfig.configUri && uvConfig.configUri.length) {
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
           // Function to merge object recursively (array_merge()).
           function arrayMerge(obj1, obj2) {
               const result = { ...obj1 };
               for (const key in obj2) {
                   if (obj2.hasOwnProperty(key)) {
                       if (typeof obj2[key] === 'object' && obj2[key] !== null && !Array.isArray(obj2[key])) {
                           result[key] = arrayMerge(result[key] || {}, obj2[key]);
                       } else {
                           result[key] = obj2[key];
                       }
                   }
               }
               return result;
           }

            // Merge data and options
            var data = urlAdapter.getInitialData(params);
            data = arrayMerge(params, data);
            /*
            // For internal locales, only the name is needed.
            // The first is the default locale. The others are optional.
            // Default locales are always included.
            data.locales = [
                {
                    name: 'fr-FR',
                    // label: 'Français',
                    // path: 'path/to/french/locale/config.json',
                },
            ];
            */
            uv = UV.init(uvConfig.id, data);
            urlAdapter.bindTo(uv);
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
