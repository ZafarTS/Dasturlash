# Dasturlash amaliy ishlar tizimi

Talabalar uchun fayl yuborish va administratorlar uchun boshqaruv paneliga ega bo'lgan PHP/MySQL asosidagi veb-ilova.

## Xususiyatlar

- **Talaba paneli**
  - Profil ma'lumotlarini yangilash, parolni almashtirish va avatar yuklash
  - 10 MB gacha bo'lgan PPT/PPTX, DOC/DOCX, ZIP, PY va PDF fayllarini topshirish
  - Guruhiga biriktirilgan fanlar bo'yicha mavjud amaliy ishlarni tanlash
  - Topshiriqlar tarixi va baholarni ko'rish

- **Admin paneli**
  - Talabalarni qo'shish, guruh va fanlarni yaratish
  - Fanlarni guruhlarga biriktirish (biriktirilganda barcha talabalarga avtomatik ochiladi)
  - 24 tagacha amaliy ishni avtomatik yaratish
  - Topshiriqlarni guruh, fan va amaliy ish bo'yicha filtrlash, fayllarni yuklab olish va baholash
  - Statistika paneli: guruhlar bo'yicha bajarilish ko'rsatkichlari

## O'rnatish

1. **Kerakli PHP kengaytmalari**
   - `pdo_mysql`
   - `fileinfo`

2. **Ma'lumotlar bazasi**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   Admin uchun standart login/parol: `admin` / `admin123`.

3. **Konfiguratsiya**
   `.env` mavjud bo'lmasa, `config/config.php` faylidagi standart parametrlar ishlatiladi. Muayyan sozlamalar uchun quyidagi muhit o'zgaruvchilarini o'rnating:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASSWORD`

4. **Serverni ishga tushirish**
   ```bash
   php -S localhost:8000 -t public
   ```
   So'ngra brauzerda `http://localhost:8000` manziliga o'ting.

## Fayl tuzilmasi

```
config/              # Konfiguratsiya
lib/                 # PDO va yordamchi funksiyalar
public/              # Veb-ilova sahifalari (login, admin, talaba)
uploads/             # Yuklangan fayllar (gitda saqlanmaydi)
database/schema.sql  # Ma'lumotlar bazasi sxemasi
```

## Eslatmalar

- Fayl hajmi limiti 10 MB, ruxsat etilgan kengaytmalar: `ppt`, `pptx`, `doc`, `docx`, `zip`, `py`, `pdf`.
- Amaliy ishlar soni har bir fan uchun 24 tagacha avtomatik yaratiladi, zaruratga qarab kamaytirish mumkin.
- Ro'yxatdan o'tish faqat admin orqali, talabalar login sifatida o'z ID raqamlaridan foydalanadilar.
