<?php
// api/v1/scoring/AssessmentScoringEngine.php

require_once 'ScoringResult.php';

interface AssessmentScoringEngine
{
    /**
     * @param array $attemptData Must contain:
     *   - 'config' => array
     *   - 'questions' => array of [id, default_weight, weight_override, answer_key]
     *   - 'responses' => array of [assessment_question_id => response_value]
     * @return ScoringResult
     */
    public function calculate(array $attemptData): ScoringResult;
}
