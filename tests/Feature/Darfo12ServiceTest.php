<?php

namespace Tests\Feature;

use App\Services\Darfo12Service;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Darfo12ServiceTest extends TestCase
{
    private array $requests = [];

    private function priceRow(string $name, array $priceCells): string
    {
        $tds = '';
        foreach ($priceCells as $cell) {
            $tds .= '<td class="text-center align-middle">' . $cell . '</td>';
        }

        return '<tr><td class="text-wrap">' . $name . '</td><td class="text-wrap">750GM-1KG/HEAD</td>' . $tds . '</tr>';
    }

    private function pricesFragmentForCode(int $code): string
    {
        $kept = $this->priceRow('Test_' . $code . '_Kept', [
            '70', '60', '55', 'N/A', '90', 'N/A', '80',
            'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A',
        ]);
        $fillerA = $this->priceRow('FillerA_' . $code, [
            '100', '110', '105', '95', 'N/A', '108', 'N/A',
            'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A',
        ]);
        $fillerB = $this->priceRow('FillerB_' . $code, [
            '50', 'N/A', '60', '55', 'N/A', '52', 'N/A',
            'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A',
        ]);
        $drop = $this->priceRow('DropMe_' . $code, [
            'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A',
            'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A',
        ]);

        return '<table>' . $kept . $fillerA . $fillerB . $drop . '</table>';
    }

    private function fakeBantay(array $fragmentByCode = null, string $dateText = 'August 14, 2026'): void
    {
        $fragmentByCode = $fragmentByCode ?? []; // optional overrides (used for thin datasets)
        $this->requests = [];

        Http::fake(function ($request) use ($fragmentByCode, $dateText) {
            $this->requests[] = ['url' => $request->url(), 'data' => $request->data()];

            if (str_contains($request->url(), 'tbl_price_get_comm_price_veg.php')) {
                $code = (int) ($request->data()['commodity'] ?? 0);
                $fragment = $fragmentByCode[$code] ?? $this->pricesFragmentForCode($code);

                return Http::response($fragment, 200);
            }

            if (str_contains($request->url(), 'tbl_veg.php')) {
                return Http::response($dateText, 200);
            }

            return Http::response('', 404);
        });
    }

    public function test_fetches_all_seven_crop_codes_and_derives_price_statistics(): void
    {
        $this->fakeBantay();
        $service = new Darfo12Service();

        $prices = $service->fetchRegion12Prices('2026-08-14');

        $this->assertNotEmpty($prices);
        $this->assertGreaterThanOrEqual(10, count($prices));

        foreach ($prices as $row) {
            $this->assertEqualsCanonicalizing(
                ['commodity', 'category', 'low_price', 'high_price', 'common_price', 'dpi_price'],
                array_keys($row)
            );
            $this->assertIsFloat($row['low_price']);
            $this->assertIsFloat($row['high_price']);
            $this->assertIsFloat($row['common_price']);
            $this->assertIsFloat($row['dpi_price']);
        }

        // 7 prices POSTs exactly — never codes 4 / 8 / 10.
        $pricePosts = array_values(array_filter($this->requests, fn ($r) => str_contains($r['url'], 'tbl_price_get_comm_price_veg.php')));
        $this->assertCount(7, $pricePosts, 'Expected exactly 7 POSTs to the prices URL');

        $postedCodes = array_map(fn ($r) => (int) $r['data']['commodity'], $pricePosts);
        sort($postedCodes);
        $this->assertSame([1, 2, 3, 5, 6, 7, 9], $postedCodes);
        $this->assertNotContains(4, $postedCodes);
        $this->assertNotContains(8, $postedCodes);
        $this->assertNotContains(10, $postedCodes);

        // Every produced row carries the exact category for its source commodity code.
        $categoryMap = (new \ReflectionClass(Darfo12Service::class))->getConstant('BANTAY_CATEGORY_MAP');
        foreach ($categoryMap as $code => $category) {
            $kept = collect($prices)->firstWhere('commodity', 'Test_' . $code . '_Kept');
            $this->assertNotNull($kept, "Missing kept row for code {$code}");
            $this->assertSame($category, $kept['category']);
        }

        // Ruling R1 for the known-pattern row: [70,60,55,N/A,90,N/A,80,...] → low 55, high 90, common=dpi=round(avg).
        $kept = collect($prices)->firstWhere('commodity', 'Test_5_Kept');
        $this->assertSame(55.0, $kept['low_price']);
        $this->assertSame(90.0, $kept['high_price']);
        $this->assertSame(71.0, $kept['common_price']);
        $this->assertSame(71.0, $kept['dpi_price']);

        // All-N/A rows are dropped entirely.
        $this->assertEmpty(collect($prices)->filter(fn ($row) => str_starts_with($row['commodity'], 'DropMe_')));
    }

    public function test_fetch_region12_prices_returns_empty_when_dataset_is_thin(): void
    {
        $fragmentByCode = [];
        foreach ([1, 2, 3, 5, 6, 7, 9] as $code) {
            $fragmentByCode[$code] = '<table>' . $this->priceRow('Only_' . $code, ['100', '101', '102', '103', '104', '105', '106', '107', '108', '109', '110', '111', '112', '113', '114']) . '</table>';
        }

        $this->fakeBantay($fragmentByCode);
        $service = new Darfo12Service();

        $prices = $service->fetchRegion12Prices('2026-08-14');

        $this->assertSame([], $prices);
    }

    public function test_fetch_latest_bantay_date_parses_human_readable_date(): void
    {
        $this->fakeBantay(null, 'August 14, 2026');
        $service = new Darfo12Service();

        $date = $service->fetchLatestBantayDate();

        $this->assertSame('2026-08-14', $date);

        $datePosts = array_values(array_filter($this->requests, fn ($r) => str_contains($r['url'], 'tbl_veg.php')));
        $this->assertCount(1, $datePosts);
        $this->assertSame('get_latest_date', $datePosts[0]['data']['action']);
        $this->assertSame('120000000', $datePosts[0]['data']['region']);
    }

    public function test_fetch_latest_bantay_date_returns_null_on_garbage(): void
    {
        $this->fakeBantay(null, 'not-a-date-at-all');
        $service = new Darfo12Service();

        $this->assertNull($service->fetchLatestBantayDate());
    }
}
