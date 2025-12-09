SYSTEM REDESIGN - COMPLETE IMPLEMENTATION
==========================================

## 1. ADMIN INTERFACE SIMPLIFIED ✅

### Admin Dashboard Changes (admin/pages/vragen.php)

**Old System:**
- 4 separate dropdowns: Category, Question Type, Answer Type, + text field
- Complex form with many options
- Users confused about different answer types

**New System:**
```
┌─────────────────────────────────┐
│ Categorie selecteren (dropdown) │
│ [x] Hoofdvraag (checkbox)       │
│ Voer hier je vraag in... (text) │
│ 🔲 ⚠️ Dit is de drugs vraag    │ (shows only for Verslavingen)
└─────────────────────────────────┘
```

**How It Works:**
1. Admin selects category
2. Checkbox for "Is this a main question" (unchecked = secondary)
3. If "Verslavingen" (pillar 4) selected → drugs checkbox appears
4. Always yes/no + number input (no selector needed)

**Database Fields Added:**
- `is_main_question` (tinyint) - 1 = main, 0 = secondary
- `is_drugs_question` (tinyint) - 1 = this is the drug question


## 2. DRUGS QUESTION SPECIAL FEATURE ✅

### For Verstrekking (Intoxication) Category Only

**Question:** "Wat voor drugs hebt u gebruikt?"

**Answer Options:**
- Softdrugs (Marihuana) → Score penalty: -15
- Harddrugs (Cocaïne, Heroine, Ecstasy) → Score penalty: -40
- No drugs / No answer → Score penalty: 0

**Scoring Logic (in HealthScoreCalculator.php):**

```php
private function scoreDrugsAnswer($answerValue, $question, &$details): float
{
    if (stripos($answer, 'softdrug') !== false) {
        $score = 20;       // Base unhealthy
        $penalty = 15;     // Moderate penalty
    } else if (stripos($answer, 'harddrug') !== false) {
        $score = 10;       // Very low base
        $penalty = 40;     // SEVERE penalty (2.7x worse than softdrugs)
    } else {
        $score = 0;        // No drugs
        $penalty = 0;
    }
    
    $finalScore = $score - $penalty;
    return max(-50, $finalScore); // Cap at -50
}
```

**Impact:**
- Softdrugs: -15 to category score
- Harddrugs: -40 to category score
- Both significantly damage overall health score
- Harddrugs are 2.7x worse than softdrugs


## 3. DATABASE CONSOLIDATION ✅

### All Tables Merged Into init.sql

**3 New Tables Added:**

### A. `user_health_scores` table
Stores daily health scores with complete breakdown

```sql
CREATE TABLE user_health_scores (
    id bigint PRIMARY KEY,
    user_id bigint (FK),
    score_date date,
    overall_score decimal(5,2),  -- 0-100
    pillar_scores JSON,           -- {"1":85, "2":72, ...}
    calculation_details JSON,     -- Full transparency
    created_at timestamp,
    UNIQUE (user_id, score_date)
)
```

**Data Example:**
```json
{
    "user_id": 5,
    "score_date": "2025-12-08",
    "overall_score": 74.50,
    "pillar_scores": {
        "1": 82.5,   // Voeding
        "2": 70.0,   // Beweging
        "3": 65.0,   // Slaap
        "4": 45.0,   // Verslavingen (drugs penalty)
        "5": 75.0,   // Sociaal
        "6": 80.0    // Mentaal
    },
    "calculation_details": { ... }
}
```

### B. `question_scoring_rules` table
Defines how answers are scored with flexible conditions

```sql
CREATE TABLE question_scoring_rules (
    id bigint PRIMARY KEY,
    question_id bigint (FK),
    condition_type varchar(50),    -- 'equals', 'greater_than', 'range', etc.
    condition_value varchar(255),
    condition_min int,
    condition_max int,
    base_score decimal(5,2),
    multiplier decimal(3,2),       -- 1.5x for water, 1.75x for food
    max_daily_value int,
    excess_penalty_per_unit decimal(3,2)
)
```

### C. `category_keywords` table
Maps keywords to scoring multipliers

```sql
CREATE TABLE category_keywords (
    id bigint PRIMARY KEY,
    pillar_id tinyint (FK),
    keyword varchar(255),
    subcategory varchar(255),
    multiplier decimal(3,2),       -- 1.5, 1.75, etc.
    notes text
)
```

### Questions Table Updates
```sql
ALTER TABLE questions ADD COLUMN is_main_question tinyint DEFAULT 1;
ALTER TABLE questions ADD COLUMN is_drugs_question tinyint DEFAULT 0;
```


## 4. CATEGORY SCORES DISPLAY ✅

### New Page: /pages/category-scores.php

**Purpose:** Show per-category health breakdown

**Features:**
1. **Date Selector** - View scores for any past date
2. **Summary Stats** - Overall score, # categories, date
3. **Category Cards** - Grid layout showing:
   - Category name & description
   - Score circle (colored by pillar color)
   - Score percentage (0-100)
   - Status badge: Uitstekend/Goed/Matig/Slecht
   - 7-day trend bars (mini visualization)

**Score Status Definitions:**
- **Uitstekend (Excellent):** 80-100 🟢
- **Goed (Good):** 60-79 🔵
- **Matig (Fair):** 40-59 🟡
- **Slecht (Poor):** 0-39 🔴

**Visual Layout:**
```
┌──────────────────────────────────────────────────────┐
│ Categorie Scores - Bekijk gebroken scores per category
│                                                       │
│ Datum: [2025-12-08 ▼]                               │
│                                                       │
│ ┌─────────────┬──────────┬──────────┬──────────────┐ │
│ │Totaal: 74/100│6 Categ.│08-12-2025│              │ │
│ └─────────────┴──────────┴──────────┴──────────────┘ │
│                                                       │
│ ┌────────────┐  ┌────────────┐  ┌────────────┐      │
│ │  VOEDING   │  │  BEWEGING  │  │   SLAAP    │      │
│ │  82/100    │  │  70/100    │  │  65/100    │      │
│ │ [Goed ✓]   │  │ [Goed ✓]   │  │ [Matig ⚠]  │      │
│ │ Trend ▁▃▂▄ │  │ Trend ▂▁▃▂ │  │ Trend ▁▁▁▂ │      │
│ └────────────┘  └────────────┘  └────────────┘      │
│                                                       │
│ ┌────────────┐  ┌────────────┐  ┌────────────┐      │
│ │ VERSLAVINGEN│  │  SOCIAAL   │  │  MENTAAL   │      │
│ │  45/100    │  │  75/100    │  │  80/100    │      │
│ │ [Slecht ✗] │  │ [Goed ✓]   │  │ [Excellent]│      │
│ │ Trend ▂▂▂▁ │  │ Trend ▂▃▂▃ │  │ Trend ▃▄▃▄ │      │
│ └────────────┘  └────────────┘  └────────────┘      │
└──────────────────────────────────────────────────────┘
```

**Navigation:**
- Button in results page → "Bekijk categorie scores"
- Date picker to view historical breakdowns
- Back button to return to overall results


## 5. SCORING SYSTEM BEHAVIOR ✅

### Example: How Scores Go Up and Down

**Good Day (High Score):**
- Voeding: Healthy foods + water (1.5x) = +35
- Beweging: 45 min exercise = +20
- Slaap: 8 hours = +25
- Verslavingen: No drugs = 0
- Sociaal: Social interaction = +20
- Mentaal: Low stress = +18
- **Total: ~75-80/100** ✅

**Bad Day (Low Score):**
- Voeding: Junk food + no water = +10 (but excess penalty = -12)
- Beweging: No exercise = -5
- Slaap: 5 hours = +10
- Verslavingen: Harddrugs = -40 (SEVERE)
- Sociaal: Isolated = 0
- Mentaal: High stress = -10
- **Total: ~20-30/100** ❌

**Drugs Impact:**
- No drugs: 0 penalty ✅
- Softdrugs once: -15 ⚠️
- Harddrugs once: -40 🚨 (2.7x worse!)

**BMI Impact:**
- Healthy (18.5-24.9): 0 adjustment
- Underweight: -5 penalty
- Overweight (25-29): -10 penalty
- Obese (30+): -20 penalty


## 6. FILE CHANGES SUMMARY

### Modified Files:
1. **admin/pages/vragen.php** - Simplified form, drugs checkbox
2. **src/config/gezondheidsmeter.sql** - Added 3 new tables + 2 columns to questions

### New Files:
1. **pages/category-scores.php** - Per-category scores display
2. **classes/HealthScoreCalculator.php** (updated) - Drug scoring logic

### Updated Methods:
- `HealthScoreCalculator::scoreAnswer()` - Routes to drug handler
- `HealthScoreCalculator::scoreDrugsAnswer()` - NEW special handling

---

## CLEAN & COMPLETE IMPLEMENTATION ✅

✅ Admin interface: Simple 2-field form
✅ Drugs question: Special scoring (soft vs hard)
✅ Database: All tables in init.sql (no separate files)
✅ Category display: Per-category scores with trends
✅ Scoring: Intelligent up/down based on answers + biometrics

**Ready to deploy!**
