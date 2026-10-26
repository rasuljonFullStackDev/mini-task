# ASSUMPTIONS.md

Bu faylda topshirig'ida aniq aytilmagan joylar uchun qabul qilingan qarorlar yozilgan.

---

## 1. Bekor qilingan to'lovning smeta oyi allaqachon o'tgan bo'lsa

**Holat:** Buxgalter mart smetasidan to'lov kiritdi, keyin bekor qildi. Lekin bekor qilish vaqtida mart oy o'tib ketgan.

**Qaror:** To'lov bekor qilinganda smeta qoldig'i qayta hisoblanadi (ya'ni, bekor qilingan summa avtomatik qaytadi). Smeta oyining o'tgan-o'tmaganligini tekshirmaymiz — faqat yangi to'lov kiritishda joriy to'lov sanasi moliyaviy yilga mos bo'lishi tekshiriladi. Bu auditorlik izi (reestr o'zgarmasligi) tamoyiliga mos.

---

## 2. Jarima to'lovlar kiritilgandan keyin tasdiqlanganida

**Holat:** Buxgalter "yetkazib berish" bosqichini to'ladi. Keyin boshliq jarima tasdiqladi.

**Qaror:** Jarima alohida ko'rsatkich sifatida buxgalteriya sahifasida ko'rsatiladi. Jarima tasdiqlanganda to'lov bosqichlari summasi o'zgarmaydi — ya'ni, jarima summasi "yetkazib berish" bosqichidan ayirilmaydi. Bu tizimda jarima axborot maqsadida ko'rsatiladi (qanday ta'sir qilishini: yangi bosqich qo'shish, joriy bosqich summasini oshirish yoki alohida hisob-faktura yozish — tashkilot hisobchisi hal qiladi). Topshiriq bu ta'sirni "o'zingiz hal qiling" deb qoldirgani uchun ushbu yondashuvni tanladik.

---

## 3. Hisoblash sanasi muddatdan oldin bo'lsa

**Holat:** Foydalanuvchi "hisoblash sanasi" sifatida muddat sanasidan oldingi sanani kiritdi.

**Qaror:** Jarima 0.00 qaytariladi. Jarima faqat muddatdan keyingi kunlar uchun hisoblanadi. Hisoblash sanasi muddat sanasiga teng bo'lsa ham 0.00 — chunki topshiriq "muddatdan keyingi" (strictly after) deydi.

---

## 4. Jarimaning "yakuniy to'lovga ta'siri"

**Qaror (yuqorida batafsil):** Jarima to'lov bosqichlari bilan birlashtirilmagan, alohida ko'rsatich. Amaliy integratsiya (yangi bosqich, summa oshirish) tashkilot ichki qoidasiga qarab qo'lda amalga oshiriladi. Tizim faqat tasdiqlangan jarimani ko'rsatadi.

---

## 5. Bir shartnomada bir nechta jarima hisoblansa

**Qaror:** Har safar "Boshliqqa yuborish" bosilganda, mavjud `pending` holatdagi jarima o'chiriladi va yangi hisoblanadi (bir shartnomada bir vaqtda faqat bitta pending jarima bo'ladi). `approved` yoki `rejected` jarimalarga tegmaymiz. Shunday qilib bir shartnomada tarix saqlanadi.

---

## 6. Limit hisoblash — "muddat kunidagi kelmagan summa"

**Topshiriqdan:** "Jami jarima muddat kunining oxiridagi kelmagan pullik summaning 50% idan oshmaydi."

**Qaror:** "Muddat kunida" degani muddat sanasi kechqurutigacha (end-of-day) yetkazilgan tovarlar inobatga olinadi. Yani muddat sanasiga (`deadline`) teng bo'lgan yetkazish batches hisobga olinadi, muddat sanasidan keyin kelgan batches esa hisobga olinmaydi. Bu `computeOutstanding($totalPaid, $deliveries, $deadline)` da amalga oshirilgan.

---

## 7. Float ishlatmaslik

Barcha pul operatsiyalari `bcmath` funksiyalari (`bcadd`, `bcsub`, `bcmul`, `bcdiv`) orqali amalga oshiriladi. Ma'lumotlar bazasida `decimal(12, 2)` formatida saqlanadi.

---

## 8. Smeta qoldig'i manfiy bo'lishi

**Qaror:** Qoldiq manfiy bo'lmasligi uchun to'lov kiritishda tekshiriladi. Lekin bekor qilish operatsiyasida bu tekshiruv o'tkazilmaydi (chunki bekor qilish qayta hisobga oladi, qoldig'i oshadi).

---

## 9. Bir vaqtda ishlash (concurrency)

`DB::transaction` va `lockForUpdate()` yordamida pessimistic locking amalga oshirildi. Agar ikki buxgalter bir vaqtda bir smeta oyining oxirgi qoldig'ini ishlatmoqchi bo'lsa, faqat biri o'tadi, ikkinchisi "Smeta qoldig'i yetarli emas" xatosini oladi.

---

## 10. Nima qilinmadi (halollik)

- Bosqich holatini `ready` (To'lashga tayyor) holati `computedStatus()` da `scheduled` sifatida ham ko'rsatilishi mumkin — bu alohida holat sifatida ajratilib, UI da to'liq ko'rsatilmagan.
- E-mail bildirishnomalar (jarima tasdiqlanganda va h.k.) qo'shilmadi.
- Foydalanuvchi interfeysi minimal — dizayn chiroyli emas, faqat funksional.
