import os
import re

# 1. Update sub-page headers
sub_pages = {
    'advisor_student_list.php': ('cvht', 'advisor_manage_class'),
    'advisor_review.php': ('cvht', 'grade_advisor'),
    'advisor_grading.php': ('cvht', 'grade_advisor'),
    'department_review.php': ('khoa', 'grade_department'),
    'department_grading.php': ('khoa', 'grade_department'),
}

for f, (role, perm) in sub_pages.items():
    if not os.path.exists(f): continue
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    pattern1 = re.compile(rf"if \(!isset\(\$_SESSION\['user_id'\]\) \|\| \$_SESSION\['role'\] !== '{role}'\) {{\s+redirect\('index\.php'\);\s*}}")
    repl1 = f"if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== '{role}' && !has_permission('{perm}'))) {{\n    redirect('index.php');\n}}"
    
    pattern2 = re.compile(rf"if \(!isset\(\$_SESSION\['user_id'\]\) \|\| \$_SESSION\['role'\] !== '{role}'\) redirect\('index\.php'\);")
    repl2 = f"if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== '{role}' && !has_permission('{perm}'))) redirect('index.php');"

    new_content = pattern1.sub(repl1, content)
    new_content = pattern2.sub(repl2, new_content)
    
    if content != new_content:
        with open(f, 'w', encoding='utf-8') as file:
            file.write(new_content)
        print(f'Updated header for {f}')

# 2. Patch SQL Queries
patches = {
    'advisor_classes.php': {
        "pc.cvht_id = $cvht_id AND": "(pc.cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht') AND"
    },
    'advisor_manage_class.php': {
        "WHERE l.cvht_id = $cvht_id": "WHERE (l.cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')"
    },
    'advisor_reports.php': {
        "WHERE cvht_id = $cvht_id": "WHERE (cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')"
    },
    'advisor_student_list.php': {
        "AND cvht_id = $cvht_id": "AND (cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')"
    },
    'advisor_review.php': {
        "AND cvht_id = $cvht_id": "AND (cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')"
    },
    'advisor_grading.php': {
        "AND pc.cvht_id = $cvht_id": "AND (pc.cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')"
    },
    'advisor_complaints.php': {
        "l.cvht_id = $uid": "(l.cvht_id = $uid OR $_SESSION['role'] !== 'cvht')"
    },
    'department_classes.php': {
        "WHERE l.khoa_id = $khoa_id": "WHERE (l.khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')"
    },
    'department_reports.php': {
        "WHERE khoa_id = $khoa_id": "WHERE (khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')",
        "WHERE l.khoa_id = $khoa_id AND t.vai_tro = 'sinh_vien'": "WHERE (l.khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa') AND t.vai_tro = 'sinh_vien'"
    },
    'department_review.php': {
        "AND khoa_id = $khoa_id": "AND (khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')"
    },
    'department_grading.php': {
        "AND l.khoa_id = $khoa_id": "AND (l.khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')"
    },
    'department_complaints.php': {
        "(l.khoa_id = $khoa_id OR k.sinh_vien_id IS NULL)": "(l.khoa_id = $khoa_id OR k.sinh_vien_id IS NULL OR $_SESSION['role'] !== 'khoa')"
    }
}

for f, replacements in patches.items():
    if not os.path.exists(f): continue
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    new_content = content
    for target, repl in replacements.items():
        if target in new_content:
            new_content = new_content.replace(target, repl)
            print(f'Patched query in {f}')
        else:
            # Maybe already patched
            pass
            
    if content != new_content:
        with open(f, 'w', encoding='utf-8') as file:
            file.write(new_content)

print("Patching complete.")
