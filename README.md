# Introduction
This package was created as a proof-of-concept for content-type and doktype configuration via YAML files instead of the complicated process currently required by the TYPO3 Core
# Setup
## Install
Install this package
> composer req punktde/typo3-yaml-loader
 
## Include configuration
Add the following lines to your `ext_tables.php`:
```
    $doktypeLoader = GeneralUtility::makeInstance(DoktypeLoader::class, 'your-site-extension-key');
    $doktypeLoader->loadPageTypes();
```

Add the following lines to your `ext_localconf.php`:
```
    $doktypeLoader = GeneralUtility::makeInstance(DoktypeLoader::class, 'your-site-extension-key');
    $doktypeLoader->loadPageTS();

    /** @var ContentTypeLoader $contentTypeLoader */
    $contentTypeLoader = GeneralUtility::makeInstance(ContentTypeLoader::class, 'your-site-extension-key');
    $contentTypeLoader->loadPageTS();
```

Add the following lines to your `Configuration/TCA/Overrides/pages.php`:
```
    $doktypeLoader = GeneralUtility::makeInstance(DoktypeLoader::class, 'your-site-extension-key');
    $doktypeLoader->loadTcaOverrides();
```
Add the following lines to your `Configuration/TCA/Overrides/tt_content.php`:
```
    $contentTypeLoader = GeneralUtility::makeInstance(ContentTypeLoader::class, 'your-site-extension-key');
    $contentTypeLoader->loadTcaOverrides();
```

> Please Note: This package does not contain any configuration for rendering of custom Content-Types or Doktypes. You will need to take care of registering rendering via Typoscript yourself.
> 
> For Content Elements see https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/ContentElements/AddingYourOwnContentElements.html#configure-the-frontend-rendering
>
> For doktypes you will need to configure your `page` object to choose the correct template, eg. based on backend_layout.
>
> Backend Layouts will be named as `pagets__{YOUR_IDENTIFIER}` by TYPO3. You can use this identifier to render the appropriate template. 
 

## Add a Content or Page Type

### Create YAML Config
YAML configuration files are located at `Configuration/(ContentTypes|Doktyps)/(Elements|Palettes)`

The yaml config will be validated on load, you can find the validation-rules in the following class:

```
  Classes/Validator/*Validator.php
```

# Acknowledgments

The `ArrayToTyposcriptConverter` class is heavily inspired by this gist by Armin Vieweg:
>https://gist.github.com/a-r-m-i-n/442693801bab280e42b7
