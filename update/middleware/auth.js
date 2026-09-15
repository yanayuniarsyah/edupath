// =====================================================
// Authentication Middleware
// Validates JWT tokens and extracts user context
// =====================================================
const jwt = require('jsonwebtoken');

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';

/**
 * Middleware: Validate JWT Token
 * Extracts user from token and attaches to req.user
 */
const verifyToken = (req, res, next) => {
  try {
    // Get token dari Authorization header atau Cookie
    const token = req.headers.authorization?.split(' ')[1] || req.cookies?.ep_access_token;
    
    if (!token) {
      return res.status(401).json({
        success: false,
        error: {
          code: 'UNAUTHORIZED',
          message: 'Token tidak ditemukan. Silakan login terlebih dahulu.'
        }
      });
    }

    // Verify JWT
    const decoded = jwt.verify(token, JWT_SECRET);
    req.user = decoded; // Attach user context ke request
    next();
  } catch (error) {
    return res.status(401).json({
      success: false,
      error: {
        code: 'INVALID_TOKEN',
        message: 'Token tidak valid atau sudah expired.'
      }
    });
  }
};

/**
 * Middleware: Validate Admin Token
 */
const verifyAdminToken = (req, res, next) => {
  verifyToken(req, res, () => {
    if (req.user.role !== 'admin') {
      return res.status(403).json({
        success: false,
        error: {
          code: 'FORBIDDEN',
          message: 'Anda tidak memiliki akses ke resource ini.'
        }
      });
    }
    next();
  });
};

module.exports = { verifyToken, verifyAdminToken, JWT_SECRET };
