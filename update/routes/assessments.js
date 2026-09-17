// =====================================================
// Assessment Routes
// Handles assessment, tryout, and scoring
// =====================================================
const express = require('express');
const router = express.Router();
const { v4: uuidv4 } = require('uuid');
const { verifyToken } = require('../middleware/auth');
const { checkEntitlement, checkSubscription } = require('../middleware/entitlements');
const { validateAssessmentSubmission, validateTryoutQuery } = require('../middleware/validation');
const { logAssessmentSubmission } = require('../middleware/audit');
const db = require('../db/database');

/**
 * GET /api/assessments/tryout
 * List available tryouts for user
 */
router.get('/tryout', verifyToken, validateTryoutQuery, async (req, res) => {
  try {
    const userId = req.user.id;
    const { page, limit } = req.validatedQuery;

    // Get all active tryouts
    let tryouts = db.tryouts?.filter(t => t.is_active) || [];

    // Check user's attempt history
    const userAttempts = db.attempts?.filter(a => a.user_id === userId) || [];

    tryouts = tryouts.map(t => ({
      ...t,
      attempts: userAttempts.filter(a => a.tryout_id === t.id).length,
      last_attempt: userAttempts
        .filter(a => a.tryout_id === t.id)
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    }));

    // Paginate
    const startIdx = (page - 1) * limit;
    const paginatedTryouts = tryouts.slice(startIdx, startIdx + limit);

    res.json({
      success: true,
      data: {
        tryouts: paginatedTryouts,
        total: tryouts.length,
        page,
        limit
      }
    });
  } catch (error) {
    console.error('Fetch tryouts error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'FETCH_TRYOUTS_ERROR',
        message: 'Gagal mengambil daftar tryout.'
      }
    });
  }
});

/**
 * GET /api/assessments/tryout/:tryoutId/questions
 * Get questions for a specific tryout
 */
router.get('/tryout/:tryoutId/questions', verifyToken, async (req, res) => {
  try {
    const { tryoutId } = req.params;

    // Get tryout
    const tryout = db.tryouts?.find(t => t.id === tryoutId);
    if (!tryout) {
      return res.status(404).json({
        success: false,
        error: {
          code: 'TRYOUT_NOT_FOUND',
          message: 'Tryout tidak ditemukan.'
        }
      });
    }

    // Get questions for this tryout
    const questions = db.questions?.filter(q => q.tryout_id === tryoutId && q.is_active) || [];

    // Sanitize: remove correct_answer from frontend
    const sanitizedQuestions = questions.map(q => {
      const { correct_answer, ...rest } = q;
      return rest;
    });

    res.json({
      success: true,
      data: {
        tryout,
        questions: sanitizedQuestions,
        total_questions: sanitizedQuestions.length
      }
    });
  } catch (error) {
    console.error('Fetch tryout questions error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'FETCH_QUESTIONS_ERROR',
        message: 'Gagal mengambil soal tryout.'
      }
    });
  }
});

/**
 * POST /api/assessments/attempt/start
 * Start a new assessment attempt
 */
router.post('/attempt/start', verifyToken, checkSubscription, async (req, res) => {
  try {
    const userId = req.user.id;
    const { tryout_id } = req.body;

    if (!tryout_id) {
      return res.status(422).json({
        success: false,
        error: {
          code: 'MISSING_TRYOUT_ID',
          message: 'tryout_id harus disediakan.'
        }
      });
    }

    // Check if tryout exists
    const tryout = db.tryouts?.find(t => t.id === tryout_id);
    if (!tryout) {
      return res.status(404).json({
        success: false,
        error: {
          code: 'TRYOUT_NOT_FOUND',
          message: 'Tryout tidak ditemukan.'
        }
      });
    }

    // Create new attempt
    const attemptId = uuidv4();
    const attempt = {
      id: attemptId,
      user_id: userId,
      tryout_id,
      status: 'in_progress',
      start_time: new Date().toISOString(),
      end_time: null,
      score: null,
      percentile: null,
      created_at: new Date().toISOString()
    };

    if (!db.attempts) db.attempts = [];
    db.attempts.push(attempt);

    res.json({
      success: true,
      data: {
        attempt_id: attemptId,
        tryout_id,
        time_limit: tryout.time_limit_minutes,
        start_time: attempt.start_time
      }
    });
  } catch (error) {
    console.error('Start attempt error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'START_ATTEMPT_ERROR',
        message: 'Gagal memulai attempt.'
      }
    });
  }
});

/**
 * POST /api/assessments/attempt/:attemptId/submit
 * Submit assessment answers and calculate score
 */
router.post('/attempt/:attemptId/submit', 
  verifyToken, 
  validateAssessmentSubmission, 
  async (req, res) => {
  try {
    const userId = req.user.id;
    const { attemptId } = req.params;
    const { tryout_id, answers } = req.body;

    // Validate attempt belongs to user
    const attempt = db.attempts?.find(a => a.id === attemptId && a.user_id === userId);
    if (!attempt) {
      return res.status(404).json({
        success: false,
        error: {
          code: 'ATTEMPT_NOT_FOUND',
          message: 'Attempt tidak ditemukan atau tidak milik Anda.'
        }
      });
    }

    if (attempt.status === 'submitted') {
      return res.status(409).json({
        success: false,
        error: {
          code: 'ATTEMPT_ALREADY_SUBMITTED',
          message: 'Attempt sudah disubmit dan tidak bisa diubah (immutable).'
        }
      });
    }

    // Calculate score
    let correctCount = 0;
    const answerDetails = [];

    for (const answer of answers) {
      const question = db.questions?.find(q => q.id === answer.question_id);
      if (question) {
        const isCorrect = question.correct_answer === answer.selected_option;
        if (isCorrect) correctCount++;

        answerDetails.push({
          question_id: answer.question_id,
          selected_option: answer.selected_option,
          correct_answer: question.correct_answer,
          is_correct: isCorrect,
          irt_weight: question.irt_weight || 1
        });
      }
    }

    // Calculate score using IRT model (simple)
    const totalIrtWeight = answerDetails.reduce((sum, a) => sum + (a.is_correct ? a.irt_weight : 0), 0);
    const maxIrtWeight = answerDetails.reduce((sum, a) => sum + a.irt_weight, 0);
    const score = Math.round((totalIrtWeight / maxIrtWeight) * 100);

    // Update attempt with immutable result
    attempt.status = 'submitted';
    attempt.end_time = new Date().toISOString();
    attempt.score = score;
    attempt.correct_count = correctCount;
    attempt.total_questions = answers.length;
    attempt.answer_details = answerDetails; // Store for audit

    // Log to audit
    logAssessmentSubmission(userId, tryout_id, attemptId, score, 0);

    res.json({
      success: true,
      data: {
        attempt_id: attemptId,
        score,
        correct_count: correctCount,
        total_questions: answers.length,
        percentage: Math.round((correctCount / answers.length) * 100)
      }
    });
  } catch (error) {
    console.error('Submit attempt error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'SUBMIT_ATTEMPT_ERROR',
        message: 'Gagal submit jawaban.'
      }
    });
  }
});

/**
 * GET /api/assessments/attempt/:attemptId/result
 * Get assessment result (only after submitted)
 */
router.get('/attempt/:attemptId/result', verifyToken, async (req, res) => {
  try {
    const userId = req.user.id;
    const { attemptId } = req.params;

    const attempt = db.attempts?.find(a => a.id === attemptId && a.user_id === userId);
    if (!attempt) {
      return res.status(404).json({
        success: false,
        error: {
          code: 'ATTEMPT_NOT_FOUND',
          message: 'Attempt tidak ditemukan.'
        }
      });
    }

    if (attempt.status !== 'submitted') {
      return res.status(409).json({
        success: false,
        error: {
          code: 'ATTEMPT_NOT_SUBMITTED',
          message: 'Attempt belum disubmit.'
        }
      });
    }

    res.json({
      success: true,
      data: {
        attempt_id: attemptId,
        status: attempt.status,
        score: attempt.score,
        correct_count: attempt.correct_count,
        total_questions: attempt.total_questions,
        percentage: Math.round((attempt.correct_count / attempt.total_questions) * 100),
        start_time: attempt.start_time,
        end_time: attempt.end_time,
        submitted_at: attempt.created_at
      }
    });
  } catch (error) {
    console.error('Get result error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'GET_RESULT_ERROR',
        message: 'Gagal mengambil hasil.'
      }
    });
  }
});

module.exports = router;
