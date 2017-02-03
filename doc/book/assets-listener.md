# Assets listener

Allow to use assets (css, js etc.) from modules, or other not public folders

Web server has not access to module folder directly, but this is possible through `AssetsListener`.
`Zend\View\Helper\Assets` helper can build link to module folder (through Router),
`AssetsListener` can detect this link (through Router too) and route it to module name and asset.
When `AssetsListener` detected asset link, it caching module asset to public folder and 
browser can request this asset directly from public folder (it can be disabled).
Also `AssetsListener` can filter assets content, as sample .less to .css.

## Basic Usage

Configure template resolver
```php
// Add temptate resolver for assets in config file
'assets_manager' => [
    'template_resolver' => [
        'prefix_resolver' => [
            'ModuleName::' => __DIR__ . '\ModuleNameDir\assets'
        ],
    ],
],
```

Add asset in layout
```php
<?php
// in layout add asset for module:
$this->assets()->add('ModuleName::foo.css');
echo $this->assets();
?>
```

Layout Output:
```html
<link href="/assets-cache/prefix-ModuleTwo/foo.css" type="text/css">
```