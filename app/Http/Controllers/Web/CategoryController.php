<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private const COLORS = [
        'gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green',
        'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet',
        'purple', 'fuchsia', 'pink', 'rose',
    ];

    public function index()
    {
        $categories = Category::withCount('prompts')->orderBy('sort_order')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $colors = self::COLORS;
        $nextOrder = (Category::max('sort_order') ?? 0) + 1;

        return view('categories.create', compact('colors', 'nextOrder'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'color'      => ['required', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Category::create([
            'name'       => $data['name'],
            'color'      => $data['color'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        $colors = self::COLORS;

        return view('categories.edit', compact('category', 'colors'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'color'      => ['required', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update([
            'name'       => $data['name'],
            'color'      => $data['color'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted. Prompts in this category are now uncategorized.');
    }
}
