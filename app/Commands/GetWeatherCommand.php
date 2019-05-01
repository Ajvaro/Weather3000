<?php

namespace App\Commands;

use function GuzzleHttp\Psr7\str;
use Illuminate\Support\Facades\Log;
use Zttp\Zttp;
use Zttp\ZttpRequest;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class GetWeatherCommand extends Command
{
    private $option;
    private $city;
    private $lat;
    private $lng;

    const BASE_URL = 'https://api.openweathermap.org/data/2.5/weather';
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'weather {lat?} {lng?} {city?} {name=Artisan}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Shows weather for Nis';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->option = $this->choice('Search by:', ['city', 'coordinates']);

       $response = ($this->option == 'coordinates'
            ? $this->searchByCoordinates()
            : $this->searchByCity());

       if($response) {
           [$headers, $rows] = $this->getTablePayload(json_decode($response, true));
           $this->info("Hello there! Your weather report is ready:");
           $this->table($headers, $rows);
       }
    }

    /**
     * @param array $response
     * @return array
     */
    private function getTablePayload(array $response)
    {
        $headers = ['Information', 'Value'];
        $todayWeather = $this->transformResponse($response);
        $rows = collect($todayWeather)->map(function ($value, $title) {
            return ['Information' => $title, 'Value' => $value];
        })->toArray();

        return [$headers, $rows];
    }

    /**
     * @param array $response
     * @return \Illuminate\Support\Collection
     */
    private function transformResponse(array $response): \Illuminate\Support\Collection
    {
        return collect([
            'city' => $response['name'],
            'weather' => $response['weather'][0]['main'],
            'details' => $response['weather'][0]['description'],
            'temperature' => (int) $response['main']['temp'] . 'C',
            'pressure' => $response['main']['pressure'] . ' mbar',
            'humidity' => $response['main']['humidity'] . '%',
            'min' => (int) $response['main']['temp_min']. 'C',
            'max' => (int) $response['main']['temp_max']. 'C',
            'wind' => $response['wind']['speed'] . 'm/s ' . (array_key_exists('deg', $response['wind']) ? $this->convertWindToCardinals((int) $response['wind']['deg']) : '')
        ]);
    }

    /**
     * @param int $direction
     * @return string
     */
    private function convertWindToCardinals(int $direction): string
    {
        $cardinals = ["N", "NE", "E", "SE", "S", "SW", "W", "NW", "N"];
        return $cardinals[(int) round(($direction % 360) / 45)];
    }

    /**
     *
     */
    private function searchByCoordinates()
    {
        $this->setLatitude();
        $this->setLongitude();

        $response = Zttp::get(
            self::BASE_URL . "?lat=" . $this->lat . "&lon=" . $this->lng . "&units=". config('openweather.units') ."&appid=" . config('openweather.api_key')
        );

        $this->setResponse($response);
    }

    /**
     *
     */
    private function searchByCity()
    {
        $this->setCity();

        $response = Zttp::get(
            self::BASE_URL . "?q=" . $this->city . "&units=" . config('openweather.units') . "&appid=" . config('openweather.api_key')
        );

        $this->setResponse($response);
    }

    /**
     *
     */
    private function setLatitude()
    {
        $this->lat = (float) $this->argument('lat');

        if(! $this->argument('lat')) {
            $this->lat = $this->ask('Please enter latitude');
        }
    }

    /**
     *
     */
    private function setLongitude()
    {
        $this->lng = (float) $this->argument('lng');

        if(! $this->argument('lng')) {
            $this->lng = $this->ask('Please enter longitude');
        }
    }

    /**
     *
     */
    private function setCity()
    {
        $this->city = (string) $this->argument('city');

        if(! $this->argument('city')) {
            $this->city = $this->ask('Please enter city');
        }
    }

    /**
     * @param $response
     * @return bool
     */
    private function setResponse($response)
    {
        if($response->isOk()) {
            return $response;
        } else {
            $this->error('Something went wrong');
            return false;
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule)
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
