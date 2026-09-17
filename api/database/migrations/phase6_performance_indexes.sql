-- EduPath performance indexes. Run once after phase3g_questions_enhancements,
-- mobile_readiness, phase4_reliability, and the payment/order migrations.
-- These indexes support the high-frequency auth, quiz, payment, and rate-limit lookups.

ALTER TABLE `questions`
  ADD KEY `questions_quiz_filter_idx` (`is_active`, `classification`, `sub_materi`);

ALTER TABLE `refresh_tokens`
  ADD KEY `refresh_token_lookup_idx` (`token_hash`, `revoked_at`, `expires_at`);

ALTER TABLE `rate_limits`
  ADD KEY `rate_limits_cleanup_idx` (`lock_until`);

ALTER TABLE `orders`
  ADD KEY `orders_student_status_idx` (`student_id`, `status`, `created_at`);
