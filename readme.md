# Settings for Temply

## Instructions

1. Install Package
	```php
	composer require infinety-es/temply-settings
	```

2. Config temply.php should have this config:

	```php
    'settings'      => [
        'website_name' => [
            'name'  => 'Website Name',
            'help'  => 'Your Website name',
            'type'  => 'text',
            'rules' => 'required',
        ],
        ...
    ];
	```

3. Add a Listener in EventServiceProvider.php
	```php
	'Infinety\TemplyHome\Http\Events\ActivateWebsite' => [
        'App\Listeners\YourListener',
    ],
    ```
