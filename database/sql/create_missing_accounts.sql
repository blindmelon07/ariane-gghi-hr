-- Create a login for every employee that has no account yet.
-- Username = emp_code, password = gghi2021, role = employee.
-- Safe to run more than once: it only touches employees/users that are missing something.

START TRANSACTION;

-- 1. Create a user for every unlinked employee whose emp_code isn't already a user
INSERT INTO users (name, employee_code, password, role, is_active, created_at, updated_at)
SELECT CONCAT(e.first_name, ' ', e.last_name),
       e.emp_code,
       '$2y$12$bhhCbqUzcLgS8qp/HYsbjONswsniOToXHnpzBVMBk3l4eYpsbPnha',
       'employee',
       1,
       NOW(),
       NOW()
FROM employees e
LEFT JOIN users u ON u.employee_code = e.emp_code
WHERE e.user_id IS NULL
  AND u.id IS NULL;

-- 2. Link every unlinked employee to the user with the same code
UPDATE employees e
JOIN users u ON u.employee_code = e.emp_code
SET e.user_id = u.id
WHERE e.user_id IS NULL;

-- 3. Give the Spatie 'employee' role to users who have no role yet
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM users u
JOIN roles r ON r.name = 'employee' AND r.guard_name = 'web'
LEFT JOIN model_has_roles mhr
       ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
WHERE mhr.model_id IS NULL;

COMMIT;
