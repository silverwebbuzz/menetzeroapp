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
    # Alpine.js magic, used inside @click/@submit/@change JS strings. It is
    # never a Blade variable, so it can never be "undefined" in Blade terms.
    'event',
    # Controller payload variables are derived at run time in main(); see
    # controller_vars there. Only framework/composer-supplied names belong here.
}

def defined_vars(body: str) -> set:
    d = set(SHARED)
    for blk in re.findall(r'@php(.*?)@endphp', body, re.S):
        d |= set(re.findall(r'\$([a-zA-Z_]\w*)\s*=', blk))
        # PHP out-parameters: preg_match($re, $subj, $matches) DEFINES $matches.
        d |= set(re.findall(r'preg_match(?:_all)?\s*\([^;]*?,\s*\$([a-zA-Z_]\w*)\s*\)', blk))
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

    # Shared partials created by the migration live OUTSIDE themes/ because both
    # themes include them. They were therefore never scanned — which is how the
    # unbalanced @if in quick-input/partials/entry-form reached production
    # (redesign.md section 31.8). Directive balance is checked on these too; the
    # variable and CSS checks are skipped for them, since a partial legitimately
    # relies on variables its parent defines.
    shared_partials = sorted(
        f for f in glob.glob(os.path.join(ROOT, 'resources/views/**/partials/*.blade.php'),
                             recursive=True)
        if '/themes/' not in f
        and os.path.basename(f) in {
            'entry-form.blade.php', 'source-icon.blade.php',
            'index-scripts.blade.php', 'enterprise-scripts.blade.php',
        })

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
    for f in shared_partials:
        rel = f.split('resources/views/')[1]
        src = open(f).read()
        probs = []
        P = {'@if': '@endif', '@foreach': '@endforeach', '@php': '@endphp',
             '@forelse': '@endforelse', '@error': '@enderror', '@push': '@endpush',
             '@while': '@endwhile', '@isset': '@endisset', '@unless': '@endunless',
             '@for': '@endfor', '@switch': '@endswitch'}
        for o, c in P.items():
            no = len(re.findall(re.escape(o) + r'\b', src))
            nc = len(re.findall(re.escape(c) + r'\b', src))
            if o == '@php':
                no -= len(re.findall(r'@php\s*\(', src))
            if o == '@if':
                no += len(re.findall(r'@(?:hasSection|sectionMissing)\b', src))
            if no != nc:
                probs.append(f'directive imbalance: {no} {o} vs {nc} {c}')
        if probs:
            failures += 1
            print(f'  FAIL {rel}  (shared partial)')
            for x in probs:
                print(f'         {x}')
        else:
            print(f'  ok   {rel}  (shared partial)')

    for f in files:
        rel = f.split('resources/views/')[1]
        src = open(f).read()
        body = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)  # strip comments
        problems = []

        # Directive balance, counted the way BLADE counts it: on the raw source,
        # NOT on the comment-stripped body. Blade compiles directives before it
        # strips {{-- --}}, so a directive NAME written inside a comment is still
        # counted by the compiler and silently unbalances the file. That is what
        # produced the ParseError in quick-input/partials/entry-form (redesign.md
        # section 31.8), which a comment-stripped balance check had reported clean.
        PAIRS = {'@if': '@endif', '@foreach': '@endforeach', '@php': '@endphp',
                 '@forelse': '@endforelse', '@error': '@enderror',
                 '@push': '@endpush', '@while': '@endwhile', '@isset': '@endisset',
                 '@unless': '@endunless', '@for': '@endfor', '@switch': '@endswitch'}
        for o, c in PAIRS.items():
            no = len(re.findall(re.escape(o) + r'\b', src))
            nc = len(re.findall(re.escape(c) + r'\b', src))
            if o == '@php':
                # @php($x = 1) is the single-line form and takes no @endphp.
                no -= len(re.findall(r'@php\s*\(', src))
            if o == '@if':
                # @hasSection / @sectionMissing also close with @endif.
                no += len(re.findall(r'@(?:hasSection|sectionMissing)\b', src))
            if no != nc:
                problems.append(f'directive imbalance: {no} {o} vs {nc} {c}'
                                ' (Blade counts directive names inside comments too)')

        undef = sorted(set(re.findall(r'\$([a-zA-Z_]\w*)', body)) - defined_vars(body))
        # A name reached ONLY through ?? / ?-> / isset() / empty() cannot cause a
        # fatal: PHP suppresses the notice and the fallback wins. Some such names
        # are pre-existing dead code in the original view (e.g. $industryLabel in
        # quick-input/show, built in the controller but absent from its compact()).
        # Flagging them would train us to ignore this check, so only report a name
        # that is dereferenced somewhere UNGUARDED.
        if undef:
            guarded = []
            for name in undef:
                esc = re.escape(name)
                # Blank out everything inside an @if(isset($name)...)/@endif block,
                # so uses protected by an enclosing guard are not counted.
                scan, depth, out = body, 0, []
                for line in scan.split('\n'):
                    opens = re.search(r'@if\s*\(.*(?:isset|empty)\s*\(\s*\$' + esc, line)
                    if depth:
                        if re.search(r'@if\b', line):
                            depth += 1
                        if re.search(r'@endif\b', line):
                            depth -= 1
                        continue
                    if opens:
                        depth = 1
                        continue
                    out.append(line)
                remaining = '\n'.join(out)
                uses = re.findall(r'[^\n]*\$' + esc + r'\b[^\n]*', remaining)
                if uses and all(('??' in u) or ('?->' in u) or
                                re.search(r'(isset|empty)\s*\(\s*\$' + esc, u)
                                for u in uses):
                    guarded.append(name)
            undef = [n for n in undef if n not in guarded]
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
