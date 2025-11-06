<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FeeComponentController extends Controller
{
    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    public function index(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $q = trim($request->get('q',''));
        $components = FeeComponent::where('school_id', $schoolId)
            ->when($q, fn($qq)=>$qq->where('name','like',"%{$q}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.fees.components.index', compact('components','q'));
    }

    public function create()
    {
        return view('admin.fees.components.create');
    }

    public function store(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $data = $request->validate([
            'name'           => "required|string|max:120|unique:fee_components,name,NULL,id,school_id,{$schoolId}",
            'slug'           => "nullable|string|max:140|unique:fee_components,slug,NULL,id,school_id,{$schoolId}",
            'frequency'      => 'required|in:one_time,monthly,term,annual',
            'calc_type'      => 'required|in:fixed,percent_of_base',
            'default_amount' => 'nullable|numeric|min:0',
            'status'         => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['school_id'] = $schoolId;
        $data['status'] = (bool)($data['status'] ?? true);

        FeeComponent::create($data);

        return redirect()->route('admin.fees.components.index')->with('success','Component created.');
    }

    public function edit($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $component = FeeComponent::where('school_id',$schoolId)->findOrFail($id);
        return view('admin.fees.components.edit', compact('component'));
    }

    public function update(Request $request, $id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $component = FeeComponent::where('school_id',$schoolId)->findOrFail($id);

        $data = $request->validate([
            'name'           => "required|string|max:120|unique:fee_components,name,{$component->id},id,school_id,{$schoolId}",
            'slug'           => "nullable|string|max:140|unique:fee_components,slug,{$component->id},id,school_id,{$schoolId}",
            'frequency'      => 'required|in:one_time,monthly,term,annual',
            'calc_type'      => 'required|in:fixed,percent_of_base',
            'default_amount' => 'nullable|numeric|min:0',
            'status'         => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['status'] = (bool)($data['status'] ?? true);

        $component->update($data);

        return redirect()->route('admin.fees.components.index')->with('success','Component updated.');
    }

    public function destroy($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $component = FeeComponent::where('school_id',$schoolId)->findOrFail($id);
        $component->delete();

        return back()->with('success','Component deleted.');
    }

    public function toggle($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->json(['ok'=>false,'msg'=>'No school'], 422);

        $c = FeeComponent::where('school_id',$schoolId)->findOrFail($id);
        $c->update(['status' => ! $c->status]);

        return back()->with('success','Status updated.');
    }
}
