# Application Modules

Application modules group a feature such as a board, payment system, catalog, or notification system into one directory. The framework discovers direct child directories of `App/Modules` during application bootstrap.

## Directory convention

For a module named `payment`, create this structure:

```text
App/Modules/Payment/
|-- PaymentModule.php
|-- Configure/
|   `-- dependencies.php
|-- Contract/
|-- Controller/
|-- Service/
`-- Command/
```

`PaymentModule.php` must define `App\Modules\Payment\PaymentModule` and implement `ModuleInterface`. Extending `AbstractModule` provides conventional dependency, controller-route, and CLI-command loading.

## Enablement and dependency order

Configure module state in `App/Configure/modules.php`:

```php
return [
	'enabled' => null,
	'disabled' => ['payment'],
	'order' => ['board', 'comment'],
];
```

- `enabled: null` enables every discovered module.
- An `enabled` array acts as an explicit allowlist.
- `disabled` always overrides `enabled`.
- `order` supplies a preferred order, while declared dependencies always load first.
- Disabling a module that an enabled module requires stops bootstrap with a clear dependency error.

Route-cache metadata includes the enabled module list, so changing module state invalidates cached routes automatically.

## Cross-module calls

Expose only stable contracts from `getExportedServiceIdentifiers()`. Register each contract in the module's `Configure/dependencies.php`, then resolve it from another module through `ModuleManager`:

```php
$paymentGateway = $moduleManager->service('payment', PaymentGatewayInterface::class);
```

The manager rejects disabled, missing, unregistered, and non-exported services. Declare the providing module in `getDependencies()` so its services are registered first.
