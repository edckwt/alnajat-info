<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageUploader;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * الأعضاء: لكل عضو دور (users.role = roles.key) وصلاحيات إضافية اختيارية.
 * لا أحد يمنح صلاحية أو دوراً أوسع مما يملك، ومدير النظام وحده يدير المديرين.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('id')->get(),
            'roles' => Role::pluck('name', 'key'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, new User(['is_active' => true, 'role' => 'editor', 'permissions' => []]));
    }

    public function store(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $data = $this->validated($request, new User);

        User::forceCreate([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'avatar' => $request->hasFile('avatar_file') ? $uploader->store($request->file('avatar_file'), 'avatar') : null,
            'password' => $data['password'],
            'role' => $data['role'],
            'permissions' => $this->extraPermissions($request, $data, []),
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'تمت إضافة العضو.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->guardAdminTarget($request, $user);

        return $this->form($request, $user);
    }

    public function update(Request $request, User $user, ImageUploader $uploader): RedirectResponse
    {
        $this->guardAdminTarget($request, $user);

        $data = $this->validated($request, $user);
        $self = $request->user()->is($user);

        $user->forceFill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            // لا يغيّر أحد دوره أو صلاحياته أو يوقف حسابه بنفسه.
            'role' => $self ? $user->role : $data['role'],
            'permissions' => $self ? $user->permissions : $this->extraPermissions($request, $data, $user->permissions ?? []),
            'is_active' => $self ? true : ($data['is_active'] ?? false),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->legacy_password = null;
        }

        if ($request->hasFile('avatar_file')) {
            $user->avatar = $uploader->store($request->file('avatar_file'), 'avatar');
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar = null;
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'تم حفظ بيانات العضو.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'لا يمكنك حذف حسابك.']);
        }

        $this->guardAdminTarget($request, $user);

        // ما كتبه العضو يبقى (created_by يصبح فارغاً).
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', "تم حذف العضو: {$user->name}");
    }

    private function form(Request $request, User $user): View
    {
        $roles = Role::orderByDesc('is_system')->orderBy('id')->get();

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $roles->filter(fn (Role $role) => $this->canAssign($request, $role) || $role->key === $user->role)->pluck('name', 'key'),
            // صلاحيات كل دور (لإظهار الموروث منها عند تغيير الدور في النموذج)
            'rolePermissions' => $roles->mapWithKeys(fn (Role $role) => [$role->key => $role->isAdmin() ? Permissions::all() : Permissions::normalize($role->permissions)]),
            'grantable' => $request->user()->isAdmin() ? null : $request->user()->permissionKeys(),
        ]);
    }

    /** مدير النظام وحده يعدّل حساب مدير أو يحذفه. */
    private function guardAdminTarget(Request $request, User $user): void
    {
        abort_if($user->isAdmin() && ! $request->user()->isAdmin(), 403, 'مدير النظام وحده يدير حسابات المديرين.');
    }

    /** دور يمكن للعضو الحالي إسناده: المدير يسند أي دور، وغيره دوراً ضمن صلاحياته. */
    private function canAssign(Request $request, Role $role): bool
    {
        if ($request->user()->isAdmin()) {
            return true;
        }

        return ! $role->isAdmin()
            && array_diff(Permissions::normalize($role->permissions), $request->user()->permissionKeys()) === [];
    }

    /**
     * الصلاحيات الإضافية بعد الحذف والإضافة: ما لا يملكه العضو الحالي يبقى كما كان.
     *
     * @return list<string>
     */
    private function extraPermissions(Request $request, array $data, array $current): array
    {
        $requested = Permissions::normalize($data['permissions'] ?? []);

        if ($request->user()->isAdmin()) {
            return $requested;
        }

        $mine = $request->user()->permissionKeys();

        return Permissions::normalize(array_merge(array_intersect($requested, $mine), array_diff($current, $mine)));
    }

    private function validated(Request $request, User $user): array
    {
        $assignable = Role::all()->filter(fn (Role $role) => $this->canAssign($request, $role))->pluck('key')->all();
        if ($user->exists) {
            $assignable[] = $user->role; // إبقاء دوره الحالي مسموح دائماً
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'],
            'avatar_file' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'password' => [$user->exists ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            // العضو لا يغيّر دوره بنفسه: القيمة المرسلة تُتجاهل (update يبقي دوره الحالي)
            'role' => $request->user()->is($user) ? ['nullable'] : ['required', Rule::in($assignable)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
            'is_active' => ['boolean'],
        ], [], [
            'name' => 'الاسم', 'username' => 'اسم المستخدم', 'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور', 'role' => 'الدور', 'permissions.*' => 'الصلاحية',
            'phone' => 'الجوال', 'avatar_file' => 'الصورة الشخصية',
        ]);
    }
}
