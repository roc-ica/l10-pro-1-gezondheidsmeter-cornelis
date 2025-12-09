HEALTH SCORE STORAGE & DISPLAY SYSTEM
=====================================

YES - Scores ARE automatically stored and displayed! ✅

HOW IT WORKS:
=============

1. USER ANSWERS QUESTIONS:
   - User goes to /pages/vragen-hierarchical.php
   - Answers all hierarchical questions step-by-step

2. SCORE CALCULATION (Automatic):
   - HealthScoreCalculator class calculates score
   - Includes all adjustments (multipliers, BMI, age, excess penalties)

3. AUTOMATIC DATABASE STORAGE:
   - Score stored in user_health_scores table
   - Fields stored:
     * user_id - Which user
     * score_date - Date (today)
     * overall_score - Final 0-100 score
     * pillar_scores - JSON breakdown by category
     * calculation_details - JSON with full calculation breakdown
     * created_at - Timestamp

4. DISPLAY TO USER:
   - After questionnaire: Shows health score on vragen-hierarchical.php
   - Dedicated results page: /pages/results.php
   - Shows:
     * Today's score (big display)
     * 7-day average
     * Pillar breakdown
     * Trend data (last 30 days)
     * Detailed calculation breakdown
     * Clickable dates to view historical scores

CLASSES PROVIDED:
=================

1. HealthScoreCalculator (classes/HealthScoreCalculator.php)
   Methods:
   - calculateScore() → Returns array with score and breakdown
   - storeHealthScore() → Automatically stores to database
   
   Runs automatically when user completes questionnaire

2. UserHealthHistory (classes/UserHealthHistory.php)
   Methods to retrieve stored scores:
   - getTodayScore() → Get today's score
   - getScoreByDate(date) → Get score for specific date
   - getScoresLastDays(days) → Get last N days of scores
   - getAverageScore(days) → Calculate average
   - getPillarScores(date) → Get breakdown by category
   - getCalculationDetails(date) → Get full calculation details
   - getTrendData(days) → Get historical data for charts

PAGES:
======

/pages/vragen-hierarchical.php
- Where user answers questions
- Shows health score immediately after completion
- Links to /pages/results.php for detailed view

/pages/results.php (UPDATED)
- Displays comprehensive health information
- Shows:
  * Today's score (large display)
  * 7-day average
  * Total days with data
  * Pillar breakdown by category
  * Detailed calculation transparency
  * 30-day trend table with visualization
- Allows clicking dates to view past scores

DATABASE TABLE:
===============

user_health_scores:
├── id (BIGINT) - Primary key
├── user_id (BIGINT) - References users.id
├── score_date (DATE) - When score was calculated
├── overall_score (DECIMAL 5,2) - 0-100 score
├── pillar_scores (JSON) - Breakdown by category
├── calculation_details (JSON) - Full calculation details
├── created_at (TIMESTAMP) - When stored
└── UNIQUE constraint: (user_id, score_date)

EXAMPLE STORAGE (Automatic):
=============================

When user completes questionnaire:

INSERT INTO user_health_scores VALUES (
  NULL,                          -- id (auto)
  123,                           -- user_id
  '2025-12-08',                  -- score_date (today)
  75.50,                         -- overall_score
  '{"1":82.5, "2":70.3, ...}',  -- pillar_scores JSON
  '{...detailed breakdown...}',  -- calculation_details JSON
  NOW()                          -- created_at
)
ON DUPLICATE KEY UPDATE overall_score=75.50, ...

EXAMPLE RETRIEVAL:
==================

In any page where you want to show health:

<?php
require_once __DIR__ . '/../classes/UserHealthHistory.php';
$history = new UserHealthHistory($userId);

// Get today's score
$todayScore = $history->getTodayScore();
echo "Today: " . $todayScore['overall_score'] . "/100";

// Get average
$avg = $history->getAverageScore(7);
echo "7-day average: " . round($avg, 1);

// Get trend data for chart
$trend = $history->getTrendData(30);
foreach ($trend as $day) {
    echo $day['score_date'] . ": " . $day['overall_score'];
}

// Get detailed breakdown
$details = $history->getCalculationDetails('2025-12-08');
echo "<pre>" . json_encode($details, JSON_PRETTY_PRINT) . "</pre>";
?>

TRANSPARENCY:
==============

All calculations are stored with full details so users can see:
- Base score
- Pillar calculations
- Multiplier applications
- BMI adjustments
- Age adjustments
- Excess penalties
- Final formula

Users can click "Berekening Details" on results.php to see everything.

NEXT STEPS:
===========

1. Apply database migrations (alter_questions_hierarchical.sql)
2. Add category keywords for multipliers
3. Test questionnaire flow at /pages/vragen-hierarchical.php
4. View stored scores at /pages/results.php

Everything is automatic - scores are calculated and stored immediately after questionnaire completion!
