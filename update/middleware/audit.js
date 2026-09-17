// =====================================================
// Audit Logging Middleware
// Track all important actions for compliance
// =====================================================
const fs = require('fs');
const path = require('path');

const auditLogPath = path.join(__dirname, '../data/audit-logs.jsonl');

/**
 * Log audit event to file (JSONL format for streaming)
 */
const logAuditEvent = (event) => {
  const timestamp = new Date().toISOString();
  const logEntry = {
    timestamp,
    ...event
  };

  try {
    fs.appendFileSync(auditLogPath, JSON.stringify(logEntry) + '\n', 'utf8');
  } catch (error) {
    console.error('Failed to write audit log:', error);
  }
};

/**
 * Middleware: Log all API requests
 */
const auditMiddleware = (req, res, next) => {
  const startTime = Date.now();
  const userId = req.user?.id || 'anonymous';
  const method = req.method;
  const url = req.originalUrl;
  const ip = req.ip || req.connection.remoteAddress;

  // Override res.json to capture response
  const originalJson = res.json.bind(res);
  res.json = function(data) {
    const duration = Date.now() - startTime;
    
    // Log important mutations (POST, PUT, PATCH, DELETE)
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      logAuditEvent({
        user_id: userId,
        action: `${method} ${url}`,
        method,
        url,
        ip,
        status: res.statusCode,
        duration,
        request_body: req.body,
        response_status: data?.success
      });
    }

    return originalJson(data);
  };

  next();
};

/**
 * Helper: Log assessment submission
 */
const logAssessmentSubmission = (userId, assessmentId, attemptId, score, duration) => {
  logAuditEvent({
    event_type: 'ASSESSMENT_SUBMISSION',
    user_id: userId,
    assessment_id: assessmentId,
    attempt_id: attemptId,
    score,
    duration
  });
};

/**
 * Helper: Log payment
 */
const logPayment = (userId, orderId, amount, status) => {
  logAuditEvent({
    event_type: 'PAYMENT',
    user_id: userId,
    order_id: orderId,
    amount,
    status
  });
};

/**
 * Helper: Log admin action
 */
const logAdminAction = (adminId, action, targetId, details) => {
  logAuditEvent({
    event_type: 'ADMIN_ACTION',
    admin_id: adminId,
    action,
    target_id: targetId,
    details
  });
};

module.exports = {
  auditMiddleware,
  logAuditEvent,
  logAssessmentSubmission,
  logPayment,
  logAdminAction
};
