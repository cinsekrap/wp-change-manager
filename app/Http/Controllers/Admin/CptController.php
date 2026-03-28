<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCptRequest;
use App\Http\Requests\Admin\UpdateCptRequest;
use App\Models\CptType;
use App\Services\AuditService;

class CptController extends Controller
{
    public function index()
    {
        $cpts = CptType::ordered()->paginate(25);
        return view('admin.cpts.index', compact('cpts'));
    }

    public function create()
    {
        return view('admin.cpts.form', ['cpt' => new CptType()]);
    }

    public function store(StoreCptRequest $request)
    {
        $cpt = CptType::create($request->validated());

        AuditService::log(
            action: 'created',
            model: $cpt,
            description: "Created content type: {$cpt->name}",
            newValues: ['name' => $cpt->name, 'slug' => $cpt->slug],
        );

        return redirect()->route('admin.cpts.index')->with('success', 'CPT type created.');
    }

    public function edit(CptType $cpt)
    {
        return view('admin.cpts.form', compact('cpt'));
    }

    public function update(UpdateCptRequest $request, CptType $cpt)
    {
        $oldValues = $cpt->only(['name', 'slug', 'sort_order', 'is_active']);
        $cpt->update($request->validated());
        $newValues = $cpt->only(['name', 'slug', 'sort_order', 'is_active']);

        AuditService::log(
            action: 'updated',
            model: $cpt,
            description: "Updated content type: {$cpt->name}",
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return redirect()->route('admin.cpts.index')->with('success', 'CPT type updated.');
    }

    public function destroy(CptType $cpt)
    {
        $cptName = $cpt->name;
        $cpt->delete();

        AuditService::log(
            action: 'deleted',
            model: $cpt,
            description: "Deleted content type: {$cptName}",
            oldValues: ['name' => $cptName, 'slug' => $cpt->slug],
        );

        return redirect()->route('admin.cpts.index')->with('success', 'CPT type deleted.');
    }
}
