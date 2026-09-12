<?php
// api/v1/scoring/ScoringResult.php

class ScoringResult
{
    public ?float $total_score;
    public ?array $profile_metadata;
    
    // Array of ['question_id' => ['is_correct' => bool|null, 'score_earned' => float]]
    public array $question_scores;

    public function __construct(?float $total_score, ?array $profile_metadata, array $question_scores)
    {
        $this->total_score = $total_score;
        $this->profile_metadata = $profile_metadata;
        $this->question_scores = $question_scores;
    }
}
