-- Update for Jhoanna Aycardo (emp_code 12) and Marjo Cortez (emp_code 15)
-- Generated 2026-10-29 from the corrected local data.
--   1. Salary, position and payroll deductions (from the HR payslip)
--   2. Marjo's rest day: Monday only (removes the stale Saturday rest days)
--   3. Biometric punches for Oct 11-25, 2026 (from ADMIN.xls)
-- Safe to run more than once. Employees are matched by emp_code AND name;
-- if a match fails, its variable is NULL and that employee's statements do nothing.

START TRANSACTION;

SET @jho := (SELECT id FROM employees WHERE emp_code = '12' AND first_name = 'Jhoanna' AND last_name = 'Aycardo');
SET @mar := (SELECT id FROM employees WHERE emp_code = '15' AND first_name = 'Marjo' AND last_name = 'Cortez');
-- Check both ids are NOT NULL before committing:
SELECT @jho AS jhoanna_id, @mar AS marjo_id;

-- 1. Position ---------------------------------------------------------------
UPDATE employees SET position = 'Bookkeeper I', updated_at = NOW() WHERE id = @jho;
UPDATE employees SET position = 'Supply and Procurement Staff', updated_at = NOW() WHERE id = @mar;

-- 1. Salary (monthly rate; daily = monthly x 12 / 312, hourly = monthly x 12 / 365 / 8)
INSERT INTO salary_details (employee_id, rate_type, basic_salary, daily_rate, hourly_rate, effective_date, is_active, created_at, updated_at)
SELECT @jho, 'monthly', 18765, 721.73, 77.12, '2026-10-29', 1, NOW(), NOW() FROM DUAL WHERE @jho IS NOT NULL
ON DUPLICATE KEY UPDATE rate_type = 'monthly', basic_salary = 18765, daily_rate = 721.73, hourly_rate = 77.12, effective_date = '2026-10-29', is_active = 1, updated_at = NOW();
INSERT INTO salary_details (employee_id, rate_type, basic_salary, daily_rate, hourly_rate, effective_date, is_active, created_at, updated_at)
SELECT @mar, 'monthly', 16658, 640.69, 68.46, '2026-10-29', 1, NOW(), NOW() FROM DUAL WHERE @mar IS NOT NULL
ON DUPLICATE KEY UPDATE rate_type = 'monthly', basic_salary = 16658, daily_rate = 640.69, hourly_rate = 68.46, effective_date = '2026-10-29', is_active = 1, updated_at = NOW();

-- 1. Deductions (deduction type looked up by code) ----------------------------
UPDATE other_deductions SET description = 'Salary Loan', amount_per_cutoff = 2830, cutoff_schedule = 'both', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @jho AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'OTHER_LOAN');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @jho, dt.id, 'Salary Loan', 2830, 'both', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'OTHER_LOAN' AND @jho IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @jho AND od.deduction_type_id = dt.id);
UPDATE other_deductions SET description = 'Cooperative Savings', amount_per_cutoff = 500, cutoff_schedule = 'both', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @jho AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'COOP_SAV');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @jho, dt.id, 'Cooperative Savings', 500, 'both', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'COOP_SAV' AND @jho IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @jho AND od.deduction_type_id = dt.id);
UPDATE other_deductions SET description = 'SSS Contri', amount_per_cutoff = 950, cutoff_schedule = '1st', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @jho AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'SSS');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @jho, dt.id, 'SSS Contri', 950, '1st', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'SSS' AND @jho IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @jho AND od.deduction_type_id = dt.id);
UPDATE other_deductions SET description = 'PHIL-HEALTH', amount_per_cutoff = 469.13, cutoff_schedule = '1st', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @jho AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'PHC');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @jho, dt.id, 'PHIL-HEALTH', 469.13, '1st', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'PHC' AND @jho IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @jho AND od.deduction_type_id = dt.id);
UPDATE other_deductions SET description = 'Cooperative Savings', amount_per_cutoff = 5000, cutoff_schedule = 'both', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @mar AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'COOP_SAV');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @mar, dt.id, 'Cooperative Savings', 5000, 'both', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'COOP_SAV' AND @mar IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @mar AND od.deduction_type_id = dt.id);
UPDATE other_deductions SET description = 'SSS Contri', amount_per_cutoff = 825, cutoff_schedule = '1st', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @mar AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'SSS');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @mar, dt.id, 'SSS Contri', 825, '1st', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'SSS' AND @mar IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @mar AND od.deduction_type_id = dt.id);
UPDATE other_deductions SET description = 'PHIL-HEALTH', amount_per_cutoff = 416.45, cutoff_schedule = '1st', auto_stop = 0, is_active = 1, updated_at = NOW() WHERE employee_id = @mar AND deduction_type_id = (SELECT id FROM deduction_types WHERE code = 'PHC');
INSERT INTO other_deductions (employee_id, deduction_type_id, description, amount_per_cutoff, cutoff_schedule, auto_stop, is_active, created_at, updated_at)
SELECT @mar, dt.id, 'PHIL-HEALTH', 416.45, '1st', 0, 1, NOW(), NOW() FROM deduction_types dt WHERE dt.code = 'PHC' AND @mar IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM other_deductions od WHERE od.employee_id = @mar AND od.deduction_type_id = dt.id);

-- 2. Marjo's rest day: Monday ------------------------------------------------
UPDATE employees SET weekday_off = 1, updated_at = NOW() WHERE id = @mar;
DELETE FROM day_offs WHERE employee_id = @mar AND type = 'rest_day' AND recurring_day_of_week = 6 AND date >= '2026-10-05';
INSERT INTO day_offs (employee_id, date, type, description, is_recurring, recurring_day_of_week, created_at, updated_at)
SELECT @mar, m.d, 'rest_day', 'DAYOFF', 1, 1, NOW(), NOW() FROM (
  SELECT '2026-10-05' AS d
  UNION ALL SELECT '2026-10-12'
  UNION ALL SELECT '2026-10-19'
  UNION ALL SELECT '2026-10-26'
  UNION ALL SELECT '2026-11-02'
  UNION ALL SELECT '2026-11-09'
  UNION ALL SELECT '2026-11-16'
  UNION ALL SELECT '2026-11-23'
  UNION ALL SELECT '2026-11-30'
  UNION ALL SELECT '2026-12-07'
  UNION ALL SELECT '2026-12-14'
  UNION ALL SELECT '2026-12-21'
  UNION ALL SELECT '2026-12-28'
) m
WHERE @mar IS NOT NULL AND NOT EXISTS (SELECT 1 FROM day_offs x WHERE x.employee_id = @mar AND x.date = m.d);

-- 3. Punches Oct 11-25, 2026 (skipped when a punch already exists in the same minute)
-- Jhoanna: 39 punches
INSERT INTO attendance_logs (employee_id, emp_code, punch_time, punch_date, punch_state, verify_type, is_processed, created_at, updated_at)
SELECT @jho, '12', p.t, DATE(p.t), p.s, 0, 0, NOW(), NOW() FROM (
  SELECT '2026-10-12 07:48:49' AS t, 1 AS s
  UNION ALL SELECT '2026-10-12 07:49:00', 0
  UNION ALL SELECT '2026-10-12 12:01:45', 1
  UNION ALL SELECT '2026-10-12 12:02:43', 1
  UNION ALL SELECT '2026-10-12 12:04:00', 0
  UNION ALL SELECT '2026-10-12 17:03:42', 1
  UNION ALL SELECT '2026-10-13 07:38:02', 1
  UNION ALL SELECT '2026-10-13 12:02:16', 1
  UNION ALL SELECT '2026-10-13 12:12:00', 0
  UNION ALL SELECT '2026-10-13 17:03:53', 1
  UNION ALL SELECT '2026-10-13 17:04:00', 1
  UNION ALL SELECT '2026-10-14 07:54:00', 0
  UNION ALL SELECT '2026-10-14 12:03:00', 1
  UNION ALL SELECT '2026-10-14 12:05:00', 0
  UNION ALL SELECT '2026-10-14 17:03:00', 1
  UNION ALL SELECT '2026-10-15 07:38:00', 0
  UNION ALL SELECT '2026-10-15 12:01:00', 1
  UNION ALL SELECT '2026-10-15 12:10:00', 0
  UNION ALL SELECT '2026-10-15 17:02:00', 1
  UNION ALL SELECT '2026-10-16 07:53:00', 0
  UNION ALL SELECT '2026-10-16 12:01:00', 1
  UNION ALL SELECT '2026-10-16 12:04:00', 0
  UNION ALL SELECT '2026-10-16 17:04:00', 1
  UNION ALL SELECT '2026-10-19 07:59:00', 0
  UNION ALL SELECT '2026-10-19 12:02:00', 1
  UNION ALL SELECT '2026-10-19 12:04:00', 0
  UNION ALL SELECT '2026-10-19 17:04:00', 1
  UNION ALL SELECT '2026-10-20 07:54:00', 0
  UNION ALL SELECT '2026-10-20 12:02:00', 1
  UNION ALL SELECT '2026-10-20 12:04:00', 0
  UNION ALL SELECT '2026-10-20 17:04:00', 1
  UNION ALL SELECT '2026-10-21 07:52:00', 0
  UNION ALL SELECT '2026-10-21 12:02:00', 1
  UNION ALL SELECT '2026-10-21 12:04:00', 0
  UNION ALL SELECT '2026-10-21 17:03:00', 1
  UNION ALL SELECT '2026-10-23 07:47:00', 0
  UNION ALL SELECT '2026-10-23 12:01:00', 1
  UNION ALL SELECT '2026-10-23 12:03:00', 0
  UNION ALL SELECT '2026-10-23 17:06:00', 1
) p
WHERE @jho IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM attendance_logs a WHERE a.emp_code = '12' AND a.punch_time >= DATE_FORMAT(p.t, '%Y-%m-%d %H:%i:00') AND a.punch_time < DATE_FORMAT(p.t, '%Y-%m-%d %H:%i:00') + INTERVAL 1 MINUTE
);
-- Marjo: 37 punches
INSERT INTO attendance_logs (employee_id, emp_code, punch_time, punch_date, punch_state, verify_type, is_processed, created_at, updated_at)
SELECT @mar, '15', p.t, DATE(p.t), p.s, 0, 0, NOW(), NOW() FROM (
  SELECT '2026-10-13 07:58:19' AS t, 1 AS s
  UNION ALL SELECT '2026-10-13 12:00:59', 1
  UNION ALL SELECT '2026-10-13 12:01:03', 1
  UNION ALL SELECT '2026-10-13 12:03:28', 1
  UNION ALL SELECT '2026-10-13 17:01:38', 1
  UNION ALL SELECT '2026-10-14 08:11:00', 0
  UNION ALL SELECT '2026-10-14 12:01:00', 1
  UNION ALL SELECT '2026-10-14 12:03:00', 0
  UNION ALL SELECT '2026-10-14 17:01:00', 1
  UNION ALL SELECT '2026-10-15 07:25:00', 0
  UNION ALL SELECT '2026-10-15 12:01:00', 1
  UNION ALL SELECT '2026-10-15 12:03:00', 0
  UNION ALL SELECT '2026-10-15 17:01:00', 1
  UNION ALL SELECT '2026-10-16 08:07:00', 0
  UNION ALL SELECT '2026-10-16 12:01:00', 1
  UNION ALL SELECT '2026-10-16 12:03:00', 0
  UNION ALL SELECT '2026-10-16 17:12:00', 1
  UNION ALL SELECT '2026-10-20 08:16:00', 0
  UNION ALL SELECT '2026-10-20 12:01:00', 1
  UNION ALL SELECT '2026-10-20 12:03:00', 0
  UNION ALL SELECT '2026-10-20 17:02:00', 1
  UNION ALL SELECT '2026-10-21 07:39:00', 0
  UNION ALL SELECT '2026-10-21 12:01:00', 1
  UNION ALL SELECT '2026-10-21 12:03:00', 0
  UNION ALL SELECT '2026-10-21 17:02:00', 1
  UNION ALL SELECT '2026-10-22 07:44:00', 0
  UNION ALL SELECT '2026-10-22 12:01:00', 1
  UNION ALL SELECT '2026-10-22 12:03:00', 0
  UNION ALL SELECT '2026-10-22 17:02:00', 1
  UNION ALL SELECT '2026-10-23 07:24:00', 0
  UNION ALL SELECT '2026-10-23 12:00:00', 1
  UNION ALL SELECT '2026-10-23 12:02:00', 0
  UNION ALL SELECT '2026-10-23 17:02:00', 1
  UNION ALL SELECT '2026-10-24 07:39:00', 0
  UNION ALL SELECT '2026-10-24 12:00:00', 1
  UNION ALL SELECT '2026-10-24 12:02:00', 0
  UNION ALL SELECT '2026-10-24 17:02:00', 1
) p
WHERE @mar IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM attendance_logs a WHERE a.emp_code = '15' AND a.punch_time >= DATE_FORMAT(p.t, '%Y-%m-%d %H:%i:00') AND a.punch_time < DATE_FORMAT(p.t, '%Y-%m-%d %H:%i:00') + INTERVAL 1 MINUTE
);

COMMIT;
