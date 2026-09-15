// =====================================================
// Entitlements Checker Middleware
// Validates user subscription & feature access
// Server-side verification — DO NOT TRUST CLIENT
// =====================================================
const db = require('../db/database');

/**
 * Middleware: Check Feature Entitlement
 * @param {string} featureKey - e.g., 'tryout_unlimited', 'ai_tutor'
 */
const checkEntitlement = (featureKey) => {
  return (req, res, next) => {
    try {
      // 1. Get user dari context (must be authenticated)
      const userId = req.user?.id;
      if (!userId) {
        return res.status(401).json({
          success: false,
          error: {
            code: 'UNAUTHORIZED',
            message: 'User context tidak ditemukan.'
          }
        });
      }

      // 2. Check subscription status SERVER-SIDE
      const subscription = db.subscriptions?.find(s => 
        s.user_id === userId && 
        s.status === 'active' && 
        new Date(s.expired_at) > new Date()
      );

      if (!subscription) {
        return res.status(403).json({
          success: false,
          error: {
            code: 'NO_ACTIVE_SUBSCRIPTION',
            message: 'Anda tidak memiliki subscription aktif.'
          }
        });
      }

      // 3. Check plan entitlement
      const plan = db.plans?.find(p => p.id === subscription.plan_id);
      if (!plan) {
        return res.status(403).json({
          success: false,
          error: {
            code: 'PLAN_NOT_FOUND',
            message: 'Paket langganan tidak ditemukan.'
          }
        });
      }

      // 4. Check if plan includes this feature
      const planFeatures = plan.features || [];
      if (!planFeatures.includes(featureKey)) {
        return res.status(403).json({
          success: false,
          error: {
            code: 'FEATURE_NOT_INCLUDED',
            message: `Fitur '${featureKey}' tidak termasuk dalam paket Anda.`
          }
        });
      }

      // 5. Attach entitlement context ke request
      req.entitlement = {
        subscription,
        plan,
        feature: featureKey,
        isValid: true
      };

      next();
    } catch (error) {
      console.error('Entitlement check error:', error);
      res.status(500).json({
        success: false,
        error: {
          code: 'ENTITLEMENT_ERROR',
          message: 'Gagal memeriksa entitlement.'
        }
      });
    }
  };
};

/**
 * Middleware: Check Subscription (flexible)
 */
const checkSubscription = (req, res, next) => {
  try {
    const userId = req.user?.id;
    if (!userId) {
      return res.status(401).json({
        success: false,
        error: {
          code: 'UNAUTHORIZED',
          message: 'User context tidak ditemukan.'
        }
      });
    }

    const subscription = db.subscriptions?.find(s => 
      s.user_id === userId && 
      s.status === 'active' && 
      new Date(s.expired_at) > new Date()
    );

    if (!subscription) {
      return res.status(403).json({
        success: false,
        error: {
          code: 'NO_ACTIVE_SUBSCRIPTION',
          message: 'Anda tidak memiliki subscription aktif.'
        }
      });
    }

    req.subscription = subscription;
    next();
  } catch (error) {
    console.error('Subscription check error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'SUBSCRIPTION_ERROR',
        message: 'Gagal memeriksa subscription.'
      }
    });
  }
};

module.exports = { checkEntitlement, checkSubscription };
