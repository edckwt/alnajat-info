<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\GuardsPublishing;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use GuardsPublishing;

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount('news')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = new Category;
        $category->fill($this->validated($request));
        $this->guardPublishing($category, 'categories', $request);
        $category->created_by = $category->updated_by = $request->user()->id;
        $category->save();

        return redirect()->route('admin.categories.index')->with('status', 'تمت إضافة القسم.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->fill($this->validated($request));
        $this->guardPublishing($category, 'categories', $request);
        $category->updated_by = $request->user()->id;
        $category->save();

        return redirect()->route('admin.categories.index')->with('status', 'تم حفظ القسم.');
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status', 'تم تحديث حالة القسم.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // روابط الأخبار بالقسم تُحذف معه (cascade)، والأخبار نفسها تبقى.
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', "تم حذف القسم: {$category->name}");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], [], ['name' => 'اسم القسم', 'description' => 'الوصف']);
    }
}
