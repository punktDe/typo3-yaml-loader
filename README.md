# Introduction
This package was created as a proof-of-concept for content-type and doktype configuration via YAML files instead of the complicated process currently required by the TYPO3 Core
# Setup
## Install
Install this package
> composer req punktde/typo3-yaml-loader
> 
 
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
 

## Add Page Type

### Create YAML Config

YAML configuration files are located at `Configuration/Doktypes`

The configuration _must_ contain the following keys:
```
default_page:
  icons: 
    default: string (EXT:FILE...) 
    hidden: string (EXT:FILE...)
  title: string (LLL:EXT:...)
  backendLayout:
    doktype: int
    rowCount: int
    colCount: int
    
    // If you want to configure the backend-layout add a configuration:
    rows:
      1:
        columns:
          1:
            name: string (LLL:EXT...)
            colPos: int
            
            // Optional: restrict available content-element types for this column:
            allowed/disallowed:
              CType: text,textmedia (etc.)
            maxItems: int
```

### Create Template

Page-templates are located at `Resources/Private/Templates/Page`

Template paths are derived from the page-type-identifier as defined top-level in the YAML configuration *(UpperCamelCase)*.
> Note: default_page renders Resources/Private/Templates/Page/DefaultPage.html

Make sure to include the Default-Layout by adding `<f:layout name="Default" />` to your template. Your content will be rendered from within the section Main.

Example:
```
<f:layout name="Default" />

<f:section name="Main">
    <!-- Your content here -->
</f:section>
```

## Add a content type

### Create YAML Config
YAML configuration files are located at `Configuration/ContentTypes`

The configuration _must_ contain the following keys:

```
example:
  iconIdentifier: content-text
  title: LLL:EXT:your_site_extension_key/Resources/Private/Language/Backend.Content.xlf:tt_content.example.title
  description: LLL:EXT:your_site_extension_key/Resources/Private/Language/Backend.Content.xlf:tt_content.example.description
```
### Create Template
Content-templates are located at `Resources/Private/Templates/Content`

Make sure to include the Default-Layout by adding `<f:layout name="Default(WithHeader)" />` to your template. Your content will be rendered from within the section Main.

### Rendering Components
Components can be created by utilizing the methods provided by sitegeist/fluid-components.

Documentation can be found here:

> https://github.com/sitegeist/fluid-components/blob/3.6.0/Documentation/DataStructures.md

# Acknowledgments

The `ArrayToTyposcriptConverter` class is heavily inspired by this gist by Armin Vieweg:
>https://gist.github.com/a-r-m-i-n/442693801bab280e42b7
