// =====================================================
// Webhook Signature Verification
// Secure payment webhook from Midtrans
// =====================================================
const crypto = require('crypto');

const MIDTRANS_SERVER_KEY = process.env.MIDTRANS_SERVER_KEY || 'your-midtrans-server-key';

/**
 * Verify Midtrans webhook signature
 * Signature = SHA256(order_id + status_code + gross_amount + server_key)
 */
const verifyMidtransSignature = (req, res, next) => {
  try {
    const { order_id, status_code, gross_amount, signature_key } = req.body;

    // Reconstruct the signature
    const data = order_id + status_code + gross_amount + MIDTRANS_SERVER_KEY;
    const hash = crypto.createHash('sha256').update(data).digest('hex');

    if (hash !== signature_key) {
      console.warn('Invalid Midtrans webhook signature:', {
        order_id,
        expected: hash,
        received: signature_key
      });
      
      return res.status(401).json({
        success: false,
        error: {
          code: 'INVALID_SIGNATURE',
          message: 'Webhook signature tidak valid.'
        }
      });
    }

    // Signature valid, proceed
    req.midtransPayload = req.body;
    next();
  } catch (error) {
    console.error('Webhook signature verification error:', error);
    res.status(500).json({
      success: false,
      error: {
        code: 'WEBHOOK_ERROR',
        message: 'Gagal memverifikasi webhook.'
      }
    });
  }
};

module.exports = { verifyMidtransSignature };
