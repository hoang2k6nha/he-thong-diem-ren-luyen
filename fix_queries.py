import os

patches = {
    'advisor_classes.php': {
        "(pc.cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "pc.cvht_id = $cvht_id") . "'
    },
    'advisor_manage_class.php': {
        "(l.cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "l.cvht_id = $cvht_id") . "'
    },
    'advisor_reports.php': {
        "(cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "cvht_id = $cvht_id") . "'
    },
    'advisor_student_list.php': {
        "(cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "cvht_id = $cvht_id") . "'
    },
    'advisor_review.php': {
        "(cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "cvht_id = $cvht_id") . "'
    },
    'advisor_grading.php': {
        "(pc.cvht_id = $cvht_id OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "pc.cvht_id = $cvht_id") . "'
    },
    'advisor_complaints.php': {
        "(l.cvht_id = $uid OR $_SESSION['role'] !== 'cvht')": '" . ($_SESSION["role"] !== "cvht" ? "1=1" : "l.cvht_id = $uid") . "'
    },
    'department_classes.php': {
        "(l.khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')": '" . ($_SESSION["role"] !== "khoa" ? "1=1" : "l.khoa_id = $khoa_id") . "'
    },
    'department_reports.php': {
        "(l.khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')": '" . ($_SESSION["role"] !== "khoa" ? "1=1" : "l.khoa_id = $khoa_id") . "',
        "(khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')": '" . ($_SESSION["role"] !== "khoa" ? "1=1" : "khoa_id = $khoa_id") . "'
    },
    'department_review.php': {
        "(khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')": '" . ($_SESSION["role"] !== "khoa" ? "1=1" : "khoa_id = $khoa_id") . "'
    },
    'department_grading.php': {
        "(l.khoa_id = $khoa_id OR $_SESSION['role'] !== 'khoa')": '" . ($_SESSION["role"] !== "khoa" ? "1=1" : "l.khoa_id = $khoa_id") . "'
    },
    'department_complaints.php': {
        "(l.khoa_id = $khoa_id OR k.sinh_vien_id IS NULL OR $_SESSION['role'] !== 'khoa')": '" . ($_SESSION["role"] !== "khoa" ? "1=1" : "(l.khoa_id = $khoa_id OR k.sinh_vien_id IS NULL)") . "'
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
            
    if content != new_content:
        with open(f, 'w', encoding='utf-8') as file:
            file.write(new_content)

print("Fix complete.")
