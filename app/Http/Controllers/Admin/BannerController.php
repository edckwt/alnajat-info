<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\GuardsPublishing;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    use GuardsPublishing;

    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::orderByDesc('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['is_active' => true])]);
    }

    public function store(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $this->save(new Banner, $request, $uploader);

        return redirect()->route('admin.banners.index')->with('status', 'تمت إضافة البانر.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(Request $request, Banner $banner, ImageUploader $uploader): RedirectResponse
    {
        $this->save($banner, $request, $uploader);

        return redirect()->route('admin.banners.index')->with('status', 'تم حفظ البانر.');
    }

    public function toggle(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('status', 'تم تحديث حالة البانر.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('status', "تم حذف البانر: {$banner->title}");
    }

    private function save(Banner $banner, Request $request, ImageUploader $uploader): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'body' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'max:'.config('alnajat.image_max_kb')],
            'is_active' => ['boolean'],
        ], [], ['title' => 'العنوان', 'image_file' => 'الصورة', 'url' => 'الرابط', 'body' => 'الكود']);

        $banner->fill(collect($data)->except('image_file')->all());

        if ($request->hasFile('image_file')) {
            $banner->image = $uploader->store($request->file('image_file'), 'banners');
        }

        $this->guardPublishing($banner, 'banners', $request);
        $banner->created_by ??= $request->user()->id;
        $banner->updated_by = $request->user()->id;
        $banner->save();
    }
}
