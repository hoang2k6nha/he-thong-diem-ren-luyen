import os
import glob
import re

files = glob.glob('*.php')
for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # We want to replace `.badge { ... }` to ensure it has `display: inline-block; text-align: center; white-space: nowrap;`
    # Let's find `.badge {` lines
    lines = content.split('\n')
    new_lines = []
    changed = False
    for line in lines:
        if '.badge {' in line:
            # Check if it already has display: inline-block
            new_line = line
            if 'display:' not in new_line:
                new_line = new_line.replace('.badge {', '.badge { display: inline-block; white-space: nowrap; text-align: center;')
                changed = True
            elif 'inline-block' in new_line and 'white-space: nowrap' not in new_line:
                new_line = new_line.replace('display: inline-block;', 'display: inline-block; white-space: nowrap; text-align: center;')
                changed = True
            
            new_lines.append(new_line)
        else:
            new_lines.append(line)
            
    if changed:
        with open(f, 'w', encoding='utf-8') as file:
            file.write('\n'.join(new_lines))
        print(f'Patched badge in {f}')
