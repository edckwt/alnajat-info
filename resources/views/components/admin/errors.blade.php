@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <x-admin.icon name="alert" class="w-5 h-5 shrink-0" />
        <div>
            <p class="font-bold">لم يُحفظ:</p>
            <ul class="mt-1 space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
