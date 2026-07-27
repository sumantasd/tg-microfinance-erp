<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\TeamMemberRequest;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('website.manage');

        $query = TeamMember::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('designation', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $members = $query->orderBy('display_order', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.cms.team.index', compact('members'));
    }

    public function create()
    {
        $this->authorize('website.manage');

        return view('admin.cms.team.create');
    }

    public function store(TeamMemberRequest $request)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('cms/team', 'public');
        }

        TeamMember::create($data);

        return redirect()->route('admin.cms.team.index')->with('success', 'Team member added successfully.');
    }

    public function edit(TeamMember $team)
    {
        $this->authorize('website.manage');

        return view('admin.cms.team.edit', ['member' => $team]);
    }

    public function update(TeamMemberRequest $request, TeamMember $team)
    {
        $this->authorize('website.manage');

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($team->photo && Storage::disk('public')->exists($team->photo)) {
                Storage::disk('public')->delete($team->photo);
            }
            $data['photo'] = $request->file('photo')->store('cms/team', 'public');
        }

        $team->update($data);

        return redirect()->route('admin.cms.team.index')->with('success', 'Team member updated successfully.');
    }

    public function toggleStatus(TeamMember $team)
    {
        $this->authorize('website.manage');

        $newStatus = $team->status === 'active' ? 'inactive' : 'active';
        $team->update(['status' => $newStatus]);

        return back()->with('success', 'Status updated to ' . strtoupper($newStatus));
    }

    public function destroy(TeamMember $team)
    {
        $this->authorize('website.manage');

        if ($team->photo && Storage::disk('public')->exists($team->photo)) {
            Storage::disk('public')->delete($team->photo);
        }

        $team->delete();

        return redirect()->route('admin.cms.team.index')->with('success', 'Team member deleted successfully.');
    }
}
