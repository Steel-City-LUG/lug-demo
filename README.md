# LUG Demo

Demo-only orchestration plugin for `lugtools.steelcitylug.com`.

This repository is intentionally separate from `lug-platform`. It should not be included in the downloadable platform connector or installed on partner LUG sites.

## Current scope

- Admin screen for seed/rebuild and reset.
- Batch-owned, idempotent demo accounts:
  - `demo` / `demo`
  - `demo1` / `demo1`
- Adapter contract for the other installed plugins.
- Quick-login URLs after seeding:
  - `?lug_demo_login=demo`
  - `?lug_demo_login=demo1`

## Adapter contract

Installed demo-capable plugins should register with `lug_demo_seed_adapters`:

```php
$adapters['events'] = array(
    'label' => 'Events demo data',
    'run'   => array( $this, 'seed_demo' ),
    'reset' => array( $this, 'reset_demo' ),
);
```

Adapters must create only records marked with `steel-city-lug-demo-v1` and must remove only those records during reset.
