<?php

namespace App\Commands;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use LaravelZero\Framework\Commands\Command;

class GetWeatherCommand extends Command
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var
     */
    private $option;
    /**
     * @var
     */
    private $city;
    /**
     * @var
     */
    private $lat;
    /**
     * @var
     */
    private $lng;

    /**
     * GetWeatherCommand constructor.
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        parent::__construct();

        $this->client = $client;
    }
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
    protected $description = 'Shows weather for location or city';

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
           [$headers, $rows] = $this->getTablePayload($response);
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
     *
     */
    private function searchByCoordinates()
    {
        $this->setLatitude();
        $this->setLongitude();

        $params = [
            'query' => [
                'lat' => $this->lat,
                'lon' => $this->lng,
                'units' => config('openweather.units'),
                'appid' => config('openweather.api_key')
            ]
        ];

        return $this->fetchData($params);
    }


    /**
     * @return bool|mixed
     */
    private function searchByCity()
    {
        $this->setCity();

        $params = [
            'query' => [
                'q' => $this->city,
                'units' => config('openweather.units'),
                'appid' => config('openweather.api_key')
            ]
        ];

        return $this->fetchData($params);
    }


    /**
     *
     */
    private function setLatitude(): void
    {
        $this->lat = (float) $this->argument('lat');

        if(! $this->argument('lat')) {
            $this->lat = $this->ask('Please enter latitude');
        }
    }


    private function setLongitude(): void
    {
        $this->lng = (float) $this->argument('lng');

        if(! $this->argument('lng')) {
            $this->lng = $this->ask('Please enter longitude');
        }
    }


    private function setCity()
    {
        $this->city = (string) $this->argument('city');

        if(! $this->argument('city')) {
            $this->city = $this->ask('Please enter city');
        }
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
     * @param array $params
     * @return bool|mixed
     */
    private function fetchData(array $params = [])
    {
        try {
            $response = $this->client->get('weather', $params);
            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            $this->error(json_decode($e->getResponse()->getBody(), true)['message']);
            return false;
        }
    }
}
