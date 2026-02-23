Universal Viewer (module for Omeka S)
=====================================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__


[Universal Viewer] is a module for [Omeka S] that integrates [UniversalViewer],
a unified online player for any files, so it can display books, images, maps,
audio, movies, pdf, epub, 3D, youtuben and anything else as long as the
appropriate extension is installed. Rotation, zoom, inside search, etc. may be
managed too.

It uses the resources of any [IIIF] compliant server. The full specification of
the "International Image Interoperability Framework" standard is supported
(API v2 and API v3 level 2). If you don’t have an IIIF-compatible image server,
like [Cantaloupe] or [IIP Image] server, Omeka S can be one! Just install the
modules [IIIF Server] and [Image Server].

It’s an alternative to the [Mirador Viewer] or the lighter [Diva Viewer].

The Universal Viewer was firstly developed by [Digirati] for the [Wellcome Library],
the [British Library] and the [National Library of Wales], then open sourced
(unlike the viewer of [Gallica], the public digital library built by the [Bibliothèque Nationale de France],
based on Mirador, which is sold to its partners).

For an example, see [Collections de la Maison de Salins].


Installation
------------

See general end user documentation for [installing a module].

The module [Common] must be installed first.

The module uses an external library [UniversalViewer], so use the release zip to
install it, or use and init the source.

* From the zip

Download the last release [UniversalViewer.zip] from the list of releases (the
master does not contain the dependency), and uncompress it in the `modules`
directory.

* From the source and for development:

If the module was installed from the source, rename the name of the folder of
the module to `UniversalViewer`, and go to the root module, and run:

```sh
composer install --no-dev
```

Then install it like any other Omeka module.

* Compilation of Universal Viewer

The Universal Viewer is provided as a compressed file in order to be installed
quickly with composer. The compressed file is the vanilla version that is built
with default options.

So, you need to compile Universal Viewer only for development.

For v4, in a temp directory (replace `v4.2.1` with the desired version tag):

```sh
cd /tmp
git clone --branch v4.2.1 --depth 1 https://github.com/UniversalViewer/universalviewer
cd universalviewer
```

Apply the patch that adds extra locales (de-DE, ja-JP), registers them in all
extension configs, and removes the global jQuery shim that conflicts with other
modules. The patch file is stored in the `data/patches/` directory of this module:

```sh
patch -p1 < /path/to/modules/UniversalViewer/data/patches/uv-4.2.1.patch
```

Then build:

```sh
npm install
npm run build
```

Copy the content of the directory `dist` into `asset/vendor/uv` of the module
(remove the old `umd` directory first to avoid stale chunks):

```sh
rm -rf /path/to/modules/UniversalViewer/asset/vendor/uv/umd
cp -r dist/* /path/to/modules/UniversalViewer/asset/vendor/uv/
```

To prepare a tar for a release:

```sh
mv dist uv
tar -czvf uv-4.2.1.tar.gz -C /tmp/universalviewer ./uv
```

For v3, an [external repository] was used in order to include the last version
of OpenSeaDragon, the main component that manages the zoom viewer (used in other
IIIF viewers), in order to manage IIIF v3. Run this command inside the
repository of this external repository, then copy "dist" in directory "asset/vendor/uv3":

```sh
grunt build --dist
```

For v2, the "dist" directory was provided by default in the git repository.

* For test

The module includes a comprehensive test suite with unit and functional tests.
Run them from the root of Omeka:

```sh
vendor/bin/phpunit -c modules/UniversalViewer/phpunit.xml --testdox
```

* Access to IIIF images

UV viewer is based on IIIF, so an image server compliant with this protocol is
required to use it. So, install the module [Image Server] if needed.

If you need to display big images (bigger than 1 to 10 MB according to your
server, your network, and your users), use an external image server, or create
tiles with [Image Server]. The tiling means that big images like maps and deep
paintings, and any other images, are converted into tiles in order to load and
zoom them instantly.

* Access to 3D models

The display of 3D models is fully supported by the widget and natively managed
since the release 2.3. 3D models are managed via the [threejs] library.
Nevertheless, see the readme of the [module Three JS Model viewer] for some
possible additional requirements and the supported formats.


Usage
-----

### Version 2.0, 3.1 or 4.0

Three versions of the viewer are provided and can be selected in site settings:
version 2.0.2, version 3.1.1 (adapted for IIIF v3) and last version of series 4.

The first one manages pdf files quicker but supports only iiif v2 (`manifest`
for presentation and `info` for image), the second is more modern and the last
one is the up-to-date version.

### Configuration

The url of the manifest of the items should be set inside the property specified
in the config form of the module. If you don’t have an IIIF Server, install the
module [IIIF Server].

To config the universal viewer:

- in the json file "config.json" of UniversalViewer for the player itself: copy
  and update it in a folder named "universal-viewer" inside the folder "asset"
  of the theme;
- via the helper: to use an alternative config for some items, add an option
  `config` with its url in the array of arguments passed to the viewer (see
  below), or use a metadata in the field set in the IIIF server config form.

### Display

If the [IIIF Server] is installed, all resources of Omeka S are automatically
available by the viewer, else the url of the manifest should be set in the
configured property.

The viewer is always available at `http://www.example.com/item-set/{item-set id}/uv`
and `http://www.example.com/item/{item id}/uv`.

Furthermore, it is automatically embedded in "item-set/{id}" and "item/{id}"
show and/or browse pages.  This can be disabled via the module [Blocks Disposition]
for each site.

In Omeka S v4, you can use the block in the resource page theme options. Note
that when this new feature is used, the option in module [Blocks Disposition] is
automatically skipped.

Finally, a block layout is available to add the viewer in any standard page.

To embed the Universal Viewer somewhere else, just use the helper:

```php
// Display the viewer with the specified item set.
echo $this->universalViewer($itemSet);

// Display the viewer with the specified item and specified options.
// The options for UV are directly passed to the partial, so they are
// available in the theme and set for the viewer.
echo $this->universalViewer($item, $options);

// Display multiple resources (items and/or item sets).
echo $this->universalViewer($resources);
```

### Exemple of full config for version 4.

See the [Universal Viewer examples], then choose "Config example", that
redirects to a sandbox on [codesandbox.io], where the full config is available
in the file "uv-config.json". Example to set the French theme with a specific
option for the left panel:

```json
{
    "options": {
        "theme": "uv-fr-FR-theme"
    },
    "modules": {
        "contentLeftPanel": {
            "options": {
                "autoExpandTreeEnabled": true
            }
        }
    },
    "locales": [
        {
            "name": "fr-FR"
        }
    ]
}
```

Internal locales are cy-GB, en-GB,fr-FR, pl-PL and sv-SE. They are always
included. The default locale is the locale of the site if it is in this list. To
set the default locale, set it as first object in array "locales" with keys
name, label and path. Only the name is required for internal locales.


Notes
-----

- If an item has no file, the viewer is not able to display it, so a check is
  automatically done.
- Media: Currently, no image should be available in the same item.
- Audio/Video: the format should be supported by the browser of the user. In
  fact, only open, free and/or common codecs are really supported: "mp3" and
  "ogg" for audio and "webm" and "ogv" for video. They can be modified in the
  file "routes.ini".
- The Universal Viewer cannot display empty item sets, so an empty view may
  appear when multiple resources are displayed.


Bugs
----

- When an item set contains non image items, the left panel with the index is
  displayed only when the first item contains an image.


TODO
----

- [x] Improve integration of pdf for big scanned files in last version in order to use it in any version, not only v2.0.2 (fixed in v4).
- [x] Integrate json config inside site settings.
- [ ] Remove dependency to IiifServer for block.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

### PDF does not fit in fullscreen

By default, UV4 uses pdf.js to render PDF documents with a fixed scale (0.7),
which does not adapt to the viewer size or fullscreen. To use the browser's
native PDF viewer instead (which handles fit-to-screen automatically), add this
JSON in the settings (Admin > Settings > Universal Viewer > Config as json for
v4) or in the site settings:

```json
{
    "modules": {
        "pdfCenterPanel": {
            "options": {
                "usePdfJs": false
            }
        }
    }
}
```

### Theme CSS conflicts with UV4 buttons

UV4 renders inline in the DOM (not in an iframe), so theme global CSS selectors
like `button` may cascade into the viewer and alter its appearance. The module
includes a CSS fix (`all: revert`) for common elements. If buttons still look
wrong (colored background, oversized), check your theme's global button styles.

### jQuery UI conflict with other modules

UV4 no longer exposes jQuery globally (fixed in 3.6.12). If you use an older
version, the global `window.$` set by UV may conflict with other modules that
rely on jQuery UI widgets (accordion, tabs, etc.).

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.

The [UniversalViewer] is published under the [MIT licence].

See documentation on the UniversalViewer and the IIIF on their respective site.


Copyright
---------

Player [UniversalViewer]:

* Copyright Wellcome Library, 2013
* Copyright British Library, 2015-2017
* Copyright National Library of Wales, 2015-2017
* Copyright [Edward Silverton] 2013-2023

Module Universal Viewer for Omeka S:

* Copyright Daniel Berthereau, 2015-2025 (see [Daniel-KM])
* Copyright BibLibre, 2016-2017

First version of this module was built for [Mines ParisTech].

This [Omeka S] module is a rewrite of the [Universal Viewer plugin for Omeka] by
[BibLibre] with the same features as the original plugin. Next, it was separated
into three modules, the IIIF server, the Image server and the player Universal Viewer.

See a [demo] on the [Bibliothèque patrimoniale] of [Mines ParisTech], or you can
set the url "https://patrimoine.mines-paristech.fr/iiif/collection/7"
in the official [example server], because this is fully interoperable.

For Omeka S: example on [Collections de la Maison de Salins].


[Universal Viewer]: https://gitlab.com/Daniel-KM/Omeka-S-module-UniversalViewer
[Omeka S]: https://omeka.org/s
[Omeka]: https://omeka.org
[IIIF Server]: https://gitlab.com/Daniel-KM/Omeka-S-module-IiifServer
[Image Server]: https://gitlab.com/Daniel-KM/Omeka-S-module-ImageServer
[Mirador Viewer]: https://gitlab.com/Daniel-KM/Omeka-S-module-Mirador
[Diva Viewer]: https://gitlab.com/Daniel-KM/Omeka-S-module-Diva
[IIIF]: http://iiif.io
[Cantaloupe]: https://cantaloupe-project.github.io
[IIP Image]: http://iipimage.sourceforge.net
[UniversalViewer]: https://github.com/UniversalViewer/universalviewer
[Digirati]: http://digirati.co.uk
[British Library]: http://bl.uk
[National Library of Wales]: http://www.llgc.org.uk
[Gallica]: http://gallica.bnf.fr
[Bibliothèque Nationale de France]: http://bnf.fr
[Wellcome Library]: http://wellcomelibrary.org
[Universal Viewer plugin for Omeka]: https://gitlab.com/Daniel-KM/Omeka-plugin-UniversalViewer
[BibLibre]: https://github.com/biblibre
[demo]: https://patrimoine.mines-paristech.fr/collections/play/7
[Bibliothèque patrimoniale]: https://patrimoine.mines-paristech.fr
[Collections de la Maison de Salins]: https://collections.maison-salins.fr/s/patrimoine/item/1638
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[example server]: http://universalviewer.io/examples/
[UniversalViewer.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-UniversalViewer/-/releases
[external repository]: https://gitlab.com/Daniel-KM/UniversalViewer
[Upgrade to Omeka S]: https://gitlab.com/Daniel-KM/Omeka-S-module-UpgradeToOmekaS
[wiki]: https://github.com/UniversalViewer/universalviewer/wiki/Configuration
[online]: http://universalviewer.io/examples/
[iiif specifications]: http://iiif.io/api/
[OpenLayersZoom]: https://gitlab.com/Daniel-KM/Omeka-S-module-OpenLayersZoom
[Blocks Disposition]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlocksDisposition
[Universal Viewer examples]: https://github.com/UniversalViewer/universalviewer/wiki/UV-Examples
[codesandbox.io]: https://codesandbox.io/s/uv-config-example-7kh4s?file=/uv-config.json
[module IIIF Server]: https://gitlab.com/Daniel-KM/Omeka-S-module-IiifServer#3d-models
[threejs]: https://threejs.org
[module Three JS Model viewer]: https://gitlab.com/Daniel-KM/Omeka-S-module-ThreeJs
[Archive Repertory]: https://gitlab.com/Daniel-KM/Omeka-S-module-ArchiveRepertory
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-UniversalViewer/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT licence]: https://github.com/UniversalViewer/universalviewer/blob/master/LICENSE.txt
[Edward Silverton]: https://github.com/edsilv
[GitLab]: https://gitlab.com/Daniel-KM
[Mines ParisTech]: http://mines-paristech.fr
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
