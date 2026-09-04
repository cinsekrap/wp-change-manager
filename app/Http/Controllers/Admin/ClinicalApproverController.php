<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicalApprover;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalApproverController extends Controller
{
    public function index()
    {
        $approvers = ClinicalApprover::ordered()->paginate(25);

        return view('admin.clinical-approvers.index', compact('approvers'));
    }

    public function create()
    {
        return view('admin.clinical-approvers.form', ['approver' => new ClinicalApprover(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $approver = ClinicalApprover::create($this->validated($request));

        AuditService::log(
            action: 'created',
            model: $approver,
            description: "Added clinical approver: {$approver->name}",
            newValues: ['name' => $approver->name, 'email' => $approver->email],
        );

        return redirect()->route('admin.clinical-approvers.index')->with('success', 'Clinical approver added.');
    }

    public function edit(ClinicalApprover $clinicalApprover)
    {
        return view('admin.clinical-approvers.form', ['approver' => $clinicalApprover]);
    }

    public function update(Request $request, ClinicalApprover $clinicalApprover)
    {
        $old = $clinicalApprover->only(['name', 'email', 'is_active']);
        $clinicalApprover->update($this->validated($request, $clinicalApprover));

        AuditService::log(
            action: 'updated',
            model: $clinicalApprover,
            description: "Updated clinical approver: {$clinicalApprover->name}",
            oldValues: $old,
            newValues: $clinicalApprover->only(['name', 'email', 'is_active']),
        );

        return redirect()->route('admin.clinical-approvers.index')->with('success', 'Clinical approver updated.');
    }

    /**
     * Deactivate rather than delete: sign-offs already given name this person, and
     * a governance record should not lose the identity behind it.
     */
    public function destroy(ClinicalApprover $clinicalApprover)
    {
        $clinicalApprover->update(['is_active' => false]);

        AuditService::log(
            action: 'deactivated',
            model: $clinicalApprover,
            description: "Deactivated clinical approver: {$clinicalApprover->name}",
        );

        return redirect()->route('admin.clinical-approvers.index')
            ->with('success', 'Clinical approver deactivated. Past approvals still name them.');
    }

    private function validated(Request $request, ?ClinicalApprover $existing = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('clinical_approvers', 'email')->ignore($existing)],
            'job_title' => 'nullable|string|max:255',
            'areas_of_expertise' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
