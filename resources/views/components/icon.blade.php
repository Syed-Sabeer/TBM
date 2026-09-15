@props(['name', 'class' => null])

{{ \App\Support\Icons::get($name, $class) }}
