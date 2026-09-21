<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CropGrade;
use App\Models\PackagingType;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    public function index()
    {
        return view('admin.reference.index', [
            'grades' => CropGrade::orderBy('sort_order')->get(),
            'packagingTypes' => PackagingType::orderBy('sort_order')->get(),
        ]);
    }

    public function storeGrade(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:crop_grades,name',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        CropGrade::create($data + ['is_active' => true]);

        return back()->with('success', "Grade \"{$data['name']}\" was added. It now appears when personnel record receiving.");
    }

    public function updateGrade(Request $request, CropGrade $cropGrade)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:crop_grades,name,'.$cropGrade->id,
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // An unchecked checkbox is never submitted, so read it explicitly
        // rather than relying on it being present in $data.
        $data['is_active'] = $request->boolean('is_active');

        $cropGrade->update($data);

        return back()->with('success', "Grade \"{$cropGrade->name}\" was updated.");
    }

    public function destroyGrade(CropGrade $cropGrade)
    {
        $cropGrade->update(['is_active' => false]);

        return back()->with('success', "Grade \"{$cropGrade->name}\" was deactivated. Existing records keep it, but it no longer appears in new forms.");
    }

    public function storePackaging(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:packaging_types,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        PackagingType::create($data + ['is_active' => true]);

        return back()->with('success', "Packaging \"{$data['name']}\" was added. It now appears in haul requests.");
    }

    public function updatePackaging(Request $request, PackagingType $packagingType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:packaging_types,name,'.$packagingType->id,
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $packagingType->update($data);

        return back()->with('success', "Packaging \"{$packagingType->name}\" was updated.");
    }

    public function destroyPackaging(PackagingType $packagingType)
    {
        $packagingType->update(['is_active' => false]);

        return back()->with('success', "Packaging \"{$packagingType->name}\" was deactivated. Existing records keep it, but it no longer appears in new forms.");
    }
}