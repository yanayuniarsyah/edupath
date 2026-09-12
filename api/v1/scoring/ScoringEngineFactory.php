<?php
// api/v1/scoring/ScoringEngineFactory.php

require_once 'QuizV1Engine.php';

class ScoringEngineFactory
{
    public static function make(array $config): AssessmentScoringEngine
    {
        if (!isset($config['strategy'])) {
            throw new Exception("INVALID_SCORING_CONFIG");
        }

        $strategy = $config['strategy'];

        if ($strategy === 'quiz_v1') {
            return new QuizV1Engine();
        }

        throw new Exception("SCORING_ENGINE_NOT_FOUND");
    }
}
