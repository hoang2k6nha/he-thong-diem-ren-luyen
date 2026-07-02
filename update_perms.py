import os
import re

files_perms = {
    'admin_cycles.php': ('admin', 'manage_cycles'),
    'admin_criteria.php': ('admin', 'manage_criteria'),
    'admin_accounts.php': ('admin', 'manage_accounts'),
    'admin_permissions.php': ('admin', 'manage_permissions'),
    'admin_assignments.php': ('admin', 'manage_assignments'),
    'advisor_manage_class.php': ('cvht', 'advisor_manage_class'),
    'advisor_classes.php': ('cvht', 'grade_advisor'),
    'advisor_reports.php': ('cvht', 'view_reports_advisor'),
    'advisor_complaints.php': ('cvht', 'advisor_complaints'),
    'department_classes.php': ('khoa', 'grade_department'),
    'department_reports.php': ('khoa', 'view_reports_department'),
    'department_complaints.php': ('khoa', 'department_complaints'),
    'student_history.php': ('sinh_vien', 'student_history'),
    'student_complaints.php': ('sinh_vien', 'student_complaints')
}

for f, (role, perm) in files_perms.items():
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # regex replace
    pattern1 = re.compile(rf"if \(!isset\(\$_SESSION\['user_id'\]\) \|\| \$_SESSION\['role'\] !== '{role}'\) {{\s+redirect\('index\.php'\);\s*}}")
    repl1 = f"if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== '{role}' && !has_permission('{perm}'))) {{\n    redirect('index.php');\n}}"
    
    pattern2 = re.compile(rf"if \(!isset\(\$_SESSION\['user_id'\]\) \|\| \$_SESSION\['role'\] !== '{role}'\) redirect\('index\.php'\);")
    repl2 = f"if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== '{role}' && !has_permission('{perm}'))) redirect('index.php');"

    new_content = pattern1.sub(repl1, content)
    new_content = pattern2.sub(repl2, new_content)
    
    if content != new_content:
        with open(f, 'w', encoding='utf-8') as file:
            file.write(new_content)
        print(f'Updated {f}')
    else:
        print(f'Failed to update {f}')
