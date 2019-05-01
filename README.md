<p align="center">
    <img title="Laravel Zero" height="100" src="https://raw.githubusercontent.com/laravel-zero/docs/master/images/logo/laravel-zero-readme.png" />
</p>

------

## Documentation

For full documentation, visit [laravel-zero.com](https://laravel-zero.com/).

## Instructions

Clone repository to your local environment and run:

``` composer install ```

After installing your dependecies set your .env

``` cp .env.example .env ```

Set your Open Weather API key

``` OPENWEATHER_KEY=your_api_key ```

You can manually set units in config/openweather.php

``` 
return [
    'api_key' => env('OPENWEATHER_KEY', null),
    'units' => 'metric'
];
```

Run:

``` php weather3000 weather ```

choose option and enter city name, or latitude and longitude.

To rename your application run

``` php app:rename <new_app_name> ```

