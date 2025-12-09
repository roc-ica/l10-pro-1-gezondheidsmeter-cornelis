DATABASE STRUCTURE FOR HIERARCHICAL HEALTH QUESTIONNAIRE
=========================================================

OVERVIEW:
The new system allows multi-level questions with dynamic scoring based on user profile data.

TABLE MODIFICATIONS:
====================

1. USERS TABLE
   - Added: height_cm (INT) - Height in centimeters
   - Added: weight_kg (DECIMAL) - Weight in kilograms  
   - Existing: birthdate, gender - Used for age calculation
   Purpose: Calculate health scores based on BMI, age, and gender

2. QUESTIONS TABLE (MODIFIED)
   - Added: parent_question_id - Links secondary/tertiary questions to parent
   - Added: question_type (main/secondary/tertiary) - Position in hierarchy
   - Added: answer_type (binary/number/choice/text) - Expected input format
   - Added: show_on_answer - Conditional display (e.g., show if parent answer = "ja")
   Purpose: Support multi-level questions with branching logic

3. ANSWERS TABLE (MODIFIED)
   - Added: question_parent_id - Track if this answer is to a secondary question
   - Added: answer_sequence - Order of answer in conversation flow
   Purpose: Track the flow of multi-part questions

NEW TABLES:
===========

4. QUESTION_SCORING_RULES
   - Stores flexible scoring rules for each question
   - Supports: equals, greater_than, less_than, contains_keyword, range conditions
   - Fields: base_score, multiplier, penalty, max_daily_value, excess_penalty_per_unit
   Example: Water question - multiplier 1.5, max_daily_value 8, excess_penalty 0.5 per glass

5. USER_HEALTH_SCORES
   - Stores daily calculated health scores (0-100)
   - Breakdown by pillar (JSON)
   - Calculation details for transparency (JSON)
   Purpose: Track user's health over time

6. CATEGORY_KEYWORDS
   - Maps keywords to subcategories and multipliers
   Examples:
     - Pillar: Voeding (1)
       - "water" → subcategory: "water", multiplier: 1.5x
       - "fruit" → subcategory: "food", multiplier: 1.75x
     - Pillar: Verslavingen (4)
       - "zacht" → subcategory: "soft_drugs", multiplier: varies
       - "hard" → subcategory: "hard_drugs", multiplier: varies
   Purpose: Automatically apply multipliers based on question content

QUESTION HIERARCHY FLOW:
========================

Example 1: Sleep Question (Food/Drink)
------
Main Question:
  Q1: "Heb je goed kunnen slapen?" → answer_type: binary (ja/nee)
  
Secondary Questions:
  Q2: Parent=Q1, show_on_answer="ja"
       "Hoeveel uur heb je geslapen?" → answer_type: number
       Scoring: Input ≥ 7 hours = good, < 5 = bad, > 10 = bad
       
  Q3: Parent=Q1, show_on_answer="nee"
       "Wat was de reden?" → answer_type: choice
       Options: "Stress", "Pijn", "Ander"

Example 2: Drug Question with Pre-Secondary
------
Main Question:
  Q4: "Heb je alcohol of drugs gebruikt?" → answer_type: binary
  
Pre-Secondary (branching):
  Q5: Parent=Q4, show_on_answer="ja"
       "Wat voor soort drugs?" → answer_type: choice
       Options: "Soft drugs", "Hard drugs"
       
Secondary based on Q5 answer:
  Q6: Parent=Q5, show_on_answer="Soft drugs"
       "Hoeveel keer vandaag?" → answer_type: choice
       Options: "1 keer", "2-3 keer", "Meer dan 3 keer"
       Scoring: Each increases, harder drugs = bigger penalty
       
  Q7: Parent=Q5, show_on_answer="Hard drugs"
       "Hoeveel gram?" → answer_type: number
       Scoring: Direct penalty based on amount

Example 3: Food & Drink (Split by Keywords)
------
Main Question:
  Q8: "Hoeveel glazen water heb je vandaag gedronken?" → answer_type: number
       Keywords: "water" → multiplier 1.5x, max_daily 8 glasses
       Scoring: 0-8 glasses = 1.5x points per glass
                > 8 glasses = penalties apply
       
  Q9: "Hoeveel porties fruit/groente?" → answer_type: number
       Keywords: "fruit|groente" → multiplier 1.75x, max_daily 5
       Scoring: 0-5 portions = good, > 5 = excess penalty

SCORING CALCULATION:
====================

Formula: Health Score = Base Score + (Answers × Multipliers) - Penalties ± User Adjustments

Components:
1. Base Score = 50 (starting point)
2. Per-Pillar Scoring:
   - Sum answers in each pillar × appropriate multipliers
   - Apply category keywords multipliers (water 1.5x, food 1.75x)
   - Apply excess penalties if max_daily exceeded
3. User Profile Adjustment:
   - Age: Young users (< 25) slight boost, elderly (> 65) adjusted
   - BMI: Calculated from height/weight
     - Healthy BMI 18.5-24.9 = no adjustment
     - Overweight 25-29.9 = slight penalty on food scores
     - Obese 30+ = increased penalty
   - Gender: Some adjustments (e.g., water needs vary)
4. Final Score: Normalize to 0-100 scale

ADMIN INTERFACE FEATURES:
========================

When adding questions, admin:
1. Selects category (Voeding, Beweging, etc.)
2. Chooses if it's a main or secondary question
3. If main: Can add multiple secondary questions that appear conditionally
4. If secondary: Specify show_on_answer value
5. Sets answer_type (binary, number, choice, text)
6. If choice: Admin enters the options
7. Cannot modify answers/scoring - system is automatic
8. Can view scoring rules preview before saving
9. For add-on questions (like drug type branching):
   - Add primary branching question
   - Assign secondary questions to each branch
   - Example: Drug type question can have 2+ follow-ups

USER INTERFACE FEATURES:
=======================

Sequential flow:
1. Show main question (ja/nee buttons or number input)
2. Based on answer, load applicable secondary questions
3. Number inputs: Only numbers and backspace allowed
4. No word input for numeric fields
5. Show progress based on conditional questions
6. At end: Show health score breakdown by pillar
7. Show calculation details (transparency)

KEYWORD MAPPING EXAMPLES:
=========================

Insert into category_keywords:
- (1, "water", "water", 1.50, 8, "glasses")
- (1, "fruit", "food", 1.75, 5, "portions")
- (1, "groente", "food", 1.75, 5, "portions")
- (4, "zacht", "soft_drugs", 0.5, NULL, NULL)
- (4, "hard", "hard_drugs", 0.1, NULL, NULL)

This allows flexible scoring without touching question table.
