SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `academies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `address` text,
  `email` varchar(255) DEFAULT NULL,
  `working_hours` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `academy_classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_description` text NOT NULL,
  `academy_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `accounting` (
  `id` int(11) NOT NULL,
  `course_plan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_method` int(11) DEFAULT NULL,
  `bank_name` int(11) DEFAULT NULL,
  `payment_notes` varchar(250) DEFAULT NULL,
  `received_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `banks` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `course_plans` (
  `id` int(11) NOT NULL,
  `academy_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_date_1` datetime DEFAULT NULL,
  `course_date_2` datetime DEFAULT NULL,
  `course_date_3` datetime DEFAULT NULL,
  `course_date_4` datetime DEFAULT NULL,
  `course_attendance_1` tinyint(1) DEFAULT NULL,
  `course_attendance_2` tinyint(1) DEFAULT NULL,
  `course_attendance_3` tinyint(1) DEFAULT NULL,
  `course_attendance_4` tinyint(1) DEFAULT NULL,
  `course_fee` int(11) DEFAULT NULL,
  `debt_amount` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `introductory_course_plans` (
  `id` int(11) NOT NULL,
  `academy_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_date` datetime DEFAULT NULL,
  `course_attendance` tinyint(1) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(20) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `log_details` text,
  `changed_data` json DEFAULT NULL,
  `ip_address` varchar(20) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `options` (
  `option_id` int(11) NOT NULL,
  `option_name` varchar(255) DEFAULT NULL,
  `option_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `rescheduled_courses` (
  `id` int(11) NOT NULL,
  `course_plan_id` int(11) DEFAULT NULL,
  `academy_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_date` datetime DEFAULT NULL,
  `course_attendance` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `sent_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `message_text` text,
  `send_as_sms` tinyint(4) NOT NULL DEFAULT '0',
  `send_as_email` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `student_parents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `tc_identity` varchar(250) NOT NULL,
  `first_name` varchar(250) NOT NULL,
  `last_name` varchar(250) NOT NULL,
  `phone` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `city` varchar(250) DEFAULT NULL,
  `district` varchar(250) DEFAULT NULL,
  `blood_type` varchar(50) DEFAULT NULL,
  `health_issue` varchar(250) DEFAULT NULL,
  `country` varchar(250) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `password` varchar(250) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_type` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `invoice_type` varchar(255) DEFAULT NULL,
  `tax_company_name` varchar(255) DEFAULT NULL,
  `tax_office` varchar(255) DEFAULT NULL,
  `tax_number` varchar(20) DEFAULT NULL,
  `tc_identity_for_individual_invoice` varchar(250) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `profile_photo` varchar(255) DEFAULT NULL,
  `two_factor_code` text,
  `two_factor_enabled` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_academy_assignment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `academy_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `description` text,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `verification_code_email` varchar(255) DEFAULT NULL,
  `verification_code_sms` varchar(255) DEFAULT NULL,
  `verification_ip_email` varchar(255) DEFAULT NULL,
  `verification_ip_sms` varchar(255) DEFAULT NULL,
  `verification_time_email_sent` datetime DEFAULT NULL,
  `verification_time_sms_sent` datetime DEFAULT NULL,
  `verification_time_email_confirmed` datetime DEFAULT NULL,
  `verification_time_sms_confirmed` datetime DEFAULT NULL,
  `verification_signature_email` text,
  `verification_signature_sms` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `academies`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `academy_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academy_id` (`academy_id`);

ALTER TABLE `accounting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accounting_entries_ibfk_5` (`payment_method`),
  ADD KEY `course_plan_id` (`course_plan_id`),
  ADD KEY `bank_name` (`bank_name`);

ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `course_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academy_id` (`academy_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `academy_courses_ibfk_4` (`class_id`),
  ADD KEY `student_courses_ibfk_5` (`student_id`),
  ADD KEY `student_courses_ibfk_3` (`teacher_id`);

ALTER TABLE `introductory_course_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academy_id` (`academy_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `academy_courses_ibfk_4` (`class_id`),
  ADD KEY `student_courses_ibfk_5` (`student_id`),
  ADD KEY `student_courses_ibfk_3` (`teacher_id`);

ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`);

ALTER TABLE `options`
  ADD PRIMARY KEY (`option_id`);

ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `rescheduled_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academy_id` (`academy_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `academy_courses_ibfk_4` (`class_id`),
  ADD KEY `student_courses_ibfk_5` (`student_id`),
  ADD KEY `student_courses_ibfk_3` (`teacher_id`),
  ADD KEY `rescheduled_courses_ibfk_1` (`course_plan_id`);

ALTER TABLE `sent_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `sender_id` (`sender_id`);

ALTER TABLE `student_parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_parents_ibfk_1` (`parent_id`),
  ADD KEY `student_parents_ibfk_2` (`student_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_type` (`user_type`);

ALTER TABLE `user_academy_assignment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `academy_id` (`academy_id`);

ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user_uploads`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verifications_ibfk_1` (`user_id`);


ALTER TABLE `academies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `academy_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `accounting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `course_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `introductory_course_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rescheduled_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sent_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `student_parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_academy_assignment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `academy_classes`
  ADD CONSTRAINT `academy_classes_ibfk_1` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE;

ALTER TABLE `accounting`
  ADD CONSTRAINT `accounting_ibfk_1` FOREIGN KEY (`course_plan_id`) REFERENCES `course_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accounting_ibfk_2` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accounting_ibfk_3` FOREIGN KEY (`bank_name`) REFERENCES `banks` (`id`) ON DELETE CASCADE;

ALTER TABLE `course_plans`
  ADD CONSTRAINT `course_plans_ibfk_1` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_plans_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `academy_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_plans_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_plans_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_plans_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `introductory_course_plans`
  ADD CONSTRAINT `introductory_course_plans_ibfk_1` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `introductory_course_plans_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `academy_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `introductory_course_plans_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `introductory_course_plans_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `introductory_course_plans_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `rescheduled_courses`
  ADD CONSTRAINT `rescheduled_courses_ibfk_1` FOREIGN KEY (`course_plan_id`) REFERENCES `course_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rescheduled_courses_ibfk_2` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rescheduled_courses_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `academy_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rescheduled_courses_ibfk_4` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rescheduled_courses_ibfk_5` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rescheduled_courses_ibfk_6` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `sent_messages`
  ADD CONSTRAINT `sent_messages_ibfk_1` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sent_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `student_parents`
  ADD CONSTRAINT `student_parents_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `student_parents_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type`) REFERENCES `user_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_academy_assignment`
  ADD CONSTRAINT `user_academy_assignment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_academy_assignment_ibfk_2` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE;

ALTER TABLE `verifications`
  ADD CONSTRAINT `verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
