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


BLADE_OPEN = {'@if': '@endif', '@foreach': '@endforeach', '@php': '@endphp',
              '@forelse': '@endforelse', '@error': '@enderror', '@while': '@endwhile',
              '@isset': '@endisset', '@unless': '@endunless', '@for': '@endfor',
              '@switch': '@endswitch', '@push': '@endpush', '@section': '@endsection',
              '@once': '@endonce', '@verbatim': '@endverbatim', '@auth': '@endauth',
              '@guest': '@endguest', '@hasSection': '@endif', '@sectionMissing': '@endif',
              '@prepend': '@endprepend', '@can': '@endcan', '@cannot': '@endcannot'}
BLADE_CLOSE = {}
for _o, _c in BLADE_OPEN.items():
    BLADE_CLOSE.setdefault(_c, set()).add(_o)
BLADE_BRANCH = {'@else', '@elseif', '@elsecan', '@elseauth', '@elseguest'}
BLADE_BRANCHABLE = ('@if', '@unless', '@isset', '@hasSection', '@sectionMissing',
                    '@can', '@cannot', '@auth', '@guest')


def blade_structure_errors(src):
    """Walk Blade directives with a stack; report structural breakage."""
    stack, errs = [], []
    for lineno, line in enumerate(src.split('\n'), 1):
        for t in re.findall(r'@[a-zA-Z]+', line):
            # Single-line forms that take no closer.
            if t == '@php' and re.search(r'@php\s*\(', line):
                continue
            if t == '@section' and re.search(r'@section\s*\([^)]*,', line):
                continue
            # @empty is a @forelse branch, not a block, in that context.
            if t == '@empty' and stack and stack[-1][0] == '@forelse':
                continue
            if t in BLADE_OPEN:
                stack.append((t, lineno))
            elif t in BLADE_CLOSE:
                if stack and stack[-1][0] in BLADE_CLOSE[t]:
                    stack.pop()
                else:
                    inner = f'{stack[-1][0]} (line {stack[-1][1]})' if stack else 'NOTHING'
                    errs.append(f'line {lineno}: {t} but innermost open is {inner}')
            elif t in BLADE_BRANCH:
                if not stack or stack[-1][0] not in BLADE_BRANCHABLE:
                    inner = f'{stack[-1][0]} (line {stack[-1][1]})' if stack else 'NOTHING'
                    errs.append(f'line {lineno}: {t} with innermost open = {inner}')
    for t, lineno in stack:
        if t != '@section':
            errs.append(f'unclosed {t} opened at line {lineno}')
    return errs



def blade_include_errors(src, known_views):
    """@include targets that cannot resolve -> 'View not found' at runtime (500).

    Dynamic names (concatenated with a variable) are skipped: they cannot be
    resolved statically and are legitimate, e.g. emission-form/step.blade.php.
    """
    errs = []
    for m in re.finditer(r"@(?:include|includeIf|includeWhen|includeFirst|each)\s*\(\s*'([^']+)'(\s*\.)?", src):
        name, concat = m.group(1), m.group(2)
        if concat:
            continue
        if name.split('::')[-1] not in known_views:
            errs.append(f"@include target does not exist: '{name}'")
    return errs


def blade_php_brace_errors(src):
    """Unbalanced braces inside @php blocks -> ParseError, same failure class
    as the two structural crashes (redesign.md 31.8 / 31.9)."""
    errs = []
    for m in re.finditer(r'@php\b(?!\s*\()(.*?)@endphp', src, re.S):
        blk = m.group(1)
        blk = re.sub(r'/\*.*?\*/', '', blk, flags=re.S)
        blk = re.sub(r'//[^\n]*', '', blk)
        blk = re.sub(r"'(?:\\.|[^'\\])*'", "''", blk)
        blk = re.sub(r'"(?:\\.|[^"\\])*"', '""', blk)
        if blk.count('{') != blk.count('}'):
            line = src[:m.start()].count('\n') + 1
            errs.append(f'line {line}: @php block has unbalanced braces '
                        f'({blk.count("{")} open, {blk.count("}")} close)')
    return errs


def main() -> int:
    css = set(re.findall(r'\.(mnz-[a-zA-Z0-9_-]+)',
                         open(os.path.join(ROOT, 'public/css/mnz-ui.css')).read()))
    files = sorted(glob.glob(os.path.join(ROOT, 'resources/views/themes/**/*.blade.php'),
                             recursive=True))

    # Blade STRUCTURE is checked on every view in the repo, not just themed
    # ones. Two production ParseErrors (redesign.md 31.8, 31.9) shipped from
    # files outside themes/: a shared partial this migration created, and an
    # error-page partial. Structure is cheap to verify and a broken view is a
    # 500, so there is no reason to scope this check narrowly. The variable and
    # CSS checks stay scoped to themes/ — a partial legitimately relies on
    # variables its parent defines.
    all_views = {}
    for _f in glob.glob(os.path.join(ROOT, 'resources/views/**/*.blade.php'), recursive=True):
        _rel = _f.split('resources/views/')[1][:-len('.blade.php')].replace('/', '.')
        all_views[_rel] = _f

    structural = sorted(f for f in glob.glob(
        os.path.join(ROOT, 'resources/views/**/*.blade.php'), recursive=True)
        if '/themes/' not in f)

    # Variables any controller passes to any view. Derived from the source so
    # this list cannot drift as controllers change — a hand-maintained list
    # would go stale and start reporting false failures.
    controller_vars = set()
    # Services can also build a view payload. TeamAccessService::viewShared()
    # returns teamLayout / teamRoutes / userLimitMessage / showConsultantTrialNotice
    # etc., which controllers array_merge into the view data -- so those names
    # never appear in a controller compact() and were reported as undefined.
    # Scanning app/Services alongside the controllers fixes that for any service
    # that assembles a payload the same way.
    for f in glob.glob(os.path.join(ROOT, 'app/Http/Controllers/**/*.php'), recursive=True) \
            + glob.glob(os.path.join(ROOT, 'app/Services/**/*.php'), recursive=True):
        src = open(f).read()
        controller_vars |= set(re.findall(r"'([a-zA-Z_]\w*)'\s*=>", src))
        for c in re.findall(r"compact\(([^)]*)\)", src, re.S):
            controller_vars |= set(re.findall(r"'([a-zA-Z_]\w*)'", c))
    SHARED.update(controller_vars)

    # A partial's variables are supplied by whoever @includes it, so the keys of
    # every @include(..., [...]) array in any view are legitimate definitions for
    # the partial being included. Without this, a shared partial like
    # help/partials/guide-highlight -- whose $highlight ALWAYS arrives from its
    # two call sites, and which additionally guards itself with
    # @if(!empty($highlight)) on line 1 -- is reported as undefined.
    # Harvested across all views, not just themed ones, because a themed partial
    # can legitimately be included by a shared one and vice versa.
    include_vars = set()
    for f in glob.glob(os.path.join(ROOT, 'resources/views/**/*.blade.php'), recursive=True):
        src = open(f).read()
        for arr in re.findall(r"@include\s*\([^,]+,\s*\[(.*?)\]\s*\)", src, re.S):
            include_vars |= set(re.findall(r"'([a-zA-Z_]\w*)'\s*=>", arr))
    SHARED.update(include_vars)

    # Classes defined inline by any shell layout — available to wrapped views.
    layout_css = [set(re.findall(r'\.(mnz-[a-zA-Z0-9_-]+)', open(f).read()))
                  for f in files if '/layouts/' in f]

    failures = 0
    structural_failures = 0
    for f in structural:
        _src = open(f).read()
        probs = (blade_structure_errors(_src)
                 + blade_include_errors(_src, all_views)
                 + blade_php_brace_errors(_src))
        if probs:
            structural_failures += 1
            failures += 1
            print(f'  FAIL {f.split("resources/views/")[1]}  (structure)')
            for x in probs:
                print(f'         {x}')
    print(f'  {len(structural)} non-theme views scanned for Blade structure, '
          f'{structural_failures} broken')

    for f in files:
        rel = f.split('resources/views/')[1]
        src = open(f).read()
        body = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)  # strip comments
        problems = []

        # Directive STRUCTURE, walked with a stack on the raw source.
        #
        # Counting open/close pairs is not enough: it reports a file clean when
        # an @else has no enclosing @if (which is how the second production
        # ParseError shipped -- see redesign.md section 31.9), because the counts
        # still balanced. Blade also compiles directives BEFORE stripping
        # {{-- --}} comments, so this walks src, not the stripped body.
        problems += blade_structure_errors(src)
        problems += blade_include_errors(src, all_views)
        problems += blade_php_brace_errors(src)

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
