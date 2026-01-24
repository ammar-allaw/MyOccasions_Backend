# إضافة خاصية سبب الرفض (Rejection Reason)

## الملفات التي تم تعديلها:

### 1. Migration
- **الملف**: `database/migrations/2025_11_30_000001_add_rejection_reason_to_order_statuses_table.php`
- **التغيير**: إضافة عمود `rejection_reason` إلى جدول `order_statuses`

### 2. Request Classes
تم إضافة validation rule للـ `rejection_reason` في:
- `app/Http/Requests/Hall/UpdateRoomRequest.php`
- `app/Http/Requests/Hall/UpdateServiceRequest.php`
- `app/Http/Requests/ServiceProvider/UpdateServiceProviderRequest.php`

**Validation Rule**: 
```php
'rejection_reason' => 'nullable|string|required_if:status_id,2'
```
- مطلوب فقط عندما يكون `status_id = 2` (rejected)

### 3. Controllers
تم تعديل معالجة `status_id` في:

#### a) HallController.php
- **updateRoom()**: إضافة معالجة `rejection_reason`
- **updateService()**: إضافة معالجة `rejection_reason`

#### b) ServiceProviderController.php
- **updateServiceProvider()**: إضافة معالجة `rejection_reason`

**المنطق**:
```php
if ($data['status_id'] == 2 && isset($data['rejection_reason'])) {
    $model->orderStatusAble->rejection_reason = $data['rejection_reason'];
} else {
    $model->orderStatusAble->rejection_reason = null;
}
```

### 4. Resources
تم إضافة `rejection_reason` في response لـ:
- `app/Http/Resources/Hall/RoomResource.php`
- `app/Http/Resources/Hall/ServiceResource.php`
- `app/Http/Resources/User/ServiceProvideResource.php`
- `app/Http/Resources/User/UserResource.php`

## خطوات التنفيذ:

### 1. تشغيل Migration
```bash
php artisan migrate
```

### 2. API Usage

#### مثال: رفض Room
```http
POST /api/hall/update-room/{roomId}
Authorization: Bearer {owner_token}
Content-Type: application/json

{
    "status_id": 2,
    "rejection_reason": "الصور غير واضحة ويجب تحسين الوصف"
}
```

#### مثال: رفض Service
```http
POST /api/hall/update-service/{serviceId}
Authorization: Bearer {owner_token}
Content-Type: application/json

{
    "status_id": 2,
    "rejection_reason": "السعر غير مناسب للخدمة المقدمة"
}
```

#### مثال: رفض Service Provider
```http
POST /api/service-provider/update-service-provider/{serviceProviderId}
Authorization: Bearer {owner_token}
Content-Type: application/json

{
    "status_id": 2,
    "rejection_reason": "المعلومات غير كاملة ويجب إضافة صور أكثر"
}
```

### 3. Response Format
```json
{
    "order_status": {
        "order_status_id": 8,
        "status_id": 2,
        "name": "مرفوض",
        "name_en": "rejected",
        "change_description": "تم تعديل الحالة",
        "last_modified_at": "2025-11-30T10:00:00.000000Z",
        "rejection_reason": "الصور غير واضحة ويجب تحسين الوصف"
    }
}
```

## ملاحظات مهمة:

1. **فقط للـ Owner**: تعديل `status_id` و `rejection_reason` متاح فقط لـ owner
2. **إلزامي عند الرفض**: `rejection_reason` مطلوب فقط عندما `status_id = 2`
3. **يتم مسحه**: عند تغيير الحالة لغير "مرفوض"، يتم مسح `rejection_reason`
4. **الصالة تستطيع رؤيته**: الـ hall يمكنه رؤية سبب الرفض في الـ response

## Status IDs:
- 1 = accepted (مقبول)
- 2 = rejected (مرفوض)
- 3 = under_review (قيد المراجعة)
