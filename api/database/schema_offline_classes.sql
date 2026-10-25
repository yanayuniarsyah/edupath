-- Schema Migration: Offline Classes, Attendance & SPP Certificate
-- Platform: EduPath SaaS (Production-Grade)

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tambah Kolom Telegram di tabel students
ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `telegram_chat_id` varchar(50) DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `parent_telegram_id` varchar(50) DEFAULT NULL AFTER `telegram_chat_id`,
  ADD KEY IF NOT EXISTS `idx_parent_tg` (`parent_telegram_id`);

-- 2. Ruang Kelas Fisik Cabang
CREATE TABLE IF NOT EXISTS `class_rooms` (
  `id` varchar(36) NOT NULL,
  `tenant_id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 15,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cr_tenant_fk` (`tenant_id`),
  CONSTRAINT `cr_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Rombongan Belajar (Classes)
CREATE TABLE IF NOT EXISTS `classes` (
  `id` varchar(36) NOT NULL,
  `tenant_id` varchar(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `default_room_id` varchar(36) DEFAULT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 15,
  `status` enum('draft','active','graduated','archived') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cls_tenant_fk` (`tenant_id`),
  KEY `cls_room_fk` (`default_room_id`),
  CONSTRAINT `cls_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cls_room_fk` FOREIGN KEY (`default_room_id`) REFERENCES `class_rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Penugasan Siswa ke Rombel
CREATE TABLE IF NOT EXISTS `class_students` (
  `id` varchar(36) NOT NULL,
  `class_id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','transferred','dropped') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_class_student` (`class_id`, `student_id`),
  KEY `cs_student_fk` (`student_id`),
  CONSTRAINT `cs_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cs_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Template Jadwal Mingguan
CREATE TABLE IF NOT EXISTS `class_schedules` (
  `id` varchar(36) NOT NULL,
  `class_id` varchar(36) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(100) NOT NULL,
  `room_id` varchar(36) NOT NULL,
  `primary_teacher_id` varchar(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `csch_class_fk` (`class_id`),
  KEY `csch_room_fk` (`room_id`),
  CONSTRAINT `csch_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `csch_room_fk` FOREIGN KEY (`room_id`) REFERENCES `class_rooms` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Sesi Riil Kalender & Penanganan Reschedule/Inval
CREATE TABLE IF NOT EXISTS `class_sessions` (
  `id` varchar(36) NOT NULL,
  `class_id` varchar(36) NOT NULL,
  `schedule_id` varchar(36) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_id` varchar(36) NOT NULL,
  `teacher_id` varchar(36) NOT NULL,
  `original_teacher_id` varchar(36) DEFAULT NULL,
  `session_type` enum('regular','rescheduled','inval','makeup_clinic') DEFAULT 'regular',
  `status` enum('scheduled','in_progress','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `reschedule_reason` text DEFAULT NULL,
  `rescheduled_to_session_id` varchar(36) DEFAULT NULL,
  `tutor_fee_snapshot` decimal(12,2) DEFAULT NULL,
  `bap_notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ses_class_date` (`class_id`, `session_date`),
  KEY `ses_room_time` (`room_id`, `session_date`, `start_time`),
  KEY `ses_teacher_time` (`teacher_id`, `session_date`, `start_time`),
  CONSTRAINT `ses_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ses_room_fk` FOREIGN KEY (`room_id`) REFERENCES `class_rooms` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Presensi Siswa per Sesi
CREATE TABLE IF NOT EXISTS `session_attendances` (
  `id` varchar(36) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `attendance_status` enum('present','late','sick','permitted','absent') NOT NULL DEFAULT 'absent',
  `minutes_late` int(11) DEFAULT 0,
  `is_makeup_guest` tinyint(1) DEFAULT 0,
  `check_in_time` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_student` (`session_id`, `student_id`),
  KEY `att_student_fk` (`student_id`),
  CONSTRAINT `att_session_fk` FOREIGN KEY (`session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `att_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Sertifikat SPP Terverifikasi
CREATE TABLE IF NOT EXISTS `spp_certificates` (
  `id` varchar(36) NOT NULL,
  `tenant_id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `attempt_id` varchar(36) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `student_name_snapshot` varchar(150) NOT NULL,
  `school_name_snapshot` varchar(150) DEFAULT NULL,
  `riasec_code` varchar(10) NOT NULL,
  `learning_style` varchar(50) NOT NULL,
  `top_recommendations` text DEFAULT NULL,
  `issue_date` date NOT NULL,
  `verification_hash` varchar(64) NOT NULL,
  `pdf_url` varchar(255) DEFAULT NULL,
  `pdf_generation_status` enum('pending','processing','ready','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cert_number` (`certificate_number`),
  UNIQUE KEY `uq_cert_attempt` (`attempt_id`),
  KEY `cert_tenant_fk` (`tenant_id`),
  KEY `cert_student_fk` (`student_id`),
  CONSTRAINT `cert_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cert_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
