<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCptRequest;
use App\Http\Requests\Admin\UpdateCptRequest;
use App\Models\CptType;

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
        CptType::create($request->validated());

        return redirect()->route('admin.cpts.index')->with('success', 'CPT type created.');
    }

    public function edit(CptType $cpt)
    {
        return view('admin.cpts.form', compact('cpt'));
    }

    public function update(UpdateCptRequest $request, CptType $cpt)
    {
        $cpt->update($request->validated());

        return redirect()->route('admin.cpts.index')->with('success', 'CPT type updated.');
    }

    public function destroy(CptType $cpt)
    {
        $cpt->delete();
        return redirect()->route('admin.cpts.index')->with('success', 'CPT type deleted.');
    }
}
