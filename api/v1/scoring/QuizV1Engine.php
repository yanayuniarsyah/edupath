<?php
// api/v1/scoring/QuizV1Engine.php

require_once 'AssessmentScoringEngine.php';

class QuizV1Engine implements AssessmentScoringEngine
{
    public function calculate(array $attemptData): ScoringResult
    {
        $config = $attemptData['config'] ?? [];
        $questions = $attemptData['questions'] ?? [];
        $responses = $attemptData['responses'] ?? [];

        // Config validation
        if (!isset($config['strategy']) || $config['strategy'] !== 'quiz_v1') {
            throw new Exception("INVALID_SCORING_CONFIG");
        }

        $unansweredPenalty = $config['unanswered_penalty'] ?? 0.0;
        $wrongPenalty = $config['wrong_penalty'] ?? 0.0;

        $totalScore = 0.0;
        $questionScores = [];

        foreach ($questions as $q) {
            $aqId = $q['assessment_question_id'];
            
            // Resolve Weight Precedence
            $weight = 1.0;
            if (isset($q['weight_override']) && $q['weight_override'] !== null) {
                $weight = (float)$q['weight_override'];
            } elseif (isset($q['default_weight']) && $q['default_weight'] !== null) {
                $weight = (float)$q['default_weight'];
            }

            // quiz_v1 strategy rejects negative weight globally
            if ($weight < 0) {
                throw new Exception("INVALID_SCORING_CONFIG");
            }

            $answerKey = $q['answer_key'] ?? [];
            if (is_string($answerKey)) {
                $answerKey = json_decode($answerKey, true);
            }
            $correctAnswer = $answerKey['correct'] ?? null;

            $responseVal = $responses[$aqId] ?? null;

            $isCorrect = null;
            $scoreEarned = 0.0;

            if ($responseVal === null || $responseVal === '') {
                // Unanswered
                $isCorrect = false;
                $scoreEarned = (float)$unansweredPenalty;
            } else {
                if ((string)$responseVal === (string)$correctAnswer) {
                    // Correct
                    $isCorrect = true;
                    $scoreEarned = $weight;
                } else {
                    // Wrong or Invalid
                    $isCorrect = false;
                    $scoreEarned = (float)$wrongPenalty;
                }
            }

            $totalScore += $scoreEarned;

            $questionScores[$aqId] = [
                'is_correct' => $isCorrect,
                'score_earned' => $scoreEarned
            ];
        }

        return new ScoringResult($totalScore, null, $questionScores);
    }
}
