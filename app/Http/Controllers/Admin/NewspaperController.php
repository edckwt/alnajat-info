<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\GuardsPublishing;
use App\Http\Controllers\Controller;
use App\Models\Newspaper;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewspaperController extends Controller
{
    use GuardsPublishing;

    public const TYPES = [0 => 'ورقية', 1 => 'موقع إلكتروني'];

    public function index(Request $request): View
    {
        return view('admin.newspapers.index', [
            'newspapers' => Newspaper::withCount('news')
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->integer('type')))
                ->orderByDesc('id')->get(),
            'types' => self::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.newspapers.form', ['newspaper' => new Newspaper(['is_active' => true, 'type' => 0]), 'types' => self::TYPES]);
    }

    public function store(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $newspaper = new Newspaper;
        $this->save($newspaper, $request, $uploader);

        return redirect()->route('admin.newspapers.index')->with('status', 'تمت إضافة الصحيفة.');
    }

    public function edit(Newspaper $newspaper): View
    {
        return view('admin.newspapers.form', ['newspaper' => $newspaper, 'types' => self::TYPES]);
    }

    public function update(Request $request, Newspaper $newspaper, ImageUploader $uploader): RedirectResponse
    {
        $this->save($newspaper, $request, $uploader);

        return redirect()->route('admin.newspapers.index')->with('status', 'تم حفظ الصحيفة.');
    }

    public function toggle(Newspaper $newspaper): RedirectResponse
    {
        $newspaper->update(['is_active' => ! $newspaper->is_active]);

        return back()->with('status', 'تم تحديث حالة الصحيفة.');
    }

    public function destroy(Newspaper $newspaper): RedirectResponse
    {
        // الأخبار المرتبطة تبقى، ويُفرّغ حقل الصحيفة فيها (nullOnDelete).
        $newspaper->delete();

        return redirect()->route('admin.newspapers.index')->with('status', "تم حذف الصحيفة: {$newspaper->name}");
    }

    private function save(Newspaper $newspaper, Request $request, ImageUploader $uploader): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:0,1'],
            'url' => ['nullable', 'string', 'max:2000'],
            'country' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo_file' => ['nullable', 'image', 'max:'.config('alnajat.image_max_kb')],
            'is_active' => ['boolean'],
        ], [], ['name' => 'اسم الصحيفة', 'logo_file' => 'الشعار', 'url' => 'الرابط']);

        $newspaper->fill(collect($data)->except('logo_file')->all());

        if ($request->hasFile('logo_file')) {
            $newspaper->logo = $uploader->store($request->file('logo_file'), 'newspaper');
        }

        $this->guardPublishing($newspaper, 'newspapers', $request);
        $newspaper->created_by ??= $request->user()->id;
        $newspaper->updated_by = $request->user()->id;
        $newspaper->save();
    }
}
