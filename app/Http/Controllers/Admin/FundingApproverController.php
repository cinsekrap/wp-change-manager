<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundingApprover;
use App\Services\AuditService;
use Illuminate\Http\Request;

class FundingApproverController extends Controller
{
    public function index()
    {
        $approvers = FundingApprover::ordered()->paginate(25);

        return view('admin.funding-approvers.index', compact('approvers'));
    }

    public function create()
    {
        return view('admin.funding-approvers.form', ['approver' => new FundingApprover(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $approver = FundingApprover::create($this->validated($request));

        AuditService::log(
            action: 'created',
            model: $approver,
            description: "Added funding approver: {$approver->name}",
            newValues: ['name' => $approver->name, 'email' => $approver->email],
        );

        return redirect()->route('admin.funding-approvers.index')->with('success', 'Funding approver added.');
    }

    public function edit(FundingApprover $fundingApprover)
    {
        return view('admin.funding-approvers.form', ['approver' => $fundingApprover]);
    }

    public function update(Request $request, FundingApprover $fundingApprover)
    {
        $old = $fundingApprover->only(['name', 'email', 'job_title', 'remit', 'is_active']);
        $fundingApprover->update($this->validated($request));

        AuditService::log(
            action: 'updated',
            model: $fundingApprover,
            description: "Updated funding approver: {$fundingApprover->name}",
            oldValues: $old,
            newValues: $fundingApprover->only(['name', 'email', 'job_title', 'remit', 'is_active']),
        );

        return redirect()->route('admin.funding-approvers.index')->with('success', 'Funding approver updated.');
    }

    /**
     * Deactivate rather than delete: rounds already decided name this person, and
     * a record of who agreed to spend money should not lose them.
     */
    public function destroy(FundingApprover $fundingApprover)
    {
        $fundingApprover->update(['is_active' => false]);

        AuditService::log(
            action: 'deactivated',
            model: $fundingApprover,
            description: "Deactivated funding approver: {$fundingApprover->name}",
        );

        return redirect()->route('admin.funding-approvers.index')
            ->with('success', $fundingApprover->name.' can no longer be asked to fund work.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'job_title' => 'nullable|string|max:255',
            'remit' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
