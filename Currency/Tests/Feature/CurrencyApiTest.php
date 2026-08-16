<?php

namespace Modules\Currency\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\ModuleTestCase;

class CurrencyApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Currency';

    protected function setUp(): void
    {
        parent::setUp();

        // Курсы лежат в общем кэше сайта, между тестами его нужно сбрасывать
        Cache::forget('currency');

        require_once base_path('modules/Currency/helpers.php');
    }

    public function testRatesAreReturned(): void
    {
        Http::fake([
            'cbr-xml-daily.ru/*' => Http::response([
                'Date'   => '2026-08-16T11:30:00+03:00',
                'Valute' => [
                    'USD' => ['Name' => 'Доллар США', 'Nominal' => 1, 'Value' => 90.5, 'Previous' => 91.2],
                ],
            ]),
        ]);

        $this->getJson('/api/currency')
            ->assertOk()
            ->assertJsonPath('date', '2026-08-16T11:30:00+03:00')
            ->assertJsonPath('data.USD.value', 90.5)
            ->assertJsonPath('data.USD.previous', 91.2);
    }

    public function testRatesComeFromCache(): void
    {
        Http::fake([
            'cbr-xml-daily.ru/*' => Http::response([
                'Date'   => '2026-08-16T11:30:00+03:00',
                'Valute' => ['USD' => ['Name' => 'Доллар США', 'Nominal' => 1, 'Value' => 90.5, 'Previous' => 91.2]],
            ]),
        ]);

        $this->getJson('/api/currency')->assertOk();
        $this->getJson('/api/currency')->assertOk();

        // К ЦБ ходит сам сайт раз в час, а не каждый клиент
        Http::assertSentCount(1);
    }

    public function testUnavailableSourceGives503(): void
    {
        Http::fake(['cbr-xml-daily.ru/*' => Http::response(null, 500)]);

        $this->getJson('/api/currency')->assertStatus(503);
    }
}
