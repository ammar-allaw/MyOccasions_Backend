# Registration OTP Flow

هذا الملف يشرح كل حالات تسجيل حساب العميل عبر OTP للفرونت، حسب التنفيذ الحالي في الباك إند.

## ملاحظات عامة

- التسجيل هنا خاص بحساب العميل `client`.
- لا يتم إنشاء المستخدم في جدول `users` مباشرة عند `/api/register`.
- عند `/api/register` يتم حفظ البيانات مؤقتا في جدول `pending_registrations` وإرسال OTP.
- يتم إنشاء حساب المستخدم الحقيقي فقط بعد نجاح `/api/register/verify-otp`.
- رقم الهاتف يتم تطبيعه تلقائيا إلى صيغة:

```text
+9639xxxxxxxx
```

المقصود عمليا:

```text
+963991000000
```

ملاحظة: في JSON الحقيقي اكتب `+963991000000` بعلامة `+` واحدة فقط.

- مدة صلاحية OTP الحالية: `5 minutes`.
- يمكن إعادة الإرسال بعد: `60 seconds`.
- عدد محاولات التحقق الخاطئة قبل الحظر: `5 attempts`.
- إرسال OTP يتم عبر Queue باسم:

```text
otp
```

## صيغ أرقام الهاتف المقبولة

كل هذه الصيغ تتحول لنفس الرقم الداخلي:

```text
0991000000      => +963991000000
963991000000    => +963991000000
9630991000000   => +963991000000
00963991000000  => +963991000000
+963991000000   => +963991000000
```

الصيغة النهائية المطلوبة بعد التطبيع يجب أن تطابق:

```text
+9639 + 8 digits
```

مثال صحيح فعلي:

```text
+963991000000
```

## Endpoints

## 1. Start Register And Send OTP

```http
POST /api/register
```

### Body

```json
{
  "first_name": "Ahmad",
  "last_name": "Ali",
  "phone_number": "0991000000",
  "password": "Password123",
  "password_confirmation": "Password123",
  "government_id": 1
}
```

### Optional Fields

```json
{
  "role_id": 2
}
```

ملاحظة: `role_id` موجود كـ nullable في validation، لكن تسجيل العميل الطبيعي لا يحتاجه من الفرونت إذا ما كان متفق عليه.

### Success Response

Status:

```text
200 OK
```

Response:

```json
{
  "success": true,
  "message": "success send otp code",
  "data": {
    "phone_number": "+963991000000",
    "expires_in": 300,
    "resend_after": 60
  }
}
```

### ماذا يحدث داخليا؟

- يتم تطبيع رقم الهاتف.
- يتم التأكد أن الرقم غير موجود مسبقا في جدول `users`.
- يتم إنشاء أو تحديث سجل مؤقت في `pending_registrations`.
- يتم توليد OTP من 6 أرقام.
- يتم تخزين OTP بشكل مشفر `hash` وليس كنص صريح.
- يتم تصفير عدد المحاولات `attempts = 0`.
- يتم ضبط انتهاء الصلاحية بعد 5 دقائق.
- يتم إرسال Job إلى queue باسم `otp`.

### Register Validation Errors

#### first_name مفقود

```json
{
  "success": false,
  "message": {
    "first_name": [
      "The first name field is required."
    ]
  },
  "data": null
}
```

#### last_name مفقود

```json
{
  "success": false,
  "message": {
    "last_name": [
      "The last name field is required."
    ]
  },
  "data": null
}
```

#### phone_number مفقود

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number field is required."
    ]
  },
  "data": null
}
```

#### phone_number غير سوري أو غير صحيح

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number format is invalid."
    ]
  },
  "data": null
}
```

أمثلة غير صحيحة:

```text
091000000
0810000000
+963811000000
12345
abc
```

#### phone_number موجود مسبقا في users

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number has already been taken."
    ]
  },
  "data": null
}
```

#### password مفقود

```json
{
  "success": false,
  "message": {
    "password": [
      "The password field is required."
    ]
  },
  "data": null
}
```

#### password أقل من 9 أحرف

```json
{
  "success": false,
  "message": {
    "password": [
      "The password field must be at least 9 characters."
    ]
  },
  "data": null
}
```

#### password أكثر من 50 حرف

```json
{
  "success": false,
  "message": {
    "password": [
      "The password field must not be greater than 50 characters."
    ]
  },
  "data": null
}
```

#### password_confirmation غير مطابق

```json
{
  "success": false,
  "message": {
    "password": [
      "The password field confirmation does not match."
    ]
  },
  "data": null
}
```

#### password_confirmation مفقود

```json
{
  "success": false,
  "message": {
    "password_confirmation": [
      "The password confirmation field is required."
    ]
  },
  "data": null
}
```

#### government_id مفقود

```json
{
  "success": false,
  "message": {
    "government_id": [
      "The government id field is required."
    ]
  },
  "data": null
}
```

#### government_id غير موجود

```json
{
  "success": false,
  "message": {
    "government_id": [
      "The selected government id is invalid."
    ]
  },
  "data": null
}
```

#### role_id غير موجود في حال تم إرساله

```json
{
  "success": false,
  "message": {
    "role_id": [
      "The selected role id is invalid."
    ]
  },
  "data": null
}
```

### Register Rate Limit Errors

على مستوى رقم الهاتف:

```text
5 requests per minute
```

على مستوى IP:

```text
20 requests per minute
```

عند تجاوز الحد:

```json
{
  "message": "Too Many Attempts."
}
```

أو قد يرجع حسب Handler المشروع:

```json
{
  "success": false,
  "message": "Too Many Attempts.",
  "data": null
}
```

## 2. Resend Register OTP

```http
POST /api/register/resend-otp
```

### Body

```json
{
  "phone_number": "0991000000"
}
```

### Success Response

Status:

```text
200 OK
```

Response:

```json
{
  "success": true,
  "message": "success resend otp code",
  "data": {
    "phone_number": "+963991000000",
    "expires_in": 300,
    "resend_after": 60
  }
}
```

### ماذا يحدث داخليا؟

- يجب أن يوجد سجل مؤقت في `pending_registrations`.
- يتم توليد OTP جديد.
- يتم استبدال OTP القديم.
- يتم تصفير `attempts` إلى 0.
- يتم تمديد صلاحية الكود 5 دقائق جديدة.
- يتم تحديث `last_sent_at`.
- يتم إرسال Job جديد إلى queue باسم `otp`.

### Resend Scenarios

#### لا يوجد تسجيل مؤقت لهذا الرقم

يحدث إذا:

- المستخدم لم يطلب `/api/register` قبلها.
- أو تحقق من OTP بنجاح سابقا وتم حذف السجل المؤقت.
- أو الرقم مختلف عن الرقم المستخدم في التسجيل.

Status:

```text
404 Not Found
```

Response:

```json
{
  "success": false,
  "message": "No pending registration found for this phone number",
  "data": null
}
```

#### طلب إعادة الإرسال قبل مرور 60 ثانية

Status:

```text
429 Too Many Requests
```

Response:

```json
{
  "success": false,
  "message": "Please wait before requesting another OTP code",
  "data": {
    "retry_after": 45
  }
}
```

ملاحظة: `retry_after` رقم متغير حسب الوقت المتبقي.

#### phone_number مفقود

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number field is required."
    ]
  },
  "data": null
}
```

#### phone_number غير صحيح

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number format is invalid."
    ]
  },
  "data": null
}
```

### Resend Rate Limit Errors

على مستوى رقم الهاتف:

```text
3 requests per minute
```

على مستوى IP:

```text
15 requests per minute
```

عند تجاوز الحد:

```json
{
  "message": "Too Many Attempts."
}
```

أو حسب Handler المشروع:

```json
{
  "success": false,
  "message": "Too Many Attempts.",
  "data": null
}
```

## 3. Verify Register OTP

```http
POST /api/register/verify-otp
```

### Body

```json
{
  "phone_number": "0991000000",
  "code": "123456",
  "dive_name": "iphone"
}
```

### Optional Fields

```json
{
  "dive_name": "android"
}
```

ملاحظة: اسم الحقل حاليا في الكود هو `dive_name` وليس `device_name`.

### Success Response

Status:

```text
200 OK
```

Response:

```json
{
  "success": true,
  "message": "success registration user",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Ahmad",
      "last_name": "Ali",
      "phone_number": "+963991000000"
    },
    "token": "plain-text-token"
  }
}
```

ملاحظة: شكل `user` الكامل يرجع حسب `UserResource` الحالي في المشروع، لذلك قد يحتوي حقول إضافية.

### ماذا يحدث داخليا عند النجاح؟

- يتم البحث عن السجل المؤقت حسب رقم الهاتف.
- يتم التأكد أن OTP لم تنته صلاحيته.
- يتم التأكد أن عدد المحاولات أقل من 5.
- يتم مقارنة الكود مع `otp_hash`.
- يتم إنشاء `Client`.
- يتم إنشاء `User`.
- يتم تحميل علاقة `role`.
- يتم حذف السجل المؤقت من `pending_registrations`.
- يتم إنشاء token لا ينتهي للعميل.

### Verify Scenarios

#### لا يوجد تسجيل مؤقت لهذا الرقم

Status:

```text
404 Not Found
```

Response:

```json
{
  "success": false,
  "message": "No pending registration found for this phone number",
  "data": null
}
```

يحدث إذا:

- لم يتم استدعاء `/api/register`.
- أو تم التحقق بنجاح سابقا.
- أو تم استخدام رقم مختلف.

#### OTP منتهي الصلاحية

Status:

```text
422 Unprocessable Entity
```

Response:

```json
{
  "success": false,
  "message": "OTP code has expired",
  "data": null
}
```

الحل من الفرونت:

- استدعاء `/api/register/resend-otp`.
- ثم استخدام الكود الجديد.

#### OTP خاطئ

Status:

```text
422 Unprocessable Entity
```

Response:

```json
{
  "success": false,
  "message": "Invalid OTP code",
  "data": {
    "remaining_attempts": 4
  }
}
```

ملاحظة:

- كل كود خاطئ يزيد `attempts` بمقدار 1.
- `remaining_attempts` يتناقص من 4 إلى 0.

#### تجاوز عدد محاولات OTP

Status:

```text
429 Too Many Requests
```

Response:

```json
{
  "success": false,
  "message": "Maximum OTP attempts exceeded",
  "data": null
}
```

الحل من الفرونت:

- استدعاء `/api/register/resend-otp`.
- هذا يولد كود جديد ويعيد `attempts` إلى 0 إذا انتهت مدة cooldown.

#### code مفقود

```json
{
  "success": false,
  "message": {
    "code": [
      "The code field is required."
    ]
  },
  "data": null
}
```

#### code ليس 6 أرقام

```json
{
  "success": false,
  "message": {
    "code": [
      "The code field must be 6 digits."
    ]
  },
  "data": null
}
```

أمثلة غير صحيحة:

```text
12345
1234567
abcdef
12 3456
```

#### phone_number مفقود

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number field is required."
    ]
  },
  "data": null
}
```

#### phone_number غير صحيح

```json
{
  "success": false,
  "message": {
    "phone_number": [
      "The phone number format is invalid."
    ]
  },
  "data": null
}
```

#### dive_name أطول من 255

```json
{
  "success": false,
  "message": {
    "dive_name": [
      "The dive name field must not be greater than 255 characters."
    ]
  },
  "data": null
}
```

### Verify Rate Limit Errors

على مستوى رقم الهاتف:

```text
10 requests per minute
```

على مستوى IP:

```text
30 requests per minute
```

عند تجاوز الحد:

```json
{
  "message": "Too Many Attempts."
}
```

أو حسب Handler المشروع:

```json
{
  "success": false,
  "message": "Too Many Attempts.",
  "data": null
}
```

## Frontend Recommended Flow

## الحالة الطبيعية

1. المستخدم يملأ بيانات التسجيل.
2. الفرونت يرسل:

```http
POST /api/register
```

3. إذا نجح الطلب، افتح شاشة OTP.
4. خزّن مؤقتا:

```json
{
  "phone_number": "+963991000000",
  "expires_in": 300,
  "resend_after": 60
}
```

5. شغل عداد انتهاء OTP من `expires_in`.
6. شغل عداد زر إعادة الإرسال من `resend_after`.
7. المستخدم يدخل الكود.
8. الفرونت يرسل:

```http
POST /api/register/verify-otp
```

9. عند النجاح، خزّن `token` وسجل دخول المستخدم.

## عند انتهاء OTP

1. أظهر للمستخدم أن الكود انتهى.
2. فعّل زر إعادة الإرسال.
3. عند الضغط أرسل:

```http
POST /api/register/resend-otp
```

4. استخدم `expires_in` و `resend_after` الجدد.

## عند إدخال OTP خاطئ

1. اعرض رسالة الخطأ.
2. إذا رجع `remaining_attempts` اعرض عدد المحاولات المتبقية.
3. لا تمسح رقم الهاتف.
4. اسمح للمستخدم يعيد إدخال الكود حتى يصل إلى الحد الأقصى.

## عند تجاوز المحاولات

1. عطّل زر verify مؤقتا.
2. اطلب من المستخدم إعادة إرسال كود جديد.
3. استدعاء resend يعيد المحاولات إلى 0 بعد مرور cooldown.

## عند الضغط على resend بسرعة

1. إذا رجع `retry_after` استخدمه لتشغيل عداد الزر.
2. لا تسمح بإعادة الضغط قبل انتهاء العداد.

## Queue Notes

لأن إرسال OTP صار عبر queue:

- نجاح `/api/register` يعني أن الطلب تم قبوله وتم إنشاء Job للإرسال.
- قد يصل OTP بعد ثواني قليلة حسب تشغيل worker.
- إذا لم يصل OTP:
  - افحص أن queue باسم `otp` مفعلة في Plesk.
  - افحص جدول `jobs`.
  - افحص جدول `failed_jobs`.
  - افحص إعدادات Aman Gate في `.env`.

## Test Checklist

## Register

- تسجيل ببيانات صحيحة.
- تسجيل برقم يبدأ بـ `09`.
- تسجيل برقم يبدأ بـ `963`.
- تسجيل برقم يبدأ بـ `00963`.
- تسجيل برقم يبدأ بـ `+963`.
- تسجيل بدون `first_name`.
- تسجيل بدون `last_name`.
- تسجيل بدون `phone_number`.
- تسجيل برقم غير سوري.
- تسجيل برقم موجود مسبقا في `users`.
- تسجيل بدون `password`.
- تسجيل بكلمة مرور أقل من 9.
- تسجيل بكلمة مرور أكثر من 50.
- تسجيل مع `password_confirmation` غير مطابق.
- تسجيل بدون `government_id`.
- تسجيل بـ `government_id` غير موجود.
- إرسال أكثر من 5 requests بنفس الرقم خلال دقيقة.
- إرسال أكثر من 20 requests من نفس IP خلال دقيقة.

## Resend

- resend بعد register مباشرة قبل 60 ثانية.
- resend بعد مرور 60 ثانية.
- resend لرقم لا يملك pending registration.
- resend برقم بصيغة `09`.
- resend برقم بصيغة `+963`.
- resend بدون `phone_number`.
- resend برقم غير صحيح.
- إرسال أكثر من 3 requests بنفس الرقم خلال دقيقة.
- إرسال أكثر من 15 requests من نفس IP خلال دقيقة.

## Verify

- verify بكود صحيح قبل انتهاء 5 دقائق.
- verify بكود صحيح بعد resend.
- verify بكود قديم بعد resend.
- verify بكود خاطئ مرة واحدة.
- verify بكود خاطئ 5 مرات.
- verify بعد تجاوز المحاولات.
- verify بعد انتهاء 5 دقائق.
- verify لرقم لا يملك pending registration.
- verify بعد نجاح سابق بنفس الرقم.
- verify بدون `phone_number`.
- verify بدون `code`.
- verify بكود أقل من 6 أرقام.
- verify بكود أكثر من 6 أرقام.
- verify بكود يحتوي أحرف.
- verify مع `dive_name`.
- verify مع `dive_name` أطول من 255.
- إرسال أكثر من 10 requests بنفس الرقم خلال دقيقة.
- إرسال أكثر من 30 requests من نفس IP خلال دقيقة.

## Important Frontend Contract

- لا تنتظر token من `/api/register`.
- token يرجع فقط من `/api/register/verify-otp`.
- لا تستخدم الكود القديم بعد resend.
- اعتمد على `expires_in` و `resend_after` من response.
- لا تعرض للمستخدم تفاصيل تقنية عن queue.
- إذا رجع `retry_after` استخدمه كعداد لإعادة الإرسال.
- إذا نجح verify، اعتبر عملية التسجيل انتهت ولا تستدعي resend بعدها.
