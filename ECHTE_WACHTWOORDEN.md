# 🔐 ECHTE Wachtwoorden - Huidige Database Status

## ⚠️ PROBLEEM GEVONDEN

Sommige gebruikers hebben **plain text** wachtwoorden in plaats van gehashte wachtwoorden!

## 📋 Actuele Wachtwoorden per Gebruiker

Gebaseerd op de huidige database status:

### ✅ Werkende Logins:

| Username | Wachtwoord | Status |
|----------|------------|--------|
| **colin** (admin) | `Wortelboot12` | ✅ Werkt (gehashed) |
| **lucas** | `Test123!` | ✅ Werkt (gehashed) |
| **sophie** | `Test123!` | ✅ Werkt (gehashed) |
| **thomas** | `Test123!` | ✅ Werkt (gehashed) |
| **anita_gezond** | Onbekend (andere hash) | ⚠️ Hash aanwezig |
| **lisa_stress** | Onbekend (andere hash) | ⚠️ Hash aanwezig |
| **bram_fit** | Onbekend (andere hash) | ⚠️ Hash aanwezig |

### ❌ KAPOTTE Logins:

| Username | Database Waarde | Probleem |
|----------|----------------|----------|
| **emma** | `Wortelboot12` | ❌ PLAIN TEXT! Moet gehashed worden |
| **sophie_balans** | Gedeeltelijk corrupt | ❌ Bevat "Wortelboot12" in hash |

## 🔧 Oplossing

Ik maak een SQL script om de wachtwoorden te fixen naar:
- Alle gebruikers: wachtwoord `Wortelboot12`
- Goed gehashed met bcrypt

## 📝 Samenvatting

**Werkend nu:**
- colin: `Wortelboot12`
- lucas: `Test123!`
- sophie: `Test123!`
- thomas: `Test123!`

**NIET werkend (moet gefixed worden):**
- emma: database heeft plain text
- sophie_balans: corrupt

**Onbekend wachtwoord:**
- anita_gezond
- lisa_stress
- bram_fit

(Deze hebben waarschijnlijk ook `Wortelboot12` nodig)
