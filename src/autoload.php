<?php
//todo use at composer.json and register/unregister if was registered before
spl_autoload_register(function ($class) {
    $class = ltrim('\\', $class);
    print $class;
    if (strpos($class, 'beatrix\\') === 0) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        require $path;
    } else {
        return false;
    }
}, true, true);
