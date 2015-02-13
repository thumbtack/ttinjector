# Injector

[![Build Status](https://travis-ci.org/thumbtack/ttinjector.svg?branch=master)](https://travis-ci.org/thumbtack/ttinjector)

This is a simple dependency injector.

It works in two stages. First, you register all the dependencies and describe how they relate to
each other. Then, you build an injector out of those dependencies that can inject the values you
have added.

## How to use the injector:

### 1. Create a Dependencies object:

```php
$dependencies = new Dependencies();
```

### 2. Add some dependencies:

```php
// Use register_value() to add constant values.
$dependencies->register_value('current_user_id', 12345);

// Use register_factory() to add things you don't want to construct unless used...
$dependencies->register_factory('db_connection', [], function() {
   return Database::Connect();
});

// ...Or things that have dependencies of their own.
$dependencies->register_factory(
   'users_source'
   ['db_connection'],
   function ($db) {
       return function($user_id) use ($db) {
          return $db->query('users')->where_equals('user_id', $user_id);
       };
   }
);
```

### 3. Build the injector:

```php
$injector = $dependencies->build_injector();
```

The `build_injector()` function will check to make sure there aren't any errors in the dependencies
you've set up (missing dependencies or dependency cycles).

Injector objects are immutable.

### 4. Inject your dependencies!

```php
function current_user($current_user_id, $users_source) {
   return $users_source($current_user_id);
}

$user = $injector->inject('current_user', ['current_user_id', 'users_source']);
```
