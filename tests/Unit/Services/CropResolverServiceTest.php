<?php

namespace Tests\Unit\Services;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Services\CropResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private Crop $corn;
    private CropVariety $yellow;
    private int $categoryId;
    private CropResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new CropResolverService();
        $this->categoryId = CropCategory::create(['name' => 'Test Category'])->id;
        $this->corn = Crop::create([
            'crop_category_id' => $this->categoryId,
            'name' => 'Corn',
            'status' => 'active',
        ]);
        $this->yellow = CropVariety::create([
            'crop_id' => $this->corn->id,
            'name' => 'Yellow',
            'status' => 'active',
            'price_per_kg' => 0,
        ]);
    }

    public function test_crop_typo_corrects_to_existing_name(): void
    {
        $corrected = null;
        $crop = $this->resolver->resolveCrop('Korn', $this->categoryId, $corrected);

        $this->assertSame('Corn', $crop->name);
        $this->assertSame('Korn', $corrected);
        $this->assertDatabaseCount('crops', 1);
    }

    public function test_exact_case_insensitive_name_reuses_crop_without_correction(): void
    {
        $corrected = 'x';
        $crop = $this->resolver->resolveCrop('corn', $this->categoryId, $corrected);

        $this->assertSame('Corn', $crop->name);
        $this->assertNull($corrected);
        $this->assertDatabaseCount('crops', 1);
    }

    public function test_unmatched_name_still_creates_new_crop(): void
    {
        $corrected = null;
        $crop = $this->resolver->resolveCrop('Pepper', $this->categoryId, $corrected);

        $this->assertSame('Pepper', $crop->name);
        $this->assertNull($corrected);
        $this->assertDatabaseCount('crops', 2);
    }

    public function test_tied_candidates_are_not_auto_corrected(): void
    {
        Crop::create(['crop_category_id' => $this->categoryId, 'name' => 'Banana', 'status' => 'active']);
        Crop::create(['crop_category_id' => $this->categoryId, 'name' => 'Yanana', 'status' => 'active']);

        $corrected = null;
        $crop = $this->resolver->resolveCrop('Xanana', $this->categoryId, $corrected);

        $this->assertSame('Xanana', $crop->name);
        $this->assertNull($corrected);
        $this->assertDatabaseCount('crops', 4);
    }

    public function test_variety_typo_corrects_to_existing_name_within_crop(): void
    {
        $corrected = null;
        $variety = $this->resolver->resolveVariety($this->corn, 'Yelow', $corrected);

        $this->assertSame('Yellow', $variety->name);
        $this->assertSame('Yelow', $corrected);
        $this->assertDatabaseCount('crop_varieties', 1);
    }

    public function test_variety_typo_ignores_names_from_other_crops(): void
    {
        $rice = Crop::create(['crop_category_id' => $this->categoryId, 'name' => 'Rice', 'status' => 'active']);
        CropVariety::create(['crop_id' => $rice->id, 'name' => 'Yellow', 'status' => 'active', 'price_per_kg' => 0]);

        $corrected = null;
        $variety = $this->resolver->resolveVariety($this->corn, 'Yelow', $corrected);

        $this->assertSame('Yellow', $variety->name);
        $this->assertSame('Yelow', $corrected);
        $this->assertSame($this->corn->id, $variety->crop_id);
        $this->assertDatabaseCount('crop_varieties', 2);
    }
}