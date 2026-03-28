<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('changeRequests')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:tags,name,' . $tag->id,
            'colour' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $tag->update([
            'name' => $request->name,
            'colour' => $request->colour,
        ]);

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->changeRequests()->detach();
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
