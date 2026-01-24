<?php

namespace App\Traits;

use App\Models\Status;
use Illuminate\Http\Request;

trait TracksChanges
{
    /**
     * تتبع التغييرات وتحديث الـ status إلى under_review
     * 
     * @param mixed $model النموذج المراد تتبع تغييراته (Room, Service, etc.)
     * @param array $data البيانات الجديدة
     * @param Request $request الـ Request object
     * @param array $trackableFields الحقول المراد تتبعها
     * @return void
     */
    protected function trackChangesAndUpdateStatus($model, array $data, Request $request, array $trackableFields = [])
    {
        $changes = [];

        // تتبع التغييرات في الحقول النصية
        foreach ($trackableFields as $field => $label) {
            if (isset($data[$field]) && $model->{$field} != $data[$field]) {
                $oldValue = $model->{$field};
                $newValue = $data[$field];
                $changes[] = "{$label} من '{$oldValue}' إلى '{$newValue}'";
            }
        }

        // تتبع التغييرات في الصور
        if ($request->hasFile('image')) {
            $imageCount = is_array($request->file('image')) ? count($request->file('image')) : 1;
            $changes[] = "إضافة {$imageCount} صورة جديدة";
        }

        if (isset($data['image_id'])) {
            $idCount = is_array($data['image_id']) ? count($data['image_id']) : 1;
            $changes[] = "استبدال {$idCount} صورة";
        }

        if (isset($data['replace_all']) && $data['replace_all']) {
            $changes[] = "استبدال جميع الصور";
        }

        // إذا كان هناك تغييرات، نحدّث الـ status
        if (!empty($changes)) {
            $model->load('orderStatusAble');
            $underReviewStatus = Status::where('name_en', 'under_review')->first();

            if ($model->orderStatusAble && $underReviewStatus) {
                $changeDescription = "تم تعديل: " . implode(', ', $changes);
                $model->orderStatusAble->update([
                    'status_id' => $underReviewStatus->id,
                    'change_description' => $changeDescription,
                    'last_modified_at' => now(),
                ]);
            } elseif (!$model->orderStatusAble && $underReviewStatus) {
                // إنشاء OrderStatus إذا لم تكن موجودة
                $changeDescription = "تم تعديل: " . implode(', ', $changes);
                $model->orderStatusAble()->create([
                    'status_id' => $underReviewStatus->id,
                    'change_description' => $changeDescription,
                    'last_modified_at' => now(),
                ]);
            }
        }
    }

    /**
     * الحصول على النموذج الأصلي قبل التعديل
     * 
     * @param string $modelClass اسم الكلاس (Room::class, Service::class)
     * @param int $id معرف النموذج
     * @return mixed
     */
    protected function getOriginalModel(string $modelClass, int $id)
    {
        return $modelClass::find($id);
    }
}
