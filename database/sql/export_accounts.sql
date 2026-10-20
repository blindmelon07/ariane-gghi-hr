-- List every login account except super admins, for exporting to Excel.
-- "Password" shows gghi2021 only when the account still has the exact hash set by
-- create_missing_accounts.sql; anything else was changed or set from the app.

SELECT u.employee_code                                   AS `Username (Emp Code)`,
       u.name                                            AS `Name`,
       COALESCE(d.name, e.department)                    AS `Department`,
       COALESCE(p.name, e.position)                      AS `Position`,
       u.role                                            AS `Role`,
       IF(u.is_active = 1, 'Active', 'Inactive')         AS `Status`,
       IF(u.password = '$2y$12$bhhCbqUzcLgS8qp/HYsbjONswsniOToXHnpzBVMBk3l4eYpsbPnha',
          'gghi2021', 'Changed / set separately')        AS `Password`
FROM users u
LEFT JOIN employees e   ON e.user_id = u.id
LEFT JOIN departments d ON d.id = e.department_id
LEFT JOIN positions p   ON p.id = e.position_id
WHERE u.role <> 'super_admin'
ORDER BY `Department`, u.name;
