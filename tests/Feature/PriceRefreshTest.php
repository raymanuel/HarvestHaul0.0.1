<?php

namespace Tests\Feature;

use App\Models\CropPriceHistory;
use App\Models\ScraperStatus;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PriceRefreshTest extends TestCase
{
    use RefreshDatabase;

    public static array $scraperCalls = [];

    private function actingAdmin(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        return $user;
    }

    private function seedStoredPrices(string $date): void
    {
        CropPriceHistory::create([
            'commodity_name' => 'Rice',
            'commodity_category' => 'Cereals',
            'source' => 'da_rfo12',
            'source_date' => $date,
            'price_per_kg' => 50,
        ]);
    }

    private function seedLastRun(array $attributes): void
    {
        ScraperStatus::create(array_merge([
            'scraper_name' => 'darfo12',
            'status' => 'success',
            'source_date' => '2026-08-21',
            'message' => 'Google Doc PDF: 25 commodities',
            'records_matched' => 25,
            'records_skipped' => 0,
        ], $attributes));
    }

    /**
     * Replace the real scraper command with a recording stub so tests never hit the network.
     * Invocations land in self::$scraperCalls as ['force' => bool].
     */
    private function fakeScraper(): void
    {
        self::$scraperCalls = [];

        $kernel = $this->app->make(ConsoleKernel::class);
        $kernel->bootstrap();

        $kernel->registerCommand(new ClosureCommand('crops:scrape:darfo12 {--force} {--diagnose}', function () {
            PriceRefreshTest::$scraperCalls[] = ['force' => (bool) $this->option('force')];

            return 0;
        }));
    }

    public function test_reports_updated_when_source_has_newer_data(): void
    {
        $this->actingAdmin();
        $this->seedStoredPrices('2026-08-20');
        $this->seedLastRun(['source_date' => '2026-08-21']);
        $this->fakeScraper();

        $this->post(route('prices.refresh'))
            ->assertRedirect(route('prices.full'))
            ->assertSessionHas('success', fn ($value) => str_contains($value, 'updated'));

        $this->assertCount(1, PriceRefreshTest::$scraperCalls);
        $this->assertFalse(PriceRefreshTest::$scraperCalls[0]['force']);
    }

    public function test_reports_no_new_data_when_source_is_stale(): void
    {
        $this->actingAdmin();
        $this->seedStoredPrices('2026-08-21');
        $this->seedLastRun(['source_date' => '2026-08-21']);
        $this->fakeScraper();

        $this->post(route('prices.refresh'))
            ->assertRedirect(route('prices.full'))
            ->assertSessionHas('warning', fn ($value) => str_contains($value, 'No new data'));
    }

    public function test_reports_no_new_data_when_run_was_skipped(): void
    {
        $this->actingAdmin();
        $this->seedStoredPrices('2026-08-21');
        $this->seedLastRun(['status' => 'skipped', 'source_date' => '2026-08-21']);
        $this->fakeScraper();

        $this->post(route('prices.refresh'))
            ->assertRedirect(route('prices.full'))
            ->assertSessionHas('warning', fn ($value) => str_contains($value, 'No new data'));
    }

    public function test_reports_error_when_scrape_failed(): void
    {
        $this->actingAdmin();
        $this->seedStoredPrices('2026-08-20');
        $this->seedLastRun(['status' => 'failed', 'source_date' => null]);
        $this->fakeScraper();

        $this->post(route('prices.refresh'))
            ->assertRedirect(route('prices.full'))
            ->assertSessionHas('error');

        $this->assertCount(1, PriceRefreshTest::$scraperCalls);
    }

    public function test_redirects_with_error_when_refresh_already_running(): void
    {
        $this->actingAdmin();
        $this->fakeScraper();

        $lock = Cache::lock('darfo12.scrape', 600);
        $lock->get();

        try {
            $this->post(route('prices.refresh'))
                ->assertRedirect(route('prices.full'))
                ->assertSessionHas('error', fn ($value) => str_contains($value, 'already in progress'));
        } finally {
            $lock->release();
        }

        $this->assertCount(0, PriceRefreshTest::$scraperCalls);
    }

    public function test_warning_banner_renders_on_prices_page(): void
    {
        $this->actingAdmin();
        $this->seedStoredPrices('2026-08-21');

        $this->withSession(['warning' => 'No new data from the DA RFO12 source yet. Prices remain as of Aug 21, 2026.'])
            ->get(route('prices.full'))
            ->assertOk()
            ->assertSee('No new data from the DA RFO12 source yet.')
            ->assertSee('bg-amber-50', false);
    }
}
