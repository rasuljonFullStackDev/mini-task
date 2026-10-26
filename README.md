# Yetkazib berish shartnomasi tizimi

Laravel 13 | Blade | SQLite | PHP 8.3+

## Ishga tushirish (5 qadam)

```bash
# 1. Dependensiyalarni o'rnatish
composer install
npm install

# 2. .env fayl va key
cp .env.example .env
php artisan key:generate

# 3. Ma'lumotlar bazasi va demo ma'lumotlar
touch database/database.sqlite
php artisan migrate --seed

# 4. Frontend build
npm run build

# 5. Serverni ishga tushirish
php artisan serve
```

Brauzerda oching: http://localhost:8000

---

## Demo foydalanuvchilar

| Rol | Email | Parol |
|-----|-------|-------|
| Super Admin | admin@admin.com | admin123 |
| Ijrochi | ijrochi@demo.test | password |
| Boshliq | boshliq@demo.test | password |
| Buxgalter | buxgalter@demo.test | password |

---

## Demo ma'lumotlar (seeder)

- **SHN-2024-001** — Faol shartnoma (MediSupply, 1000 dona × 2.50 = 2500)
- **SHN-2024-002** — Qoralama shartnoma (PharmaTech)
- **SHN-2024-003** — Yakunlangan shartnoma (VitaPlus)
- **Smeta:** Dori vositalari / Mart 2024 = 3000, Aprel 2024 = 2000

---

## Testlarni ishga tushirish

```bash
php artisan test
```

48 test, 110 assertion.

---

## Asosiy fayllar

| Fayl | Vazifasi |
|------|---------|
| `app/Services/PenaltyCalculator.php` | Jarima hisoblash (bcmath, float yo'q) |
| `app/Services/PaymentService.php` | To'lov kiritish + 5 ta tekshiruv |
| `app/Policies/ContractPolicy.php` | Ijrochi faqat o'z shartnomasi |
| `ASSUMPTIONS.md` | Noaniq joylar bo'yicha qarorlar |
