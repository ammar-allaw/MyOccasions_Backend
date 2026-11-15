# توثيق `AuthController`

هذا الملف يشرح الحالة الفعلية الحالية لـ `AuthController` وجميع الـ APIs والدوال المرتبطة به، اعتمادًا على قراءة الـ routes والـ Form Requests والـ Services والـ Repositories والـ Resources والموديلات وإعدادات Sanctum والـ Queue والـ Rate Limiter.

> المسار الأساسي لجميع الروابط أدناه هو `/api`.

## 1. نظرة عامة

الملف المسؤول:

```text
app/Http/Controllers/Api/AuthController.php
```

ملف المسارات:

```text
routes/Api/Auth.php
```

يحتوي الـ Controller على الوظائف التالية:

| الدالة | Method | Endpoint | تحتاج Auth؟ | من يستطيع استخدامها؟ |
|---|---|---|---|---|
| `register` | POST | `/api/register` | لا | Guest يريد إنشاء حساب Client |
| `verifyRegistrationOtp` | POST | `/api/register/verify-otp` | لا | Guest لديه طلب تسجيل ورمز OTP |
| `resendRegistrationOtp` | POST | `/api/register/resend-otp` | لا | Guest لديه طلب تسجيل معلّق |
| `forgotPassword` | POST | `/api/forgot-password` | لا | Guest يريد استعادة حساب Client أو Service Provider |
| `verifyPasswordResetOtp` | POST | `/api/forgot-password/verify-otp` | لا | Guest استلم OTP لنسيان كلمة المرور |
| `resetForgottenPassword` | POST | `/api/forgot-password/reset` | لا | Guest يملك `reset_token` صالحًا |
| `login` | POST | `/api/login` | لا | Client فقط |
| `loginServiceProvider` | POST | `/api/login-service-provider` | لا | Service Provider فقط |
| `profile` | GET | `/api/profile` | نعم: `auth:api` | Client فقط فعليًا |
| `deleteClientAccount` | DELETE | `/api/client/account` | نعم: `auth:api` + `auth.client` | Client فقط |
| `loginOwner` | POST | `/api/login-owner` | لا | Owner |
| `resetPassword` | POST | `/api/reset-password` | نعم: `auth:owner` | Owner فقط |
| `changePassword` | POST | `/api/change-password` | نعم: `auth:api` | Client أو Service Provider |
| `logout` | POST | `/api/logout` | نعم: `auth:api` | Client أو Service Provider |

## 2. الـ Guards والتوكنات

المشروع يستخدم Laravel Sanctum، ويوجد Guardان مهمان هنا:

| Guard | Model / جدول الحساب | الاستخدام |
|---|---|---|
| `api` | `App\Models\User` / `users` | Client وService Provider |
| `owner` | `App\Models\Owner` / `owners` | مالك المنصة |

إعداد `config/sanctum.php` العام هو:

```php
'expiration' => null
```

لكن مدة كل توكن تُحدد أيضًا صراحة عند إنشائه:

| نوع الحساب | مدة التوكن الحالية |
|---|---|
| Client بعد التسجيل | بلا انتهاء، لأن `expiresAt = null` |
| Client عند تسجيل الدخول | بلا انتهاء، لأن `expiresAt = null` |
| Service Provider | 7 أيام |
| Owner | يوم واحد |

التوكن يُرسل بعد تسجيل الدخول بهذه الصيغة:

```http
Authorization: Bearer YOUR_TOKEN
```

## 3. تنسيق الاستجابة العام

العمليات التي تستخدم `Handler` بصورة صحيحة ترجع:

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

والخطأ عادةً:

```json
{
  "success": false,
  "message": "...",
  "data": null
}
```

لكن توجد استثناءات حالية موضحة في قسم الملاحظات، خصوصًا Validation وعمليات تغيير كلمة المرور وتسجيل الخروج.

## 4. تطبيع رقم الهاتف

طلبات التسجيل والتحقق وإعادة الإرسال وتسجيل دخول المستخدم تستخدم Trait باسم:

```text
App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber
```

قبل التحقق يتم حذف الفراغات والشرطات والأقواس، ثم تحويل الرقم إلى الشكل الدولي:

```text
+9639XXXXXXXX
```

أمثلة:

| القيمة المرسلة | القيمة بعد التطبيع |
|---|---|
| `0991555990` | `+963991555990` |
| `963991555990` | `+963991555990` |
| `9630991555990` | `+963991555990` |
| `+9630991555990` | `+963991555990` |
| `00963991555990` | `+963991555990` |

الصيغة النهائية المقبولة هي `+963` ثم رقم يبدأ بـ `9` وبعده 8 أرقام. إدخال `991555990` وحده لا يتم تحويله تلقائيًا في هذا الـ Trait، ولذلك يفشل بالـ validation.

---

# APIs التسجيل والـ OTP

## 5. بدء التسجيل: `register`

### معلومات الـ API

```http
POST /api/register
```

- اسم الـ route: `register`
- Auth: لا يحتاج.
- Rate Limit:
  - 5 طلبات في الدقيقة لكل رقم هاتف.
  - 20 طلبًا في الدقيقة لكل IP.
- اللغة الافتراضية لأخطاء الـ validation والـ OTP: العربية.
- عند إرسال `Accept-Language: en` تصبح الأخطاء بالإنجليزية.

### Request

```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "phone_number": "0991555990",
  "password": "Password123",
  "password_confirmation": "Password123",
  "government_id": 1,
  "role_id": 2
}
```

### Validation

| الحقل | القواعد |
|---|---|
| `first_name` | مطلوب، نص |
| `last_name` | مطلوب، نص |
| `phone_number` | مطلوب، نص، رقم موبايل سوري صحيح، وغير مستخدم من Client فعّال؛ حساب Provider بالرقم نفسه لا يمنع التسجيل، وClient المحذوف يمكن استعادته بعد OTP |
| `role_id` | اختياري، وإذا أُرسل يجب أن يوجد في `roles` |
| `password` | مطلوب، نص، من 9 إلى 50 محرفًا، ومطابق للتأكيد |
| `password_confirmation` | مطلوب، نص، من 9 إلى 50 محرفًا |
| `government_id` | مطلوب، ويجب أن يوجد في `governments` |

### التسلسل الداخلي

```text
RegisterUserRequest
  -> RegistrationOtpService::start()
  -> DB Transaction
  -> PendingRegistrationRepository::updateOrCreateByPhoneNumber()
  -> pending_registrations
  -> SendRegistrationOtpJob على queue باسم otp
  -> AmanGateOtpSender
```

ما يحدث بالتفصيل:

1. يُطبّع رقم الهاتف إلى الصيغة السورية الدولية.
2. تُنفّذ قواعد الـ validation.
3. يُنشأ OTP عشوائي من 6 أرقام باستخدام `random_int`.
4. تُشفّر كلمة المرور ولا تُخزن كنص صريح.
5. يُشفّر رمز OTP ولا يُخزن كنص صريح.
6. يُنشأ أو يُحدّث سجل في `pending_registrations` بالاعتماد على رقم الهاتف.
7. تُضبط المحاولات على `0`.
8. تنتهي صلاحية الرمز بعد 5 دقائق.
9. يُرسل Job مشفّر إلى Queue اسمها `otp` بعد نجاح الـ transaction.
10. تعود الاستجابة مباشرة، ولا تنتظر استجابة Aman Gate لأن الإرسال غير متزامن.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success send otp code",
  "data": {
    "phone_number": "+963991555990",
    "expires_in": 300,
    "resend_after": 60
  }
}
```

`expires_in` و`resend_after` بوحدة الثانية.

### جميع حالات الفشل المهمة

| الحالة | النتيجة |
|---|---|
| حقل مطلوب غير موجود | Validation error، عادةً `422` |
| رقم الهاتف بصيغة غير صحيحة | Validation error |
| رقم الهاتف موجود في `users` | Validation error |
| المحافظة غير موجودة | Validation error |
| كلمة المرور أقل من 9 أو أكثر من 50 | Validation error |
| التأكيد غير مطابق | Validation error |
| تجاوز 5 طلبات لنفس الرقم خلال دقيقة | `429 Too Many Requests` |
| تجاوز 20 طلبًا من نفس IP خلال دقيقة | `429 Too Many Requests` |
| خطأ أثناء حفظ الطلب المعلّق | `400` من catch العام |
| فشل Aman Gate بعد وضع الطلب في Queue | لا يغيّر نجاح طلب `/register`؛ يفشل الـ Job ويعاد حسب سياسة الـ Queue |

### ملاحظات السلوك الحالي

- `role_id` يتم التحقق منه إن أُرسل، لكنه لا يُخزن في `pending_registrations` ولا يُستخدم عند إنشاء الحساب. الحساب الناتج دائمًا Client بدور رقمه `2`.
- استدعاء `/register` مرة أخرى لنفس الرقم قبل التحقق يعمل `updateOrCreate`: يستبدل بيانات التسجيل والرمز السابق ويرسل رمزًا جديدًا، ما دام الطلب لم يصطدم بالـ rate limit.
- شرط `unique` هنا يفحص رقم الهاتف على مستوى جدول `users` كاملًا، لذلك يمنع تسجيل Client جديد إذا كان نفس الرقم مستخدمًا أصلًا في حساب Service Provider.

## 6. التحقق من OTP: `verifyRegistrationOtp`

### معلومات الـ API

```http
POST /api/register/verify-otp
```

- اسم الـ route: `register.verify-otp`
- Auth: لا يحتاج.
- Rate Limit:
  - 10 طلبات في الدقيقة لكل رقم هاتف.
  - 30 طلبًا في الدقيقة لكل IP.

### Request

```json
{
  "phone_number": "0991555990",
  "code": "123456",
  "dive_name": "Samsung A55"
}
```

> اسم الحقل في الكود هو `dive_name` وليس `device_name`، ويجب على الـ frontend استخدام الاسم الحالي كما هو.

### Validation

| الحقل | القواعد |
|---|---|
| `phone_number` | مطلوب، نص، رقم موبايل سوري صحيح |
| `code` | مطلوب، 6 أرقام بالضبط |
| `dive_name` | اختياري، نص، بحد أقصى 255 محرفًا |

### التسلسل الداخلي

1. يبحث عن السجل المعلّق بواسطة رقم الهاتف.
2. يتحقق أن مدة الرمز لم تنتهِ.
3. يتحقق أن عدد المحاولات السابقة أقل من 5.
4. يقارن الرمز المرسل مع `otp_hash` باستخدام `Hash::check`.
5. إذا كان خاطئًا يزيد `attempts` بمقدار واحد.
6. إذا كان صحيحًا يبدأ DB transaction.
7. ينشئ سجلًا في `clients` يتضمن الاسم والمحافظة.
8. ينشئ سجلًا في `users` مربوطًا بالـ Client بعلاقة polymorphic.
9. يضبط `is_provider = false` و`role_id = 2`.
10. إذا وُجد Client محذوف بنفس الرقم، يُستعاد سجل `users` نفسه وتُحدّث بياناته الأساسية وكلمة مروره بدل إنشاء حساب جديد.
11. إذا لم يوجد Client سابق، يُنشأ `Client` و`User` جديدان بالطريقة المعتادة.
12. يحذف سجل `pending_registrations`.
11. ينشئ Sanctum token بلا تاريخ انتهاء.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success registration user",
  "data": {
    "user": {
      "id": 10,
      "phone_number": "+963991555990",
      "role_id": 2,
      "role_name": "client",
      "userable_id": 7,
      "userable_type": "App\\Models\\Client",
      "userable": {
        "first_name": "أحمد",
        "last_name": "محمد",
        "government_id": 1,
        "government_name": "دمشق"
      },
      "images": [],
      "models": null
    },
    "token": "SANCTUM_PLAIN_TEXT_TOKEN"
  }
}
```

الحقول الدقيقة داخل `user` تُنشأ عبر `UserResource` وقد تتضمن حقولًا إضافية بحسب العلاقات المحمّلة واللغة.

### حالات الفشل

| الحالة | Status | البيانات الإضافية |
|---|---:|---|
| لا يوجد طلب تسجيل معلّق لهذا الرقم | `404` | `data: null` |
| انتهت مدة OTP البالغة 5 دقائق | `422` | `data: null` |
| OTP غير صحيح | `422` | `remaining_attempts` |
| وصلت المحاولات السابقة إلى 5 | `429` | `data: null` |
| `code` ليس 6 أرقام | Validation error | أخطاء الحقول |
| تجاوز rate limit للرقم أو IP | `429` | استجابة Laravel throttle |
| فشل إنشاء Client أو User | `400`، أو خطأ قاعدة بيانات بحسب مكان الاستثناء | لا يحذف الطلب المعلق إذا فشلت الـ transaction |

تفصيل عداد المحاولات:

| المحاولة الخاطئة | `remaining_attempts` المرجعة |
|---:|---:|
| 1 | 4 |
| 2 | 3 |
| 3 | 2 |
| 4 | 1 |
| 5 | 0 |
| الطلب التالي بعد ذلك | `429 Maximum OTP attempts exceeded` |

إعادة إرسال OTP تعيد عداد المحاولات إلى صفر، بشرط انتهاء مدة الانتظار الخاصة بإعادة الإرسال.

## 7. إعادة إرسال OTP: `resendRegistrationOtp`

### معلومات الـ API

```http
POST /api/register/resend-otp
```

- اسم الـ route: `register.resend-otp`
- Auth: لا يحتاج.
- Rate Limit:
  - 3 طلبات في الدقيقة لكل رقم.
  - 15 طلبًا في الدقيقة لكل IP.
- يوجد Cooldown داخلي إضافي مدته 60 ثانية.

### Request

```json
{
  "phone_number": "0991555990"
}
```

### التسلسل الداخلي

1. يُطبّع رقم الهاتف.
2. يبحث عن طلب تسجيل معلّق.
3. يتحقق من مرور 60 ثانية على آخر إرسال.
4. ينشئ OTP جديدًا ويلغي فعليًا صلاحية الرمز السابق باستبدال الـ hash.
5. يعيد `attempts` إلى `0`.
6. يمدد الصلاحية 5 دقائق جديدة.
7. يحدّث `last_sent_at`.
8. يرسل Job جديدًا إلى Queue `otp`.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success resend otp code",
  "data": {
    "phone_number": "+963991555990",
    "expires_in": 300,
    "resend_after": 60
  }
}
```

### حالات الفشل

| الحالة | Status | التفاصيل |
|---|---:|---|
| لا يوجد طلب تسجيل معلّق | `404` | يجب بدء التسجيل أولًا |
| لم تمر 60 ثانية على آخر إرسال | `429` | يرجع `retry_after` بعدد الثواني المتبقية |
| رقم الهاتف غير صالح | Validation error | أخطاء الحقول |
| تجاوز 3 طلبات للرقم أو 15 للـ IP في دقيقة | `429` | Rate limiter |
| خطأ أثناء تحديث الطلب | `400` | catch عام |

## 7.1. طلب OTP لنسيان كلمة المرور: `forgotPassword`

### معلومات الـ API

```http
POST /api/forgot-password
```

- اسم الـ route: `password.forgot`.
- Auth: لا يحتاج.
- Rate Limit: 3 طلبات في الدقيقة لكل رقم ونوع حساب، و15 طلبًا في الدقيقة لكل IP.
- يوجد Cooldown داخلي مدته 60 ثانية للحساب نفسه.
- هذه المرحلة ترسل OTP فقط، ولا تغيّر كلمة المرور.

### Request للـ Client

يكفي إرسال رقم الهاتف، لأن النوع الافتراضي هو `client`:

```json
{
  "phone_number": "0991555990"
}
```

### Request للـ Service Provider

```json
{
  "phone_number": "0991555990",
  "account_type": "service_provider"
}
```

القيم المقبولة لـ `account_type` هي `client` و`service_provider` فقط. وجود النوع يمنع الالتباس عندما يوجد حسابان مستقلان بالرقم نفسه.

### التسلسل الداخلي

1. يُطبّع رقم الهاتف إلى `+9639XXXXXXXX`.
2. يبحث عن User مطابق للرقم و`is_provider` الموافق لنوع الحساب.
3. إذا لم يوجد يرجع `404`.
4. يتحقق من مرور 60 ثانية على آخر رمز للحساب نفسه.
5. ينشئ OTP عشوائيًا من 6 أرقام.
6. يخزن Hash الرمز في جدول `password_reset_otps`، ولا يخزن الرمز كنص صريح.
7. يربط السجل بـ `user_id`، مع سجل واحد فقط لكل حساب.
8. يضبط الصلاحية على 5 دقائق والمحاولات على صفر.
9. يرسل Job مشفرًا إلى Queue `otp` باستخدام Aman Gate نفسه.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success send password reset otp code",
  "data": {
    "phone_number": "+963991555990",
    "account_type": "client",
    "expires_in": 300,
    "resend_after": 60
  }
}
```

### حالات الفشل

| الحالة | Status | التفاصيل |
|---|---:|---|
| رقم الهاتف أو صيغته غير صحيحة | Validation error | عربية افتراضيًا وإنجليزية مع `Accept-Language: en` |
| `account_type` غير مقبول | Validation error | يجب أن يكون `client` أو `service_provider` |
| لا يوجد حساب مطابق للرقم والنوع | `404` | لا يتم إرسال OTP |
| إعادة الطلب قبل مرور 60 ثانية | `429` | يرجع `retry_after` |
| تجاوز Rate Limit | `429` | يمنع الإساءة على مستوى الرقم والنوع والـ IP |
| خطأ داخلي أثناء التخزين | `400` | لا يرسل الـ Job إذا فشلت الـ transaction |

## 7.2. التحقق من OTP لنسيان كلمة المرور: `verifyPasswordResetOtp`

### معلومات الـ API

```http
POST /api/forgot-password/verify-otp
```

- اسم الـ route: `password.verify-otp`.
- Auth: لا يحتاج.
- Rate Limit: 10 طلبات في الدقيقة لكل رقم ونوع حساب، و30 طلبًا في الدقيقة لكل IP.
- الحد الأقصى لمحاولات OTP الخاطئة: 5.

### Request للـ Client

```json
{
  "phone_number": "0991555990",
  "code": "123456"
}
```

### Request للـ Service Provider

```json
{
  "phone_number": "0991555990",
  "account_type": "service_provider",
  "code": "123456"
}
```

إذا لم يُرسل `account_type` تكون قيمته `client` افتراضيًا.

### التسلسل الداخلي

1. يحدد User من رقم الهاتف ونوع الحساب.
2. يقفل سجل الاستعادة أثناء المعالجة لمنع طلبين متزامنين من استخدام OTP نفسه.
3. يتحقق من وجود الطلب، وعدم استخدام OTP سابقًا، وعدم انتهاء مدة 5 دقائق.
4. يتحقق أن المحاولات أقل من 5.
5. عند الخطأ يزيد `attempts` وتُحفظ الزيادة قبل إعادة الخطأ.
6. عند النجاح يولد `reset_token` عشوائيًا بطول 64 محرفًا.
7. يخزن SHA-256 Hash للتوكن فقط، ولا يخزن التوكن الصريح.
8. تنتهي صلاحية `reset_token` بعد 10 دقائق.
9. يصبح OTP مستخدمًا ولا يمكن التحقق به مرة ثانية.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success verify password reset otp",
  "data": {
    "reset_token": "64_CHARACTER_SECURE_TOKEN",
    "expires_in": 600
  }
}
```

يجب أن يحتفظ الـ frontend بـ `reset_token` مؤقتًا ويرسله مباشرة إلى API تعيين كلمة المرور. لا يجب تخزينه تخزينًا دائمًا.

### حالات الفشل

| الحالة | Status | التفاصيل |
|---|---:|---|
| Request غير صالح | Validation error | الرقم والنوع والرمز تخضع للتحقق |
| لا يوجد User مطابق للرقم والنوع | `404` | `user_not_found` |
| لا يوجد طلب نسيان كلمة مرور | `404` | يجب استدعاء `/forgot-password` أولًا |
| انتهت صلاحية OTP | `422` | يجب طلب OTP جديد |
| OTP خاطئ | `422` | يرجع `remaining_attempts` |
| تمت 5 محاولات خاطئة | `429` | يجب طلب OTP جديد بعد مدة الإرسال |
| تم استخدام OTP مسبقًا | `422` | لا يمكن إصدار `reset_token` ثانٍ منه |
| تجاوز Rate Limit | `429` | حماية للرقم والنوع والـ IP |

## 7.3. تعيين كلمة المرور الجديدة: `resetForgottenPassword`

### معلومات الـ API

```http
POST /api/forgot-password/reset
```

- اسم الـ route: `password.reset`.
- Auth: لا يحتاج؛ المصادقة المؤقتة هنا هي `reset_token`.
- Rate Limit: 5 طلبات في الدقيقة لكل reset token و15 طلبًا في الدقيقة لكل IP.
- `reset_token` صالح لمدة 10 دقائق ولمرة واحدة فقط.

### Request

```json
{
  "reset_token": "64_CHARACTER_SECURE_TOKEN",
  "new_password": "NewPassword@123",
  "new_password_confirmation": "NewPassword@123"
}
```

### قواعد كلمة المرور

- من 8 إلى 50 محرفًا.
- تحتوي على حرف إنجليزي كبير واحد على الأقل.
- تحتوي على رقم واحد على الأقل.
- تحتوي على رمز خاص واحد على الأقل.
- تطابق `new_password_confirmation`.

### التسلسل الداخلي

1. يحول `reset_token` المرسل إلى SHA-256 Hash.
2. يبحث عن السجل ويقفله أثناء التنفيذ لمنع استخدامه مرتين بالتزامن.
3. يتحقق أن OTP تم التحقق منه وأن reset token لم ينتهِ.
4. يغيّر كلمة مرور `user_id` المرتبط بالسجل تحديدًا.
5. يلغي جميع Sanctum tokens القديمة لذلك الحساب لحمايته.
6. يحذف سجل `password_reset_otps` كاملًا.
7. يصبح `reset_token` غير قابل لإعادة الاستخدام.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success reset password",
  "data": null
}
```

بعد النجاح يجب توجيه المستخدم إلى شاشة تسجيل الدخول وإدخال كلمة المرور الجديدة.

### حالات الفشل

| الحالة | Status | التفاصيل |
|---|---:|---|
| `reset_token` ناقص أو ليس بطول 64 | Validation error | لا تصل العملية إلى Service |
| كلمة المرور لا تحقق الشروط | Validation error | رسالة عربية افتراضيًا أو إنجليزية |
| تأكيد كلمة المرور غير مطابق | Validation error | لا تتغير كلمة المرور |
| reset token غير موجود أو مستخدم | `422` | `invalid_reset_token` |
| انتهت مدة 10 دقائق | `422` | يجب بدء التدفق من `/forgot-password` |
| تجاوز Rate Limit | `429` | حماية للتوكن والـ IP |
| فشل تحديث كلمة المرور أو حذف التوكنات | `400` | تعمل العملية داخل transaction ويُلغى كامل التغيير |

## 8. إرسال OTP في الخلفية

الـ Jobs المستخدمة:

```text
App\Jobs\SendRegistrationOtpJob
App\Jobs\SendPasswordResetOtpJob
```

خصائصه الحالية:

| الخاصية | القيمة |
|---|---|
| Queue | `otp` |
| تشفير Payload | نعم، عبر `ShouldBeEncrypted` |
| عدد المحاولات | 3 |
| Backoff | 10 ثم 30 ثم 60 ثانية |
| Timeout | 20 ثانية |
| التنفيذ | بعد نجاح DB transaction |

يقوم `AmanGateOtpSender` بإرسال:

```json
{
  "gsm": "963991555990",
  "template_id": 1,
  "code": "123456",
  "language": 1
}
```

- علامة `+` تُحذف قبل إرسال الرقم إلى Aman Gate.
- Token وTemplate ID وBase URL واللغة تُقرأ من `config/services.php` والـ `.env`.
- مهلة HTTP إلى Aman Gate هي 10 ثوانٍ.
- إذا لم تكن الإعدادات موجودة يفشل الـ Job.
- إذا رجع المزود Status غير ناجح يفشل الـ Job ويُعاد حسب سياسة المحاولات.

---

# APIs تسجيل الدخول

## 9. دخول Client: `login`

### معلومات الـ API

```http
POST /api/login
```

- اسم الـ route: `login`
- Auth: لا يحتاج.
- الحساب المسموح: Client فقط.

### Request

```json
{
  "phone_number": "0991555990",
  "password": "Password123",
  "dive_name": "Samsung A55"
}
```

### Validation

| الحقل | القواعد الحالية |
|---|---|
| `phone_number` | مطلوب، نص، رقم سوري صحيح، ويجب أن يوجد في جدول `users` |
| `password` | مطلوب فقط |
| `dive_name` | لا توجد له قاعدة validation في `LoginUserRequest` |

### طريقة اختيار الحساب عند تكرار الرقم

الـ Repository ينفذ:

```text
where phone_number = الرقم
orderBy is_provider تصاعديًا
first
```

وبما أن `false = 0` و`true = 1`، فإن حساب Client يُختار أولًا عندما يوجد Client وService Provider بنفس الرقم.

بعد ذلك توجد حماية إضافية: إذا كان الحساب المختار `is_provider = true` تُرجع استجابة User not found، ولذلك هذا endpoint لا يسمح بدخول مزود الخدمة.

### نجاح العملية: `200`

1. يتحقق من كلمة المرور عبر `Hash::check`.
2. يحمّل علاقة `role`.
3. ينشئ توكن بلا تاريخ انتهاء.
4. يرجع المستخدم عبر `UserResource`.

```json
{
  "success": true,
  "message": "success login",
  "data": {
    "user": {},
    "token": "SANCTUM_PLAIN_TEXT_TOKEN"
  }
}
```

### حالات الفشل

| الحالة | Status المتوقع حاليًا | السبب |
|---|---:|---|
| رقم غير موجود في `users` | غالبًا Validation `422` | قاعدة `exists` تعمل قبل دخول الـ controller |
| الرقم موجود فقط كـ Service Provider | `404` | الـ controller يمنع `is_provider = true` ويعرضه كأنه غير موجود |
| كلمة المرور غير صحيحة | `422` | `invalid_credentials` |
| الحساب محذوف Soft Delete | Validation قد يرى الرقم في DB، ثم الـ Eloquent لا يجده ويرجع `404` | `exists` لا يطبق Global Scope، بينما Model يطبقه |
| خطأ داخلي آخر | `404` حسب catch الحالي | الـ catch العام يثبت status على 404 |

### اللغة

رسائل `user_not_found` و`invalid_credentials` تدعم بالترتيب:

1. Header باسم `localization`.
2. Header القديم المكتوب `localiztion`.
3. `Accept-Language`.
4. العربية افتراضيًا.

أي قيمة تبدأ بـ `en` تستخدم الإنجليزية، وما عدا ذلك يستخدم العربية.

## 10. دخول Service Provider: `loginServiceProvider`

### معلومات الـ API

```http
POST /api/login-service-provider
```

- اسم الـ route: `login-service-provider`
- Auth: لا يحتاج.
- الحساب المسموح: Service Provider فقط.

### Request

نفس طلب دخول العميل:

```json
{
  "phone_number": "0991555990",
  "password": "Password123",
  "dive_name": "Chrome Dashboard"
}
```

### التسلسل الداخلي

1. يستخدم `LoginUserRequest` نفسه.
2. يبحث عن الرقم مع شرط `is_provider = true`.
3. إذا لم يجد مزود خدمة يرمي Service استثناء `the user not found`.
4. يقارن كلمة المرور.
5. يحمّل الدور.
6. ينشئ توكن ينتهي بعد 7 أيام.
7. يرجع `UserResource`.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success service provider login",
  "data": {
    "user": {},
    "token": "SANCTUM_PLAIN_TEXT_TOKEN"
  }
}
```

### حالات الفشل

| الحالة | Status |
|---|---:|
| الرقم غير موجود إطلاقًا في `users` | Validation غالبًا `422` |
| الرقم موجود كـ Client فقط ولا يوجد Provider بنفسه | `404` |
| كلمة المرور غير صحيحة | `422` |
| Provider محذوف Soft Delete | `404` بعد البحث |
| خطأ آخر داخل الدالة | `404` بسبب catch الحالي |

عندما يوجد Client وService Provider بنفس الرقم، هذا endpoint يختار سجل مزود الخدمة تحديدًا، ولا يتأثر بسجل Client.

## 11. دخول Owner: `loginOwner`

### معلومات الـ API

```http
POST /api/login-owner
```

- اسم الـ route الحالي: `/login-owner`.
- Auth: لا يحتاج.
- الحساب المسموح: Owner من جدول `owners`.

### Request

```json
{
  "email": "owner@example.com",
  "password": "OwnerPassword",
  "dive_name": "Admin Dashboard"
}
```

### Validation

| الحقل | القواعد |
|---|---|
| `email` | مطلوب، بريد إلكتروني صحيح |
| `password` | مطلوب |

### التسلسل الداخلي

1. يبحث `OwnerService::findByEmail` مباشرة في `owners`.
2. يتحقق من كلمة المرور.
3. يحمّل علاقة `role`.
4. ينشئ توكن ينتهي بعد يوم واحد.
5. يخفي `password` و`role` من كائن Owner المرجع.
6. يرجع اسم الدور الإنجليزي في `role_en`.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success owner login",
  "data": {
    "owner": {
      "id": 1,
      "name": "Owner",
      "email": "owner@example.com",
      "role_id": 1
    },
    "role_en": "owner",
    "token": "SANCTUM_PLAIN_TEXT_TOKEN"
  }
}
```

### حالات الفشل

| الحالة | Status |
|---|---:|
| Email غير صالح أو الحقول ناقصة | Validation error |
| البريد غير موجود | `401 Invalid credentials` |
| كلمة المرور خاطئة | `401 Invalid credentials` |
| خطأ آخر | `401` حسب catch الحالي |

هذه الرسائل لا تستخدم حاليًا نظام الترجمة الموجود لدخول المستخدم.

---

# APIs الملف الشخصي وكلمة المرور والخروج

## 12. ملف Client: `profile`

### معلومات الـ API

```http
GET /api/profile
Authorization: Bearer CLIENT_TOKEN
```

- اسم الـ route: `profile`.
- Middleware: `auth:api`.
- يمكن للتوكن أن يمر تقنيًا إذا كان Client أو Provider لأنهما يستخدمان Guard نفسه.
- الـ Service يسمح فعليًا بـ Client فقط.

### التسلسل الداخلي

1. `auth:api` يستخرج المستخدم من Sanctum token.
2. `AuthService::authUser()` يرجع مستخدم Guard `api`.
3. الـ Repository يبحث عن المستخدم نفسه بشرط `is_provider = false`.
4. يحمّل `userable.government` و`role` مسبقًا.
5. يتحقق أن `userable_type` هو `App\Models\Client`.
6. يعرض النتيجة عبر `ClientProfileResource`.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "success get profile",
  "data": {
    "profile": {
      "id": 10,
      "first_name": "أحمد",
      "last_name": "محمد",
      "full_name": "أحمد محمد",
      "phone_number": "+963991555990",
      "government": {
        "id": 1,
        "name": "دمشق"
      }
    }
  }
}
```

اللغة:

- العربية افتراضيًا لاسم المحافظة فقط.
- `Accept-Language: en` يرجع اسم المحافظة بالإنجليزية.
- يدعم أيضًا `localization` و`localiztion` في هذا الـ Resource.

### حالات الفشل

| الحالة | Status |
|---|---:|
| لا يوجد Bearer token | `401` من middleware |
| التوكن غير صالح أو منتهي | `401` |
| التوكن يعود إلى Service Provider | `403 Only clients can access this profile` |
| User غير مربوط بـ Client بشكل صحيح | `403` |
| خطأ آخر | `400` |

## 12.1 حذف حساب Client: `deleteClientAccount`

### معلومات الـ API

```http
DELETE /api/client/account
Authorization: Bearer CLIENT_TOKEN
Accept-Language: ar
```

- اسم الـ route: `client.account.destroy`.
- Middleware: `auth:api` ثم `auth.client` ثم `throttle:account-deletion`.
- متاح للـ Client فقط، ولا يستقبل `user_id` من الطلب.
- Rate Limit: 5 محاولات بالدقيقة للحساب و20 محاولة بالدقيقة للـ IP.

### Request

```json
{
  "current_password": "CurrentPassword123"
}
```

### التسلسل الداخلي

1. يتحقق `DeleteClientAccountRequest` من وجود كلمة المرور الحالية.
2. تؤخذ هوية الحساب من Sanctum token حصراً.
3. يتأكد `UserService` أن الحساب Client وأن `userable_type` يساوي `App\\Models\\Client`.
4. يتحقق من كلمة المرور بواسطة `Hash::check`.
5. داخل DB transaction تُحذف إعجابات العميل وتُنقص عدادات `likes_count` المقابلة دون النزول تحت الصفر.
6. تُحذف سجلات `model_views` الخاصة بالعميل، بينما يبقى إجمالي `views_count` محفوظاً كإحصائية مجهولة.
7. تُحذف جميع `personal_access_tokens` الخاصة بالحساب.
8. يُنفذ Soft Delete على سجل `users`، ولا يُحذف سجل `clients` الأساسي.

### نجاح العملية: `200`

```json
{
  "success": true,
  "message": "تم حذف الحساب بنجاح",
  "data": null
}
```

مع `Accept-Language: en` تصبح الرسالة `Account deleted successfully`.

### حالات الفشل

| الحالة | Status |
|---|---:|
| لا يوجد Token أو Token غير صالح | `401` |
| الحساب Service Provider | `403` |
| `current_password` غير موجود | `422` Validation |
| كلمة المرور الحالية غير صحيحة | `422` |
| تجاوز Rate Limit | `429` |

### التسجيل بعد الحذف

عند التسجيل مجدداً بالرقم نفسه، يسمح Validation ببدء OTP لأن Client القديم محذوف. بعد نجاح OTP يستعيد النظام سجل `users` نفسه ويحدّث الاسم والمحافظة وكلمة المرور، ثم يصدر Token جديداً. لا تُستعاد الإعجابات أو سجلات المشاهدة الشخصية أو التوكنات القديمة، ولا يتأثر Service Provider الذي يحمل الرقم نفسه.

## 13. إعادة تعيين كلمة المرور بواسطة Owner: `resetPassword`

### معلومات الـ API

```http
POST /api/reset-password
Authorization: Bearer OWNER_TOKEN
```

- Middleware: `auth:owner`.
- Owner فقط يستطيع الوصول.

### Request

```json
{
  "phone_number": "+963991555990",
  "new_password": "NewPassword123",
  "new_password_confirmation": "NewPassword123"
}
```

### Validation الحالية

| الحقل | القواعد |
|---|---|
| `phone_number` | مطلوب، وموجود في `users.phone_number` |
| `new_password` | مطلوب، نص، ومطابق لـ `new_password_confirmation` |

لا يوجد حاليًا حد أدنى أو أقصى لكلمة المرور، ولا يتم تطبيق تطبيع الرقم السوري في هذه الدالة.

### التسلسل الداخلي

1. يتحقق من الحقول داخل الـ controller باستخدام `$request->validate`.
2. ينفذ `User::where(phone_number)->first()` مباشرة.
3. يغيّر كلمة مرور أول User يجده بهذا الرقم.
4. يحفظها بعد `Hash::make`.

### حالات الفشل

| الحالة | النتيجة الحالية |
|---|---|
| بدون Owner token | `401` من middleware |
| الرقم غير موجود | validation يُمسك داخل catch ويرجع `400` |
| التأكيد غير مطابق | `400` |
| خطأ قاعدة بيانات | `400` |

### شكل النجاح الفعلي الحالي

بسبب ترتيب المعاملات المرسل حاليًا إلى `successResponse`، الاستجابة ليست بالشكل المقصود، بل تكون تقريبًا:

```json
{
  "success": "success change password",
  "message": null,
  "data": true
}
```

### ملاحظة مهمة عند تكرار رقم الهاتف

إذا كان Client وService Provider يملكان الرقم نفسه، فالدالة لا تحدد `is_provider` ولا `role_id`، وتستخدم `first()` دون ترتيب. لذلك ستغيّر كلمة مرور أول سجل تعيده قاعدة البيانات، وليس بالضرورة الحساب الذي يقصده الـ Owner.

## 14. تغيير كلمة مرور الحساب الحالي: `changePassword`

### معلومات الـ API

```http
POST /api/change-password
Authorization: Bearer USER_TOKEN
```

- Middleware: `auth:api`.
- Client أو Service Provider.
- كل حساب مستقل عن الآخر حتى لو كان لهما رقم الهاتف نفسه، لأن المستخدم يُحدد من التوكن وليس من رقم الهاتف.

### Request

```json
{
  "current_password": "OldPassword123",
  "new_password": "NewPassword123",
  "new_password_confirmation": "NewPassword123"
}
```

### Validation الحالية

| الحقل | القواعد |
|---|---|
| `current_password` | مطلوب، نص |
| `new_password` | مطلوب، نص، confirmed |

لا يوجد حد أدنى أو أقصى لكلمة المرور الجديدة حاليًا.

### التسلسل الداخلي

1. يستخرج User من التوكن عبر `$request->user()`.
2. يقارن كلمة المرور الحالية.
3. إذا كانت صحيحة يغيّر كلمة مرور هذا السجل فقط.
4. لا يحذف التوكنات القديمة بعد تغيير كلمة المرور.

### الحالات

| الحالة | النتيجة الحالية |
|---|---|
| لا يوجد توكن | `401` من middleware |
| كلمة المرور الحالية خاطئة | `400 Current password is incorrect` |
| التأكيد غير مطابق أو حقل ناقص | `400` بسبب catch داخل الدالة |
| نجاح | HTTP `200` |

شكل النجاح الفعلي الحالي، لنفس سبب ترتيب معاملات `successResponse`:

```json
{
  "success": "success change password",
  "message": null,
  "data": true
}
```

## 15. تسجيل الخروج: `logout`

### معلومات الـ API

```http
POST /api/logout
Authorization: Bearer USER_TOKEN
```

- Middleware: `auth:api`.
- Client أو Service Provider.
- لا يعمل لتوكن Owner لأن المسار لا يستخدم `auth:owner`.

### ما الذي يحذفه؟

1. يحذف التوكن الحالي فقط عبر `currentAccessToken()->delete()`.
2. يحذف أيضًا جميع التوكنات المنتهية التابعة لنفس المستخدم.
3. لا يحذف التوكنات السليمة الأخرى على أجهزة أخرى.

بعد النجاح يصبح التوكن المستخدم في الطلب غير صالح.

شكل النجاح الفعلي الحالي:

```json
{
  "success": "success logout",
  "message": null,
  "data": true
}
```

حالات الفشل:

| الحالة | Status |
|---|---:|
| لا يوجد توكن أو التوكن غير صالح | `401` |
| فشل حذف التوكن | `400` |

---

# الدوال الداخلية في `AuthController`

## 16. `__construct`

يحقن الاعتماديات التالية:

| الاعتمادية | الوظيفة |
|---|---|
| `Handler` | توحيد استجابات النجاح والفشل |
| `UserServiceInterface` | البحث عن المستخدم، إنشاء Client/User، وجلب profile |
| `OwnerService` | البحث عن Owner بالبريد |
| `RegistrationOtpServiceInterface` | بدء وإعادة إرسال والتحقق من OTP |
| `PasswordResetOtpServiceInterface` | إرسال OTP والتحقق منه وإصدار reset token وتعيين كلمة المرور الجديدة |

الربط في `AppServiceProvider` يحوّل الواجهات إلى implementations الفعلية:

```text
UserServiceInterface -> UserService
RegistrationOtpServiceInterface -> RegistrationOtpService
```

## 17. `loginMessage`

دالة خاصة تستخدمها عمليات دخول Client وService Provider لإرجاع رسالتين:

| المفتاح | العربية | الإنجليزية |
|---|---|---|
| `user_not_found` | المستخدم غير موجود | User not found |
| `invalid_credentials` | بيانات تسجيل الدخول غير صحيحة | Invalid credentials |

الأولوية للـ headers هي:

```text
localization -> localiztion -> Accept-Language -> ar
```

---

# الملفات المرتبطة

## 18. الملفات التي تمت قراءتها لتكوين هذا التوثيق

### Controller وRoutes

```text
app/Http/Controllers/Api/AuthController.php
routes/Api/Auth.php
routes/api.php
```

### Form Requests وتطبيع الهاتف

```text
app/Http/Requests/Auth/LoginUserRequest.php
app/Http/Requests/Auth/LoginOwnerRequest.php
app/Http/Requests/Auth/RegisterUserRequest.php
app/Http/Requests/Auth/VerifyRegistrationOtpRequest.php
app/Http/Requests/Auth/ResendRegistrationOtpRequest.php
app/Http/Requests/Auth/ForgotPasswordRequest.php
app/Http/Requests/Auth/VerifyPasswordResetOtpRequest.php
app/Http/Requests/Auth/ResetForgottenPasswordRequest.php
app/Http/Requests/Concerns/NormalizesSyrianPhoneNumber.php
```

### Services

```text
app/Services/User/Interface/UserServiceInterface.php
app/Services/User/Implementation/UserService.php
app/Services/Owner/OwnerService.php
app/Services/Auth/AuthService.php
app/Services/Otp/Interface/RegistrationOtpServiceInterface.php
app/Services/Otp/Implementation/RegistrationOtpService.php
app/Services/Otp/Implementation/AmanGateOtpSender.php
app/Services/Otp/Interface/PasswordResetOtpServiceInterface.php
app/Services/Otp/Implementation/PasswordResetOtpService.php
```

### Repositories

```text
app/Repositories/User/Interface/UserRepositoryInterface.php
app/Repositories/User/Implementation/UserRepository.php
app/Repositories/PendingRegistration/Interface/PendingRegistrationRepositoryInterface.php
app/Repositories/PendingRegistration/Implementation/PendingRegistrationRepository.php
app/Repositories/PasswordResetOtp/Interface/PasswordResetOtpRepositoryInterface.php
app/Repositories/PasswordResetOtp/Implementation/PasswordResetOtpRepository.php
```

### Resources وModels وJob

```text
app/Http/Resources/User/UserResource.php
app/Http/Resources/Auth/ClientProfileResource.php
app/Models/User.php
app/Models/Client.php
app/Models/Owner.php
app/Models/PendingRegistration.php
app/Models/PasswordResetOtp.php
app/Jobs/SendRegistrationOtpJob.php
app/Jobs/SendPasswordResetOtpJob.php
```

### Migrations

```text
database/migrations/2026_11_08_000000_create_password_reset_otps_table.php
database/migrations/2026_11_08_000001_add_verification_fields_to_password_reset_otps_table.php
```

### الإعدادات

```text
app/Providers/RouteServiceProvider.php
app/Providers/AppServiceProvider.php
app/Exceptions/Handler.php
config/auth.php
config/sanctum.php
config/queue.php
config/services.php
```

---

# ملاحظات فنية على الوضع الحالي

هذه الملاحظات تصف الكود كما هو، ولم يتم تعديل أي Logic أثناء إعداد الملف:

1. `resetPassword` و`changePassword` تستخدمان Validation داخل الـ controller بدل FormRequest مستقل.
2. `resetPassword` يستخدم Eloquent مباشرة في الـ controller، ولا يمر عبر Service/Repository.
3. `OwnerService::findByEmail` يستخدم Eloquent مباشرة داخل Service ولا يوجد OwnerRepository لهذه العملية.
4. نجاح `resetPassword` و`changePassword` و`logout` يمرر معاملات `successResponse` بترتيب غير صحيح، لذلك قيمة `success` تصبح نصًا و`message` تصبح `null`.
5. `resetPassword` غير حاسم عند وجود حسابين بنفس رقم الهاتف، لأنه لا يحدد Client أو Provider.
6. `LoginUserRequest` يملك قاعدة `exists`، لذلك حالة الرقم غير الموجود غالبًا تتوقف في validation قبل رسالة `user_not_found` الموجودة داخل الـ controller.
7. `login` و`loginServiceProvider` يحولان أغلب الاستثناءات غير المتوقعة إلى `404`، حتى لو كان الخطأ داخليًا وليس عدم وجود حساب.
8. `changePassword` يرجع `400` لكلمة المرور الحالية الخاطئة، وليس `401` أو `422`.
9. لا يتم إلغاء بقية توكنات الحساب بعد تغيير كلمة المرور.
10. `/logout` لا يدعم Owner حاليًا.
11. نجاح OTP يُرجع رسالة إنجليزية حاليًا، بينما أخطاء OTP فقط تدعم العربية افتراضيًا والإنجليزية عبر `Accept-Language`.
12. `Handler` يستورد `Dotenv\Exception\ValidationException` بدل `Illuminate\Validation\ValidationException`، ولذلك معالجة أخطاء FormRequest قد لا تمر من فرع Validation المخصص داخله.
13. `UserResource` كبير ومتغير بحسب نوع الحساب والـ guard والعلاقات المحمّلة، لذلك شكل `user` قد يتوسع في بعض السياقات.
14. إرسال OTP غير متزامن: نجاح `/register` أو `/resend-otp` يعني أن الـ Job وُضع في الـ Queue، وليس ضمانًا أن رسالة SMS وصلت بالفعل.
15. لا يوجد قفل row lock أثناء التحقق من OTP؛ الطلبات المتزامنة جدًا لنفس الرمز تعتمد على قيود قاعدة البيانات والـ transaction لمنع إنشاء بيانات مكررة.

## 19. خلاصة تدفق التسجيل

```text
POST /register
  -> حفظ بيانات مؤقتة + OTP مشفر
  -> Queue otp
  -> SMS عبر Aman Gate

POST /register/verify-otp
  -> فحص الطلب والمدة والمحاولات والرمز
  -> إنشاء Client
  -> إنشاء User بدور client
  -> حذف الطلب المؤقت
  -> إنشاء Token دائم

POST /register/resend-otp
  -> فحص الطلب ومدة الانتظار
  -> استبدال OTP
  -> تصفير المحاولات وتجديد المدة
  -> Queue otp
```
