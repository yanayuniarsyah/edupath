// =====================================================
// Input Validation Middleware
// Sanitize & validate all incoming requests
// =====================================================

/**
 * Validate UUID format
 */
const isValidUUID = (uuid) => {
  const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
  return uuidRegex.test(uuid);
};

/**
 * Validate email format
 */
const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

/**
 * Validate integer range
 */
const isValidInteger = (value, min, max) => {
  const num = parseInt(value);
  if (isNaN(num)) return false;
  return num >= min && num <= max;
};

/**
 * Sanitize string input
 */
const sanitizeString = (str) => {
  if (typeof str !== 'string') return '';
  return str
    .trim()
    .replace(/[<>"']/g, '') // Remove potentially dangerous characters
    .substring(0, 500); // Limit length
};

/**
 * Middleware: Validate Assessment Submission
 */
const validateAssessmentSubmission = (req, res, next) => {
  try {
    const { assessment_id, attempt_id, answers } = req.body;

    // Validate required fields
    if (!assessment_id || !isValidUUID(assessment_id)) {
      return res.status(422).json({
        success: false,
        error: {
          code: 'INVALID_ASSESSMENT_ID',
          message: 'ID assessment tidak valid.'
        }
      });
    }

    if (!attempt_id || !isValidUUID(attempt_id)) {
      return res.status(422).json({
        success: false,
        error: {
          code: 'INVALID_ATTEMPT_ID',
          message: 'ID attempt tidak valid.'
        }
      });
    }

    if (!Array.isArray(answers)) {
      return res.status(422).json({
        success: false,
        error: {
          code: 'INVALID_ANSWERS',
          message: 'Format answers tidak valid.'
        }
      });
    }

    // Validate each answer
    for (const answer of answers) {
      if (!answer.question_id || !isValidUUID(answer.question_id)) {
        return res.status(422).json({
          success: false,
          error: {
            code: 'INVALID_QUESTION_ID',
            message: 'ID pertanyaan tidak valid.'
          }
        });
      }

      if (answer.selected_option === undefined || answer.selected_option === null) {
        return res.status(422).json({
          success: false,
          error: {
            code: 'MISSING_SELECTED_OPTION',
            message: 'Pilihan jawaban harus dipilih untuk setiap soal.'
          }
        });
      }
    }

    next();
  } catch (error) {
    console.error('Assessment submission validation error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Gagal validasi submission.'
      }
    });
  }
};

/**
 * Middleware: Validate Query Parameters (Tryout)
 */
const validateTryoutQuery = (req, res, next) => {
  try {
    const { tryout_id, page, limit } = req.query;

    if (tryout_id && !isValidUUID(tryout_id)) {
      return res.status(422).json({
        success: false,
        error: {
          code: 'INVALID_TRYOUT_ID',
          message: 'ID tryout tidak valid.'
        }
      });
    }

    const pageNum = parseInt(page) || 1;
    const limitNum = parseInt(limit) || 10;

    if (pageNum < 1 || limitNum < 1 || limitNum > 100) {
      return res.status(422).json({
        success: false,
        error: {
          code: 'INVALID_PAGINATION',
          message: 'Parameter page/limit tidak valid.'
        }
      });
    }

    req.validatedQuery = { tryout_id, page: pageNum, limit: limitNum };
    next();
  } catch (error) {
    console.error('Tryout query validation error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Gagal validasi query.'
      }
    });
  }
};

module.exports = {
  isValidUUID,
  isValidEmail,
  isValidInteger,
  sanitizeString,
  validateAssessmentSubmission,
  validateTryoutQuery
};
