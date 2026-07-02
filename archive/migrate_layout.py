import os
import re

LAYOUT_HEADER = "layout_header.php"
LAYOUT_FOOTER = "layout_footer.php"

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Skip files that don't have <!DOCTYPE html> or already migrated
    if '<!DOCTYPE html>' not in content or 'layout_header.php' in content:
        return

    # Extract PHP logic (everything before <!DOCTYPE html>)
    php_match = re.match(r'(.*?)<!DOCTYPE html>', content, re.DOTALL)
    if not php_match:
        return
    php_logic = php_match.group(1).strip()
    
    # Remove the trailing ?> from php_logic if it exists
    if php_logic.endswith('?>'):
        php_logic = php_logic[:-2].strip()

    # Extract Title
    title_match = re.search(r'<title>(.*?)</title>', content)
    title = title_match.group(1) if title_match else "Dashboard"

    # Extract Container Content
    # find <div class="container"> and everything inside it, but careful with nested divs.
    # A simpler way is to find everything after `<div class="container">` and before `<script>` or `</body>`
    
    # Let's match from `<div class="container">` to the end of the file
    container_start = content.find('<div class="container">')
    if container_start == -1:
        return
        
    container_start += len('<div class="container">')
    
    # Extract scripts if any
    script_content = ""
    script_match = re.search(r'(<script>.*?</script>)', content[container_start:], re.DOTALL)
    if script_match:
        script_content = script_match.group(1)
        # remove script from content to get just HTML
        html_content = content[container_start:container_start + script_match.start()]
    else:
        # find </div>\n</body> or similar
        body_end = content.find('</body>', container_start)
        html_content = content[container_start:body_end]
        # remove the last </div> which belongs to container
        last_div = html_content.rfind('</div>')
        if last_div != -1:
            html_content = html_content[:last_div]

    # Clean up html_content leading/trailing spaces
    html_content = html_content.strip()

    # Assemble new file content
    new_content = f"{php_logic}\n\n"
    new_content += f"$page_title = '{title}';\n"
    new_content += f"require_once 'layout_header.php';\n"
    new_content += f"?>\n\n"
    new_content += html_content + "\n\n"
    if script_content:
        new_content += script_content + "\n\n"
    new_content += f"<?php require_once 'layout_footer.php'; ?>\n"

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f"Migrated {filepath}")

def main():
    skip_files = ['index.php', 'config.php', 'layout_header.php', 'layout_footer.php']
    for filename in os.listdir('.'):
        if filename.endswith('.php') and filename not in skip_files:
            process_file(filename)

if __name__ == '__main__':
    main()
