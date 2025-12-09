IMPLEMENTATION PLAN FOR HIERARCHICAL HEALTH QUESTIONS
======================================================

WHAT YOU ASKED FOR (Summary):
-----------------------------

1. ✅ Step-by-step questions (main question with ja/nee, then secondary based on answer)
2. ✅ Multi-level questions (main → secondary, with optional pre-secondary like drug types)
3. ✅ Number-only input fields (no characters, only numbers and backspace)
4. ✅ Smart scoring based on keywords (water 1.5x, food 1.75x)
5. ✅ Health scoring based on: answers + height + weight + age + gender
6. ✅ Separate water from food (both in Voeding but different multipliers via keywords)
7. ✅ Excess penalties (too much water = unhealthy, too much food = unhealthy)
8. ✅ Admin can add main questions and link secondary questions
9. ✅ Admin cannot modify answers (system is automatic)
10. ✅ Admin can add optional pre-secondary questions (drug type example)

NEXT STEPS NEEDED:
------------------

Phase 1: DATABASE (Foundation)
  → Apply the SQL in alter_questions_hierarchical.sql to your database
  → Add height/weight to existing user accounts
  → Populate category_keywords table with initial keywords

Phase 2: ADMIN INTERFACE (Question Management)
  → Modify admin/pages/vragen.php to:
    - Select if main or secondary question
    - If main: can add secondary questions inline
    - If secondary: specify parent and show_on_answer value
    - If adding drugs-type questions: allow multiple branches
  → Add scoring rule interface (optional but recommended)

Phase 3: USER INTERFACE (Questionnaire Flow)
  → Modify pages/vragen.php to:
    - Display main question only initially
    - After answer, load secondary questions conditionally
    - Number fields: implement character filtering (numbers + backspace only)
    - Show all questions in flow before submission

Phase 4: SCORING ENGINE (Health Calculation)
  → Create HealthScoreCalculator class that:
    - Gets all user answers for the day
    - Applies question_scoring_rules based on answers
    - Calculates BMI from height/weight/age/gender
    - Applies multipliers for keywords (water, food, drugs)
    - Applies excess penalties
    - Returns overall score 0-100 and breakdown by pillar
  → Store results in user_health_scores table

Phase 5: RESULTS PAGE
  → Show user their health score
  → Breakdown by pillar
  → Show calculation transparency
  → Suggest improvements

DATABASE READINESS:
-------------------

✅ Tables created with proper relationships
✅ Foreign keys set up
✅ Unique constraints for data integrity
✅ JSON fields for flexible data storage
✅ Timestamps for tracking

Files created:
- alter_questions_hierarchical.sql (SQL migrations)
- DATABASE_STRUCTURE.md (This documentation)

DO YOU WANT TO:

A) Proceed with applying the SQL to your database? (Phase 1)
B) Wait and let me create the admin interface first? (Phase 2)
C) Have me implement everything step-by-step?
D) Modify the database structure before applying?

Let me know which phase to focus on and I'll implement it!
