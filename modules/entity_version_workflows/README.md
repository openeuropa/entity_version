# Entity Version Workflows

This module provides additional functionality that allows to control version numbers through workflow state transitions.

## Usage

The module introduces extra configuration options that allow to set for each available workflow transition if any
of the version numbers will increase, decrease or stay the same.

## API

It is possible to flag entities to bypass the configuration and prevent the version number from being altered
by setting the custom property "entity_version_no_update" to TRUE.

```
$entity->entity_version_no_update = TRUE;
```

## Configurations
Entity version settings under path "admin/config/entity-version/settings" have to be configured in order to
have the entity version workflows functionality fully operational.
