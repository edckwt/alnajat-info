<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * حقل «منشور/ظاهر» يتغيّر فقط لمن يملك صلاحية «الوحدة.publish».
 * من لا يملكها: السجل الموجود يبقى على حالته، والجديد يُحفظ مخفياً حتى ينشره غيره.
 */
trait GuardsPublishing
{
    protected function guardPublishing(Model $model, string $permission, Request $request): void
    {
        if (! $model->isDirty('is_active')) {
            return;
        }

        if ($request->user()->can("$permission.publish", $model->exists ? [$model] : [])) {
            return;
        }

        $model->is_active = $model->exists ? (bool) $model->getOriginal('is_active') : false;
    }
}
