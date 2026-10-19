# EduPath Content Architecture: Canonical Target

## 1. Hierarchy Level
Sistem Taksonomi akan direlasikan secara vertikal dengan tingkatan berikut (Single Source of Truth lintas Program):

1. **Program** (Contoh: SNBT, TKA, KEDINASAN)
2. **Assessment** (Contoh: TPS, Tes Literasi)
3. **Subtest / Subject** (Contoh: Penalaran Umum, Pengetahuan Kuantitatif)
4. **Domain** (Contoh: Penalaran Induktif, Aljabar)
5. **Topic** (Contoh: Silogisme, Persamaan Linear)
6. **Subtopic** (Contoh: Silogisme Kategorial, SPLDV)
7. **Competency** (Contoh: Kemampuan menarik kesimpulan deduktif)
8. **Indicator** (Contoh: Mampu menyelesaikan silogisme 2 premis dengan tepat)

Setiap node di atas dapat diwakili oleh ID Unik di database.

## 2. Relasi Entitas Konten
Entitas Material dan Question harus dipetakan langsung ke tingkatan terkecil yang memungkinkan (ideal: Indicator atau Competency).

```text
Indicator (atau Competency)
 │
 ├── Material (Materi Pembelajaran)
 │   └── Terdiri dari: Text, Worked Example, Practice, Summary.
 │
 └── Question (Soal Tryout/Latihan)
     └── Terdiri dari: Soal, Opsi, Jawaban, Difficulty, Cognitive Demand.
```

## 3. Prioritas SNBT (Pilot)
Hierarki khusus untuk SNBT:

**Program**: SNBT
- **Assessment**: TPS
  - **Subtest**: Penalaran Umum
  - **Subtest**: Pengetahuan dan Pemahaman Umum
  - **Subtest**: Pemahaman Bacaan dan Menulis
  - **Subtest**: Pengetahuan Kuantitatif
- **Assessment**: Tes Literasi
  - **Subtest**: Literasi Bahasa Indonesia
  - **Subtest**: Literasi Bahasa Inggris
  - **Subtest**: Penalaran Matematika

*(Domain ke bawah akan dipecah per masing-masing Subtest).*

## 4. SPP (Student Potential Path)
Sesuai arahan, SPP dipisahkan dari taksonomi SNBT di atas.
Dimensi SPP:
- Potential
- Interest
- Learning
- Growth
*(Tanpa label diagnostik/psikologis, melainkan profil belajar).*
