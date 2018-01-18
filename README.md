## HCESrl/laravel-swagger-api

[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

The packages adds a layer on top of the Laravel routing system allowing you to easily add metadata to your routes
in order to create a [Swagger](https://swagger.io/) compliant API.

## Table of contents
- [Installation](#installation)
- [Basic usage](#basic-usage)
    - [Configuration](#configuration)
    - [Routing](#routing)
    - [Route parameters](#route-parameters)
    - [Responses](#responses)
- [Advanced configuration](#advanced-configuration)
    - [General route parameters](#general-route-parameters)
    - [Tags](#tags)
    - [Versions](#versions)
    - [Aggregated resources endpoint](#aggregated-resources-endpoint)
    - [API Json Caching](#api-json-caching)
- [Todos](#todos)


## Installation

Install the package:
```bash
composer require HCESrl/laravel-swagger-api
```

Publish configuration and assets
```bash
php artisan vendor:publish --provider=LaravelApi\\ServiceProvider
```

## Basic usage

### Configuration
The main configuration is located in the `config/api.php` file. Here you can set some general metadata for your API 
specification such as the title, the description, etc.

Make sure that the `prefix` is the same used in your `RouteServiceProvider` for the api routes.

### Routing
The `Api` facade works with the same syntax as the `Route` facade and you can use it directly in your
`routes/api.php` file.

```php
Api::get('some-uri', 'Controller@action');

Api::post('some-uri', function () {
    // do something
});
```

> **Note:** the supported methods: `get`, `post`, `put`, `delete`, `patch`, `options`. There's no support for the `match`
method because every route must be associated with a single Operation in the Swagger specification.

When you create a new route, the Api facade returns an instance of an [Operation](https://swagger.io/specification/#tagObject) 
object. This object exposes some chainable configuration methods. The following example shows an extensive use of those
methods.

```php
Api::get('some-uri', 'Controller@action')
    ->setSummary('My operation summary')
    ->setDescription('My operation description')
    ->addTag('some-tag')
    ->setOperationId('executeAction')
    ->setConsumes(['application/json'])
    ->setProduces(['application/json']);
```

### Route parameters
You can define different types of route parameters after creating a route through the following methods:
*  `addHeaderParameter`
*  `addQueryParameter`
*  `addPathParameter`
*  `addFormDataParameter`
*  `addBodyParameter`

All these methods accept 4 parameters: name, description, required and type:
```php
Api::post ( 'post-uri', 'Controller@action' )
    ->addHeaderParameter ( 'header-name', 'Some description.' )
    ->addQueryParameter ( 'query-name', 'Some description.', true, 'integer' )
    ->addPathParameter ( 'path-name', 'Some description.', true, 'string' )
    ->addFormDataParameter ( 'formdata-name', 'Some description.', true, 'string' )
    ->addBodyParameter ( 'param-name', 'Some description.', true );
```

> **Note:** the `addBodyParameter` method doesn't accept a `type` parameter, according to the Swagger specification.

If you need a deeper configuration for the parameter, you may pass a Closure function instead of a text description:
```php
Api::post ( 'post-uri-2', 'Controller@action' )
    ->addQueryParameter ( 'param-name', function ( $param ) {
        $param->setDescription ( 'Some param description' )
              ->setType('integer')
              ->setFormat('int32');
    } );
```

#### Route path parameter auto-parsing
When you define a route containg path parameters using the [Laravel syntax](https://laravel.com/docs/5.5/routing#route-parameters),
the route URI will be automatically parsed for path parameters, both required and optional.

The route:
```php
Api::get('some-uri/{param1}/{param2?}', 'Controller@action');
```

will be parsed and the two path parameters will be added automatically. You can still edit the paramaters configuration:

```php
Api::get('some-uri/{param1}/{param2?}', 'Controller@action')
    ->addPathParameter ( 'param1', function( $param ) {
        $param->setDescription ( 'Some description' );
     } )
    ->addPathParameter ( 'param2', 'Some other description.', false, 'integer' );
```

It is also possible to disable the automatic route parsing from the main config file `config/api.php` setting
`parse_route_parameters` to `false`.


#### Responses
Use the `addResponse` to define the route response types:

```php
Api::get ( 'some-uri', 'Controller@action' )
    ->addResponse ( 200, 'Successful operation' )
    ->addResponse ( 422, 'Validation error' );
```

## Advanced configuration

### General route parameters
You may need to register different routes using the same parameters (eg. lang or locale) and these could lead to a long
and difficult to maintein routes file.

To avoid this you can register general reusable route parameters that will be automatically applied when parameters with
the same are found in the route uris.

```php
Api::routeParameter ( 'locale' )
   ->setDescription ( 'The request locale.' )
   ->setRequired ( true )
   ->addEnum ( 'en' )
   ->addEnum ( 'it' );
   
Api::get ( '{locale}/some-uri', 'Controller@action' )
```

### Tags
In order to create a [tag](https://swagger.io/specification/#tagObject) for your operations, you may call the `tag` 
method passing the name of the tag and its description, if needed.

You can also pass a callback function to create a group of operations automatically tagged with the given tag.

```php
Api::tag('simple-tag');

Api::tag('tag-with-description', 'Tag description', function() {
    Api::get('tagged-uri', 'Controller@action');
});
```

You may register many tags at once by passing an array to the `tags` method:

```php
Api::tags(
    [
        'tag_1' => 'Some tag description',
        'tag_2' => 'Some other tag description'
    ]
);
```

### Versions
If you want to group your routes by different version, you may use the `version` method. The grouped routes will be
automatically prefixed and tagged with the given version name.

```php
/**
 * /api/v1/versioned-uri
 */
Api::tag('v1', function() {
    Api::get('versioned-uri', 'Controller@action');
});

/**
 * /api/v2/versioned-uri
 */
Api::tag('v2', function() {
    Api::get('versioned-uri', 'Controller@action');
});
```

### Aggregated resources endpoint
An aggregate endpoint is an API endpoint that returns a mixed collection of resources, combining both Eloquent models 
and data generated by Closures.

```php
Api::aggregate ( 'aggregate/uri', [
    'App\\Page',
    'App\\Post',
    'settings' => function ( SettingStore $settings ) {
        return $settings->all ();
    },
 ] );
```

> **Note:** closures require a non-numeric array key.


### API Json Caching
To generate a route cache, just execute the `api:cache` Artisan command:
```bash
php artisan api:cache
```

After running this command, your cached json file will be used. Remember, if you add any new routes to the API you will 
need to generate a fresh route cache. Because of this, you should only run the `api:cache` command during your 
project's deployment.

You may use the `api:clear` command to clear the API cache:
```bash
php artisan api:clear
```

## Todos
*  Add support for [Components](https://swagger.io/specification/#components-object-33);
*  Add support for response [Examples](https://swagger.io/specification/#example-object-78);
*  Create route parameters based on a [Laravel Form Request](https://laravel.com/docs/5.5/validation#form-request-validation);
*  Implement authentication through [Laravel Passport](https://laravel.com/docs/5.5/passport);

## License
This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).