# rodrixcornell/apigenerate
API Rest generator for Laravel Framework.
Create your api resource in seconds using only your database table name or connection name.

## Installation

```composer.json
  "require-dev": {
    ...
    "rodrixcornell/apigenerate": "dev-master",
    ...
  },
  "repositories": [{
    "type": "vcs",
    "url": "https://github.com/rodrixcornell/laravel-api-generate.git"
  }],
```

```bash
composer update
```

```bash
composer require --dev rodrixcornell/apigenerate:dev-master
```

Install the service provider:

```php
// app/Providers/AppServiceProvider.php
public function register()
{
	if ($this->app->environment() !== 'production') {
		$this->app->register(\Rodrixcornell\ApiGenerate\ApiGenerateServiceProvider::class);
	}
}
```

## Usage

### Creating a new resource

To create all API Rest resources run this command (Only postgres and mysql):
```php
php artisan generate:api --con=conection_name
```

To create a new api resource run this command:
```php
php artisan generate:api --table=table_name --relation=true
```
You can see result in http://localhost:8000/api/table_name
You can find your new resource in app/Modules/General.
General is the default
### Params

To define route:

```php
php artisan generate:api --table=table_name --route=my-custom-route --relation=true
```

To define module name:

```php
php artisan generate:api --table=table_name --route=my-custom-route --module=Exemple --relation=true
```
You can find your new resource in app/Modules/Exemple.

## Postcardware

You are free to use this package as it's [MIT-licensed](LICENSE.md)
