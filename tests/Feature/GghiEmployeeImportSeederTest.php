<?php

use App\Models\Department;
use App\Models\Employee;
use Database\Seeders\GghiEmployeeImportSeeder;

describe('GghiEmployeeImportSeeder', function () {
    it('does not crash when a department already exists under a different name but the same code', function () {
        // Reproduces the Hostinger bug: "Information Technology" / code "IT"
        // collided with a pre-existing "IT Department" row using that same code.
        $existing = Department::create(['name' => 'IT Department', 'code' => 'IT', 'is_active' => true]);

        (new GghiEmployeeImportSeeder())->run();

        // No duplicate "Information Technology" row was created.
        expect(Department::where('code', 'IT')->count())->toBe(1)
            ->and(Department::where('name', 'IT Department')->exists())->toBeTrue()
            ->and(Department::where('name', 'Information Technology')->exists())->toBeFalse();

        // IT employees from the roster resolved to the pre-existing department.
        $itEmployee = Employee::where('position', 'It Head')->first();
        expect($itEmployee)->not->toBeNull()
            ->and($itEmployee->department_id)->toBe($existing->id);
    });

    it('is idempotent — running it twice does not duplicate departments or employees', function () {
        (new GghiEmployeeImportSeeder())->run();
        $deptCount = Department::count();
        $empCount  = Employee::count();

        (new GghiEmployeeImportSeeder())->run();

        expect(Department::count())->toBe($deptCount)
            ->and(Employee::count())->toBe($empCount);
    });

    it('creates all expected departments from a clean database', function () {
        (new GghiEmployeeImportSeeder())->run();

        foreach ([
            'Information Technology', 'Nursing', 'Laboratory', 'Pharmacy',
            'Community Pharmacy', 'Dietary', 'Hemodialysis', 'Radiology',
            'Human Resources', 'Administration', 'Accounting', 'Procurement',
            'Housekeeping Department',
        ] as $name) {
            expect(Department::where('name', $name)->exists())->toBeTrue("Missing department: {$name}");
        }

        expect(Employee::count())->toBe(199);
    });
});
