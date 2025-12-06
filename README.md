# TYPO3 Extension podigee

## 1 Features

* Podigee podcasts can be created as a file in the TYPO3 file list
* Podigee podcasts can be used and output with the text with media element
* Update metadata via command

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your [Composer][1] based TYPO3 project:

```
composer require ayacoo/podigee
```

### 2.2 TypoScript settings

#### Privacy

With `plugin.tx_podigee.settings.privacy = 1` you can ensure that the IFrame is
built with data-src instead of src. If you need more options to influence the HTML, you can
use a PSR-14 event.

## 3 Developer Corner

### 3.1 ModifyPodigeeOutputEvent

If you want to modify the output of the Podigee HTML, you can use
the `ModifyPodigeeOutputEvent`.

##### EventListener registration

In your extension, extend `Configuration/Services.yaml` once:

```yaml
Vendor\ExtName\EventListener\PodigeeOutputEventListener:
    tags:
        -   name: event.listener
            identifier: 'podigee/output'
            event: Ayacoo\Podigee\Event\ModifyPodigeeOutputEvent
```

```php
<?php

namespace Vendor\ExtName\EventListener;

use Ayacoo\Podigee\Event\ModifyPodigeeOutputEvent;

class PodigeeOutputEventListener
{
    public function __invoke(ModifyPodigeeOutputEvent $event): void
    {
        $output = $event->getOutput();
        $output = str_replace('src', 'data-src', $output);
        $event->setOutput($output);
    }
}
```

### 3.2 Backend Preview

In the backend, the preview is used by TextMediaRenderer. For online media, this
only displays the provider's icon, in this case podigee. If you want to display
the thumbnail, for example, you need your own renderer that overwrites
Textmedia. An example renderer is available in the project. Caution: This
overwrites all text media elements, so only use this renderer as a basis.

You register a renderer in the TCA `Configuration/TCA/Overrides/tt_content.php`
with `$GLOBALS['TCA']['tt_content']['types']['textmedia']['previewRenderer'] = \Ayacoo\Podigee\Rendering\PodigeePreviewRenderer::class;`

Documentation: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/ContentElements/CustomBackendPreview.html

### 3.3 Content security policy

If CSP is activated in the backend, policies will be automatically added.
To do this, the file Configuration/ContentSecurityPolicies.php is used.

If CSP is to be extended for the frontend, the configuration can be added
in a site package extension or in the global csp.yml

Take a look at the current documentation:
https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/ContentSecurityPolicy/Index.html

## 4 Administration corner

### 4.1 Versions and support

| Podigee | TYPO3 | PHP       | Support / Development                |
|---------|-------|-----------|--------------------------------------|
| 1.x     | 13.x  | 8.2 - 8.5 | features, bugfixes, security updates |

### 4.2 Release Management

podigee uses [**semantic versioning**][2], which means, that

* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or
  security relevant stuff without breaking
  changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller
  tasks without breaking changes,
* and **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes which can be
  refactorings, features or bugfixes.

### 4.3 Contribution

**Pull Requests** are gladly welcome! Nevertheless please don't forget to add an
issue and connect it to your pull
requests. This
is very helpful to understand what kind of issue the **PR** is going to solve.

**Bugfixes**: Please describe what kind of bug your fix solve and give us
feedback how to reproduce the issue. We're
going
to accept only bugfixes if we can reproduce the issue.

## 5 Thanks / Notices

- Special thanks to Georg Ringer and his [news][3] extension. A good template to
  build a TYPO3 extension. Here, for example, the structure of README.md is
  used.
- Thanks also to b13 for the [online-media-updater][4] extension. Parts of it
  were allowed to be included in this extension.

[1]: https://getcomposer.org/

[2]: https://semver.org/

[3]: https://github.com/georgringer/news

[4]: https://github.com/b13/online-media-updater

## 6 Support

If you are happy with the extension and would like to support it in any way, I
would appreciate the support of social institutions.
