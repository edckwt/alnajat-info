<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * الأدوار: مجموعة صلاحيات تُسند للأعضاء. «مدير النظام» يملك الكل ولا يُعدَّل،
 * ولا يمنح أحد صلاحية لا يملكها هو.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->orderByDesc('is_system')->orderBy('id')->get(),
            'total' => count(Permissions::all()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.roles.form', ['role' => new Role(['permissions' => []]), 'grantable' => $this->grantable($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = new Role;
        $role->key = 'role-'.Str::lower(Str::random(6));
        $this->fill($role, $data, $request);

        return redirect()->route('admin.roles.index')->with('status', "تمت إضافة الدور: {$role->name}");
    }

    public function edit(Request $request, Role $role): View
    {
        abort_if($role->isAdmin(), 403, 'دور مدير النظام يملك كل الصلاحيات ولا يُعدّل.');

        return view('admin.roles.form', ['role' => $role, 'grantable' => $this->grantable($request)]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->isAdmin(), 403);

        $this->fill($role, $this->validated($request, $role), $request);

        return redirect()->route('admin.roles.index')->with('status', "تم حفظ الدور: {$role->name}");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'لا يمكن حذف أدوار النظام.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => "الدور «{$role->name}» مسند لأعضاء؛ انقلهم إلى دور آخر أولاً."]);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', "تم حذف الدور: {$role->name}");
    }

    /** @return list<string>|null null = كل الصلاحيات (مدير النظام) */
    private function grantable(Request $request): ?array
    {
        return $request->user()->isAdmin() ? null : $request->user()->permissionKeys();
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
        ], [], ['name' => 'اسم الدور', 'description' => 'الوصف', 'permissions.*' => 'الصلاحية']);
    }

    private function fill(Role $role, array $data, Request $request): void
    {
        $requested = Permissions::normalize($data['permissions'] ?? []);
        $grantable = $this->grantable($request);

        if ($grantable !== null) {
            // ما لا يملكه العضو يبقى كما كان في الدور (لا يُمنح ولا يُسحب منه)
            $kept = array_diff($role->permissions ?? [], $grantable);
            $requested = Permissions::normalize(array_merge(array_intersect($requested, $grantable), $kept));
        }

        $role->name = $data['name'];
        $role->description = $data['description'] ?? null;
        $role->permissions = $requested;
        $role->save();
    }
}
