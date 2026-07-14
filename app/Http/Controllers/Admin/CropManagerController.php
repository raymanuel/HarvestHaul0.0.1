<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CropCategory;
use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\AuditLog;

class CropManagerController extends Controller
{

    // No __construct() — middleware handled at route level
    // via EnsureUserIsAdmin in rputes


    private function log(string $action, string $targetType, int $targetId, string $notes): void
    {
        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'notes'       => $notes,
        ]);
    }

    // -------------------------------------------------------
    // INDEX
    // -------------------------------------------------------
    public function index()
    {
        $categories = CropCategory::with(['crops.varieties'])
            ->orderBy('name')
            ->get();

        return view('admin.crops.index', compact('categories'));
    }

    // -------------------------------------------------------
    // CROP CATEGORY — store
    // -------------------------------------------------------
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:crop_categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.unique' => 'A category with this name already exists.',
        ]);

        $category = CropCategory::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status'      => 'active',
        ]);

        $this->log(
            'created_crop_category',
            'crop_category',
            $category->id,
            "Created crop category \"{$category->name}\"."
        );

        return back()->with('success', 'Crop category created successfully.');
    }

    // -------------------------------------------------------
    // CROP CATEGORY — update
    // -------------------------------------------------------
    public function updateCategory(Request $request, CropCategory $category)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:crop_categories,name,' . $category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ]);

        $old = $category->name;

        $category->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status'      => $validated['status'],
        ]);

        $this->log(
            'updated_crop_category',
            'crop_category',
            $category->id,
            "Updated crop category \"{$old}\" → name: \"{$validated['name']}\", status: {$validated['status']}."
        );

        return back()->with('success', 'Category updated successfully.');
    }

    // -------------------------------------------------------
    // CROP CATEGORY — destroy
    // -------------------------------------------------------
    public function destroyCategory(CropCategory $category)
    {
        if ($category->crops()->count() > 0) {
            return back()->with('error', 'Cannot delete a category that still has crops assigned to it.');
        }

        $name = $category->name;
        $id   = $category->id;

        $category->delete();

        $this->log(
            'deleted_crop_category',
            'crop_category',
            $id,
            "Deleted crop category \"{$name}\"."
        );

        return back()->with('success', 'Category deleted.');
    }

    // -------------------------------------------------------
    // CROP — store

    public function storeCrop(Request $request)
    {
        $validated = $request->validate([
            'crop_category_id' => ['required', 'exists:crop_categories,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
        ], [
            'crop_category_id.required' => 'Please select a category.',
            'crop_category_id.exists'   => 'Selected category does not exist.',
        ]);

        $exists = Crop::where('crop_category_id', $validated['crop_category_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'A crop with this name already exists under the selected category.');
        }

        $category = CropCategory::find($validated['crop_category_id']);

        $crop = Crop::create([
            'crop_category_id' => $validated['crop_category_id'],
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'status'           => 'active',
        ]);

        $this->log(
            'created_crop',
            'crop',
            $crop->id,
            "Created crop \"{$crop->name}\" under category \"{$category->name}\"."
        );

        return back()->with('success', 'Crop added successfully.');
    }

    // -------------------------------------------------------
    // CROP — update

    public function updateCrop(Request $request, Crop $crop)
    {
        $validated = $request->validate([
            'crop_category_id' => ['required', 'exists:crop_categories,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
            'status'           => ['required', 'in:active,inactive'],
        ]);

        $exists = Crop::where('crop_category_id', $validated['crop_category_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $crop->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Another crop with this name already exists under the selected category.');
        }

        $old      = $crop->name;
        $category = CropCategory::find($validated['crop_category_id']);

        $crop->update([
            'crop_category_id' => $validated['crop_category_id'],
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'status'           => $validated['status'],
        ]);

        $this->log(
            'updated_crop',
            'crop',
            $crop->id,
            "Updated crop \"{$old}\" → name: \"{$validated['name']}\", category: \"{$category->name}\", status: {$validated['status']}."
        );

        return back()->with('success', 'Crop updated successfully.');
    }

    // -------------------------------------------------------
    // CROP — destroy

    public function destroyCrop(Crop $crop)
    {
        if ($crop->varieties()->count() > 0) {
            return back()->with('error', 'Cannot delete a crop that still has varieties assigned to it.');
        }

        $name = $crop->name;
        $id   = $crop->id;

        $crop->delete();

        $this->log(
            'deleted_crop',
            'crop',
            $id,
            "Deleted crop \"{$name}\"."
        );

        return back()->with('success', 'Crop deleted.');
    }

    // -------------------------------------------------------
    // CROP VARIETY — store

    public function storeVariety(Request $request)
    {
        $validated = $request->validate([
            'crop_id'      => ['required', 'exists:crops,id'],
            'name'         => ['required', 'string', 'max:255'],
            'price_per_kg' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description'  => ['nullable', 'string', 'max:500'],
        ], [
            'crop_id.required'      => 'Please select a crop.',
            'crop_id.exists'        => 'Selected crop does not exist.',
            'price_per_kg.required' => 'Please enter a price per kg.',
            'price_per_kg.min'      => 'Price cannot be negative.',
        ]);

        $exists = CropVariety::where('crop_id', $validated['crop_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This variety already exists under the selected crop.');
        }

        $crop = Crop::find($validated['crop_id']);

        $variety = CropVariety::create([
            'crop_id'      => $validated['crop_id'],
            'name'         => $validated['name'],
            'price_per_kg' => $validated['price_per_kg'],
            'description'  => $validated['description'] ?? null,
            'status'       => 'active',
        ]);

        $this->log(
            'created_crop_variety',
            'crop_variety',
            $variety->id,
            "Created variety \"{$variety->name}\" under crop \"{$crop->name}\" at ₱{$variety->price_per_kg}/kg."
        );

        return back()->with('success', 'Variety added successfully.');
    }

    // -------------------------------------------------------
    // CROP VARIETY — update
    // -------------------------------------------------------
    public function updateVariety(Request $request, CropVariety $variety)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'price_per_kg' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description'  => ['nullable', 'string', 'max:500'],
            'status'       => ['required', 'in:active,inactive'],
        ], [
            'price_per_kg.required' => 'Please enter a price per kg.',
            'price_per_kg.min'      => 'Price cannot be negative.',
        ]);

        $exists = CropVariety::where('crop_id', $variety->crop_id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $variety->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Another variety with this name already exists under this crop.');
        }

        $old  = $variety->name;
        $oldPrice = $variety->price_per_kg;
        $crop = Crop::find($variety->crop_id);

        $variety->update([
            'name'         => $validated['name'],
            'price_per_kg' => $validated['price_per_kg'],
            'description'  => $validated['description'] ?? null,
            'status'       => $validated['status'],
        ]);

        $this->log(
            'updated_crop_variety',
            'crop_variety',
            $variety->id,
            "Updated variety \"{$old}\" under crop \"{$crop->name}\" → name: \"{$validated['name']}\", price: ₱{$oldPrice} → ₱{$validated['price_per_kg']}, status: {$validated['status']}."
        );

        return back()->with('success', 'Variety updated successfully.');
    }

    // -------------------------------------------------------
    // CROP VARIETY — destroy
    // -------------------------------------------------------
    public function destroyVariety(CropVariety $variety)
    {
        $activeHarvests = $variety->harvests()->whereIn('status', ['active', 'pending'])->count();

        if ($activeHarvests > 0) {
            return back()->with('error', 'Cannot delete a variety that has active harvest posts referencing it.');
        }

        $name = $variety->name;
        $id   = $variety->id;
        $crop = Crop::find($variety->crop_id);

        $variety->delete();

        $this->log(
            'deleted_crop_variety',
            'crop_variety',
            $id,
            "Deleted variety \"{$name}\" from crop \"{$crop->name}\"."
        );

        return back()->with('success', 'Variety deleted.');
    }
}
