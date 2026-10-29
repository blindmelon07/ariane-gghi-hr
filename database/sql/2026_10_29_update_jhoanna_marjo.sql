-- Hourly-rate fix for Jhoanna Aycardo (emp_code 12) and Marjo Cortez (emp_code 15)
--
-- HR computes late/absence deductions with hourly = monthly x 12 / 365 / 8
-- (e.g. Marjo, Oct 11-25: 0.57 hrs x 68.46 = 39.02 on the HR payslip).
-- Live used daily / 8 instead (Marjo: 80.09 -> 44.05 for 33 min late).
--
-- Only the hourly rate changes. Deductions, allowances, rest days and punches
-- on live already match the HR payslip and are left alone.
-- Each update runs only if the employee's code, name and monthly rate match.
-- Safe to run more than once.

START TRANSACTION;

-- Before: check both rows show up with the expected monthly rate.
SELECT e.emp_code, e.full_name, s.basic_salary, s.daily_rate, s.hourly_rate
FROM employees e JOIN salary_details s ON s.employee_id = e.id
WHERE (e.emp_code = '12' AND e.first_name = 'Jhoanna' AND e.last_name = 'Aycardo')
   OR (e.emp_code = '15' AND e.first_name = 'Marjo'   AND e.last_name = 'Cortez');

UPDATE salary_details s JOIN employees e ON e.id = s.employee_id
SET s.hourly_rate = 77.12, s.updated_at = NOW()
WHERE e.emp_code = '12' AND e.first_name = 'Jhoanna' AND e.last_name = 'Aycardo' AND s.basic_salary = 18765.00;

UPDATE salary_details s JOIN employees e ON e.id = s.employee_id
SET s.hourly_rate = 68.46, s.updated_at = NOW()
WHERE e.emp_code = '15' AND e.first_name = 'Marjo' AND e.last_name = 'Cortez' AND s.basic_salary = 16658.00;

-- After: hourly_rate should be 77.12 (Jhoanna) and 68.46 (Marjo).
SELECT e.emp_code, e.full_name, s.basic_salary, s.daily_rate, s.hourly_rate
FROM employees e JOIN salary_details s ON s.employee_id = e.id
WHERE (e.emp_code = '12' AND e.first_name = 'Jhoanna' AND e.last_name = 'Aycardo')
   OR (e.emp_code = '15' AND e.first_name = 'Marjo'   AND e.last_name = 'Cortez');

COMMIT;
