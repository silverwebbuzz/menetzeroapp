#!/usr/bin/env python3
"""
MENetZero 2.0 — pre-flight checks for theme views.

Run before reporting any redesign phase complete. Balance checks alone are
not sufficient for Blade: an undefined variable is silently null in a
boolean test but fatal in a strict comparison (see redesign.md sections 20-21).

Checks:
  1. Undefined variables
  2. mnz- classes used but not defined in mnz-ui.css
  3. Blade directive balance
"""
import re, sys, glob, os

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Variables shared by composers / Laravel, not defined in the view itself.
SHARED = {
    'activeTheme','isNewTheme','themeAssets','gate','companyRenewalNudge',
    'errors','loop','slot','attributes','__env','app','token','email',
    'portalVariant','showRenewalNav',
    # Controller payload variables are derived at run time in main(); see
    # controller_vars there. Only framework/composer-supplied names belong here.
}

def defined_vars(body: str) -> set:
    d = set(SHARED)
    for blk in re.findall(r'@php(.*?)@endphp', body, re.S):
        d |= set(re.findall(r'\$([a-zA-Z_]\w*)\s*=', blk))
        d |= set(re.findall(r'fn\s*\(([^)]*)\)', blk) and
                 re.findall(r'\$([a-zA-Z_]\w*)', ' '.join(re.findall(r'fn\s*\(([^)]*)\)', blk))) or [])
        d |= set(re.findall(r'function\s*\(([^)]*)\)', blk) and
                 re.findall(r'\$([a-zA-Z_]\w*)', ' '.join(re.findall(r'function\s*\(([^)]*)\)', blk))) or [])
        d |= set(re.findall(r'use\s*\(([^)]*)\)', blk) and
                 re.findall(r'\$([a-zA-Z_]\w*)', ' '.join(re.findall(r'use\s*\(([^)]*)\)', blk))) or [])
    for blk in re.findall(r'@php\((.*?)\)', body):
        d |= set(re.findall(r'\$([a-zA-Z_]\w*)\s*=', blk))
    # @foreach(... as $k => $v) and @foreach(... as $v) — collection may be any expression
    for k, v in re.findall(r'@for(?:each|else)\s*\(.*?\bas\s+\$([a-zA-Z_]\w*)\s*=>\s*\$([a-zA-Z_]\w*)', body):
        d |= {k, v}
    d |= set(re.findall(r'@for(?:each|else)\s*\(.*?\bas\s+\$([a-zA-Z_]\w*)\s*\)', body))
    d |= set(re.findall(r'=>\s*\$([a-zA-Z_]\w*)\)', body))
    return d

def main() -> int:
    css = set(re.findall(r'\.(mnz-[a-zA-Z0-9_-]+)',
                         open(os.path.join(ROOT, 'public/css/mnz-ui.css')).read()))
    files = sorted(glob.glob(os.path.join(ROOT, 'resources/views/themes/**/*.blade.php'),
                             recursive=True))

    # Variables any controller passes to any view. Derived from the source so
    # this list cannot drift as controllers change — a hand-maintained list
    # would go stale and start reporting false failures.
    controller_vars = set()
    for f in glob.glob(os.path.join(ROOT, 'app/Http/Controllers/**/*.php'), recursive=True):
        src = open(f).read()
        controller_vars |= set(re.findall(r"'([a-zA-Z_]\w*)'\s*=>", src))
        for c in re.findall(r"compact\(([^)]*)\)", src, re.S):
            controller_vars |= set(re.findall(r"'([a-zA-Z_]\w*)'", c))
    SHARED.update(controller_vars)

    # Classes defined inline by any shell layout — available to wrapped views.
    layout_css = [set(re.findall(r'\.(mnz-[a-zA-Z0-9_-]+)', open(f).read()))
                  for f in files if '/layouts/' in f]

    failures = 0
    for f in files:
        rel = f.split('resources/views/')[1]
        src = open(f).read()
        body = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)  # strip comments
        problems = []

        undef = sorted(set(re.findall(r'\$([a-zA-Z_]\w*)', body)) - defined_vars(body))
        if undef:
            problems.append(f'undefined: {undef}')

        # A shell may define its own classes in an inline <style>, and a
        # layout's classes are available to the views it wraps. Collect both.
        local = set(re.findall(r'\.(mnz-[a-zA-Z0-9_-]+)', src))
        for layout in layout_css:
            local |= layout
        used = {c for m in re.findall(r'class="([^"{}]*)"', body)
                for c in m.split() if c.startswith('mnz-')}
        missing = sorted(used - css - local - {'mnz-theme'})
        if missing:
            problems.append(f'no CSS rule: {missing}')

        for o, c in [('@if', '@endif'), ('@foreach', '@endforeach'),
                     ('@section', '@endsection'), ('@push', '@endpush'),
                     ('@auth', '@endauth')]:
            a = len(re.findall(re.escape(o) + r'\b', body))
            if o == '@section':
                a -= len(re.findall(r'@section\([^)]*,[^)]*\)', body))
            if o == '@if':
                a += len(re.findall(r'@hasSection\b', body))
            if a != len(re.findall(re.escape(c) + r'\b', body)):
                problems.append(f'unbalanced {o}')
        a = len(re.findall(r'@php\b', body)) - len(re.findall(r'@php\(', body))
        if a != len(re.findall(r'@endphp\b', body)):
            problems.append('unbalanced @php')

        # A themed view must not silently drop form fields, x-field-help
        # guidance, or route references present in the view it overrides.
        # Missing helps are invisible in a screenshot but lose real guidance.
        orig = os.path.join(ROOT, 'resources/views', rel.replace('themes/new/', ''))
        if 'themes/new/' in rel and os.path.exists(orig):
            osrc = open(orig).read()
            # <meta name="…"> is not a form field.
            src_forms = re.sub(r'<meta\b[^>]*>', '', src)
            osrc_forms = re.sub(r'<meta\b[^>]*>', '', osrc)
            for what, pattern in [('form field', r'name="([^"]+)"'),
                                  ('field-help', r'x-field-help key="([^"]+)"'),
                                  ('route', r"route\(\s*'([a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)+)'")]:
                a, b = (osrc_forms, src_forms) if what == 'form field' else (osrc, src)
                lost = sorted(set(re.findall(pattern, a)) - set(re.findall(pattern, b)))
                # A shell's links legitimately live in its nav partial.
                if what == 'route' and '/layouts/' in rel:
                    navs = ' '.join(open(n).read() for n in files if '/partials/nav' in n)
                    lost = [x for x in lost if x not in navs]
                # A view may declare an intentional omission by naming it after
                # "DELIBERATE OMISSION" in its header comment.
                if 'DELIBERATE OMISSION' in src:
                    lost = [x for x in lost if x not in b]
                if lost:
                    problems.append(f'{what} dropped vs original: {lost}')

        if problems:
            failures += 1
            print(f'  FAIL {rel}')
            for p in problems:
                print(f'         {p}')
        else:
            print(f'  ok   {rel}')

    print(f'\n{len(files)} files, {failures} with problems')
    return 1 if failures else 0

if __name__ == '__main__':
    sys.exit(main())
