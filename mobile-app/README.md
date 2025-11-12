# Zafarts Mobile

Expo asosidagi React Native ilovasi Zafarts (https://zafarts.uz) amaliy ishlar tizimi uchun mobil interfeys taqdim etadi. Ilova talaba va administrator rollarini qo'llab-quvvatlaydi, fayl yuklashni Google Apps Script orqali bajaradi va adminlar uchun baholash hamda statistikani taqdim etadi.

## Asosiy imkoniyatlar

- Demo login: admin/admin123 yoki S001-S005 / pass1-pass5.
- Talabalar uchun 24 ta amaliy ish ro'yxati, har biri uchun holat va fayl yuborish tugmasi.
- Fayl turi (PPT/PPTX/DOC/DOCX/ZIP/PY/PDF) va 10 MB hajm limitini tekshirish.
- Google Drive'ga yuklash uchun Apps Script REST endpointiga base64 ko'rinishida fayl yuborish.
- Administratorlar uchun baholash, izoh berish va yangi talabalar qo'shish moduli.
- Statistikada talabalar soni va umumiy progress ko'rsatkichlari.

## Ishga tushirish

1. [Node.js](https://nodejs.org/) (>=18) o'rnating.
2. Expo CLI ni global o'rnating yoki `npx` orqali ishlating.

```bash
cd mobile-app
npm install
npm run start
```

`npm run start` buyruqini ishga tushirganingizdan so'ng Expo QR kodi paydo bo'ladi. Uni Expo Go (iOS/Android) orqali skaner qilib, ilovani qurilmada sinab ko'rishingiz mumkin.

## Google Apps Script haqida eslatma

- `APPS_SCRIPT_URL` qiymatini mavjud skript URL manzili bilan mos holda saqlang.
- Skript JSON javobida `status`, `webViewLink` va `fileId` maydonlarini qaytarishi kerak.
- Agar cheklovlar talab qilsa, autentifikatsiya va xavfsizlik qatlamlarini qo'shishingiz mumkin.

## Kelajakdagi takomillashtirishlar

- Real API bilan autentifikatsiya va ma'lumotlarni sinxronlashtirish.
- Offline rejimi va fayllarni keyinroq yuborish imkoniyati.
- Push xabarnomalar va real vaqt rejimida baholash natijalarini yangilash.
