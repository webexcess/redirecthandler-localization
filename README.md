# WebExcess.RedirectHandler.Localization for Neos CMS
[![Logo](logo.png)](Documentation/logo-512.png)
[![Latest Stable Version](https://poser.pugx.org/webexcess/redirecthandler-localization/v/stable)](https://packagist.org/packages/webexcess/redirecthandler-localization)
[![License](https://poser.pugx.org/webexcess/redirecthandler-localization/license)](https://packagist.org/packages/webexcess/redirecthandler-localization)

This package allows to create [Neos.RedirectHandler](https://github.com/neos/redirecthandler) shortcuts which gets redirected to the matching language and region dimension of the visitor.

## Installation
```
composer require webexcess/redirecthandler-localization
```

## Configuration
- **dimensionIdentifiers.linguistic** (string)
  - The key of your linguistic dimension, default: 'language'
- **dimensionIdentifiers.regional** (string)
  - The key of your regional dimension, default: not set
- **overwriteDefaultDimensionUriSegment** (array)
  - A list of "dimensionIdentifier: defaultDimensionUriSegment": default: empty
  - Use this to overwrite the default uri segment per dimension
  - Example: `overwriteDefaultDimensionUriSegment.language: 'en'`
- **ipinfoToken** (string)
  - The token to the ipinfo.io webservice, default: '' (for non-commerical use)
  - Add [your own token](https://ipinfo.io/) if you need more than 1,000 requests a day
  - Disable the token, to use the pecl geoip
  - Or implement your very own implementation of the `LocalizationInterface`

## Usage
Instead of adding a speaking url to "target uri path", add the node identifier like: `node://ad798967-8662-4c6f-b1d1-4c8188038d23`.
```
flow redirect:add source node://ad798967-8662-4c6f-b1d1-4c8188038d23 302
```
Or use the [WebExcess.RedirectHandler.Backend](https://github.com/webexcess/redirecthandler-backend) package.

## Simple Example

A site with language dimensions.

### Existing dimension configuration

```
Neos:
  ContentRepository:
    contentDimensions:
      language:
        label: Language
        icon: icon-language
        default: en
        defaultPreset: en
        presets:
          de:
            label: German
            values:
              - de
              - en
            uriSegment: de
          fr:
            label: French
            values:
              - fr
              - en
            uriSegment: fr
          it:
            label: Italian
            values:
              - it
              - en
            uriSegment: it
```

### RedirectHandler.Localization configuration

No configuration needed.


## Advanced Example

A site with language and country dimensions which is using ipinfo.io to geolocate the visitors ip address.

### Existing dimension configuration

```
Neos:
  ContentRepository:
    contentDimensions:
      language:
        label: Language
        icon: icon-language
        default: en
        defaultPreset: en
        presets:
          de:
            label: German
            values:
              - de
              - en
            uriSegment: de
          fr:
            label: French
            values:
              - fr
              - en
            uriSegment: fr
          it:
            label: Italian
            values:
              - it
              - en
            uriSegment: it
          # ...
          en:
            label: English
            values:
              - en
            uriSegment: en
      country:
        label: Country
        icon: icon-globe
        default: GLOBAL
        defaultPreset: GLOBAL
        presets:
          GLOBAL:
            label: Global
            values:
              - GLOBAL
            uriSegment: ''
          CH:
            label: Schweiz
            values:
              - CH
              - GLOBAL
            uriSegment: CH
            constraints:
              language:
                '*': false
                de: true
                fr: true
                it: true
                en: true
          DE:
            label: Deutschland
            values:
              - DE
              - GLOBAL
            uriSegment: DE
            constraints:
              language:
                '*': false
                de: true
                en: true
          # ...
          ZA:
            label: 'South Africa'
            values:
              - ZA
              - GLOBAL
            uriSegment: ZA
            constraints:
              language:
                '*': false
                en: true
```

### RedirectHandler.Localization configuration

_Objects.yaml_
```
WebExcess\RedirectHandler\Localization\Service\LocalizationInterface:
  className: WebExcess\RedirectHandler\Localization\Service\LanguageAndCountryLocalization
```

_Settings.yaml_
```
WebExcess:
  RedirectHandler:
    Localization:
      dimensionIdentifiers:
        linguistic: 'language'
        regional: 'country'
      overwriteDefaultDimensionUriSegment:
        country: 'CH'
      ipinfoToken: '1234567890'
```

## Custom Example

You can add your own LocalizationInterface implementation.

All you have to return in the getTargetContext function is a valid context array, for example:
```
Array
(
    [dimensions] => Array
        (
            [language] => Array
                (
                    [0] => de
                    [1] => en
                )

            [country] => Array
                (
                    [0] => CH
                    [1] => GLOBAL
                )

        )

    [targetDimensions] => Array
        (
            [language] => de
            [country] => CH
        )

)
```

------------------------------------------

developed by [webexcess GmbH](https://webexcess.ch/)

sponsored by [Blaser Swisslube AG](https://www.blaser.com/)
