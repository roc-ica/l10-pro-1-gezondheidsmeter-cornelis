# 🔐 Test Gebruikers - Login Gegevens

## ✅ FIXED UPDATE

Alle wachtwoorden zijn gereset en geüniformeerd.

### 🔑 UNIVERSEEL WACHTWOORD
**Wachtwoord:** `Wortelboot12`

---

## 📋 Gebruikerslijst

Hieronder de gebruikers waarmee je kunt inloggen.

| Username | Email | Display Name | Wachtwoord |
|----------|-------|--------------|------------|
| **colin** | colinpoort@hotmail.com | (Admin) | `Wortelboot12` |
| **emma** | emma@test.nl | Emma de Vries | `Wortelboot12` |
| **lucas** | lucas@test.nl | Lucas Jansen | `Wortelboot12` |
| **sophie** | sophie@test.nl | Sophie Berg | `Wortelboot12` |
| **thomas** | thomas@test.nl | Thomas Bakker | `Wortelboot12` |
| **anita_gezond** | anita@example.com | Anita Gezond | `Wortelboot12` |
| **lisa_stress** | lisa@example.com | Lisa Stressvrij | `Wortelboot12` |
| **bram_fit** | bram@example.com | Bram Fit | `Wortelboot12` |
| **sophie_balans** | sophie@example.com | Sophie Balans | `Wortelboot12` |

---

## 👤 Gebruikersprofielen

### 1. **Emma de Vries** (emma)
- **Status:** Actieve gebruiker
- **Profiel:** Gezond, consistente scores

### 2. **Lucas Jansen** (lucas)
- **Status:** Matige gebruiker
- **Profiel:** Lagere scores, soms alcohol/drugs

### 3. **Sophie Berg** (sophie)
- **Status:** Zeer actieve gebruiker
- **Profiel:** Hoge scores

### 4. **Thomas Bakker** (thomas)
- **Status:** Nieuwe gebruiker

---

## 🛠️ Technische Info

Het probleem waarbij `emma` een plain-text wachtwoord had is opgelost.
Alle gebruikershashes zijn nu correct ingesteld op Bcrypt.

Als je **'Test123!'** probeerde: dat was het oude wachtwoord uit de generatiescripts, maar de database en docker container stonden op `Wortelboot12` (of waren corrupt). Nu is alles **Wortelboot12**.
