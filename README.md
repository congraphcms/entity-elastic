# congraph/entity-elastic

`congraph/entity-elastic` provides OpenSearch-backed indexing and delivery for Congraph entities.

## Responsibilities

- indexing EAV entities into OpenSearch
- delivery queries backed by OpenSearch
- field formatting for searchable documents
- reindex workflows, including `IndexAllCommand`

## Installation

```bash
composer require congraph/entity-elastic
```

This package depends on:

- `core`
- `contracts`
- `filesystem`
- `locales`
- `workflows`
- `eav`

Laravel package provider:

- [EntityElasticServiceProvider.php](/Users/nikolap/git/np/congraph/packages/entity-elastic/EntityElasticServiceProvider.php)

## OpenSearch

This package is now aligned on OpenSearch rather than legacy Elasticsearch naming.

Recommended environment:

```dotenv
OPENSEARCH_HOSTS=127.0.0.1:9200
```

The monorepo local helper uses OpenSearch `3.5.0`.

## Indexing

One of the most important workflows is bulk reindexing:

- [IndexAllCommand.php](/Users/nikolap/git/np/congraph/packages/entity-elastic/Console/Commands/IndexAllCommand.php)

This command now has dedicated integration coverage for:

- multi-batch indexing
- node references created later than the source entity
- node collection references created later than the source entity

See:

- [IndexAllCommandTest.php](/Users/nikolap/git/np/congraph/packages/entity-elastic/tests/integration/IndexAllCommandTest.php)

## Development

### Install dependencies

```bash
cd /Users/nikolap/git/np/congraph/packages/entity-elastic
composer update -W
```

### Run tests

```bash
composer test
composer test:pgsql
composer test:opensearch
```

Focused OpenSearch example:

```bash
composer test:opensearch -- tests/integration/IndexAllCommandTest.php
```

## Contribution notes

- any delivery/indexing change should be verified with `composer test:opensearch`
- prefer OpenSearch-compatible request bodies and mappings
- do not assume MySQL-shaped source data when formatting indexed entities
- when changing bulk indexing or relation formatting, extend integration coverage
