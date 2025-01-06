# Dynamic Search | Index Provider: OpenSearch

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Software License](https://img.shields.io/badge/license-DCL-white.svg?style=flat-square&color=%23ff5c5c)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/dynamic-search-index-provider-opensearch.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/dynamic-search-index-provider-opensearch)
[![Codeception](https://github.com/dachcom-digital/pimcore-dynamic-search-index-provider-opensearch/actions/workflows/codeception.yml/badge.svg?branch=main)](https://github.com/dachcom-digital/pimcore-dynamic-search-index-provider-opensearch/actions/workflows/codeception.yml)
[![PHP Stan](https://github.com/dachcom-digital/pimcore-dynamic-search-index-provider-opensearch/actions/workflows/php-stan.yml/badge.svg?branch=main)](https://github.com/dachcom-digital/pimcore-dynamic-search-index-provider-opensearch/actions/workflows/php-stan.yml)

An index storage extension for [Pimcore Dynamic Search](https://github.com/dachcom-digital/pimcore-dynamic-search).
Store data with the opensearch index service.

## Release Plan
| Release | Supported Pimcore Versions | Supported Symfony Versions | Release Date | Maintained     | Branch |
|---------|----------------------------|----------------------------|--------------|----------------|--------|
| **2.x** | `11.0`                     | `^6.4`                     | 28.09.2023   | Feature Branch | master |
| **1.x** | `10.0` - `10.6`            | `^5.4`                     | 09.03.2023   | No             | 1.x    |

***

## Installation
```json
"require" : {
    "dachcom-digital/dynamic-search" : "~3.0.0",
    "dachcom-digital/dynamic-search-index-provider-opensearch" : "~2.0.0"
}
```

### Dynamic Search Bundle
You need to install / enable the Dynamic Search Bundle first.
Read more about it [here](https://github.com/dachcom-digital/pimcore-dynamic-search#installation).
After that, proceed as followed:

Add Bundle to `bundles.php`:
```php
<?php

return [
    \DsOpenSearchBundle\DsOpenSearchBundle::class => ['all' => true],
];
```

***

## Basic Setup

```yaml
dynamic_search:
    enable_pimcore_element_listener: true
    context:
        default:
            index_provider:
                service: 'opensearch'
                options:
                    index:
                        identifier: 'default'
                        hosts:
                            - 'opensearch:9200'
                        settings: []
                        credentials: # optional, empty array
                            username: '%OS_USERNAME%'
                            password: '%OS_PASSWORD%'
                    analysis:
                        analyzer:
                            keyword_analyzer:
                                tokenizer: keyword
                                type: custom
                                filter:
                                    - lowercase
                                    - asciifolding
                                    - trim
                                char_filter: []
                            edge_ngram_analyzer:
                                tokenizer: edge_ngram_tokenizer
                                filter:
                                    - lowercase
                            edge_ngram_search_analyzer:
                                tokenizer: lowercase
                        tokenizer:
                            edge_ngram_tokenizer:
                                type: edge_ngram
                                min_gram: 2
                                max_gram: 5
                                token_chars:
                                    - letter
            output_channels:
                suggestions:
                    service: 'opensearch_search'
                    normalizer:
                        service: 'os_document_raw_normalizer'
                    paginator:
                        enabled: false
                search:
                    service: 'opensearch_search'
                    use_frontend_controller: true
                    options:
                        result_limit: 10
                    normalizer:
                        service: 'os_document_source_normalizer'
                    paginator:
                        enabled: true
                        max_per_page: 10
```

***

## Provider Options

| Name       | Default Value | Description |
|:-----------|:--------------|:------------|
| `index`    | []            |             |
| `analysis` | []            |             |

***

## Index Fields
**Available Index Fields**:

| Name       | Description |
|:-----------|:------------|
| `dynamic`  | TBD         |
| `explicit` | TBD         |

***

## Output Channel Services

### Search
This channel service just creates a simple DSL search class.
You're able to modify the search by hooking via `dynamic_search.output_channel.modifier.action` into the `post_query_build` action.

**Identifier**: `opensearch_search`   
**Available Options**:

| Name           | Default Value | Description |
|:---------------|:--------------|:------------|
| `result_limit` | 10            |             |

### Multi Search
**Identifier**: `TBD`   
**Available Options**: none

***

## Filter
TBD

## Output Normalizer
A Output Normalizer can be defined for each output channel.

### os_document_raw_normalizer
Use this normalizer to get the untouched opensearch response.

**Available Options**:   
Currently none

### os_document_source_normalizer
Use this normalizer to get all document values (`_source`) stored in `response.hits.hits[]`

**Available Options**:   
Currently none

***

## Commands

### Rebuild Index Mapping
Use this command to rebuild a index by passing your context name with argument `-c`

> **Attention!** By executing this command, the index gets removed and all data will be lost!

```bash
$  bin/console dynamic-search:os:rebuild-index -c default
```

***

## Upgrade Info
Before updating, please [check our upgrade notes!](./UPGRADE.md)  

## License
**DACHCOM.DIGITAL AG**, Löwenhofstrasse 15, 9424 Rheineck, Schweiz  
[dachcom.com](https://www.dachcom.com), dcdi@dachcom.ch  
Copyright © 2025 DACHCOM.DIGITAL. All rights reserved.  

For licensing details please visit [LICENSE.md](LICENSE.md)  
