"""Catch undefined-variable bugs in Blade partials without a PHP runtime.

Collects every $var READ in a template and every $var DEFINED (assigned in
@php, bound by @foreach/@forelse, or supplied by a view composer), then
reports reads with no definition.

This exists because @include renders in a CHILD scope: variables a partial
defines are discarded on return, so `@include(...)` followed by use of a
variable that partial set is always a bug. That shipped once; this catches it.
"""
import re, sys

# Variables supplied from outside the template.
COMPOSER_PROVIDED = {
    # PlanGateComposer, bound to layouts.partials.nav-client
    'gate', 'companyRenewalNudge',
    # ThemeServiceProvider::shareThemeWithViews (View::composer('*'))
    'activeTheme', 'isNewTheme', 'themeAssets',
    # Laravel globals
    'errors', 'slot', 'attributes', 'loop', '__env', 'app',
}

def analyse(path):
    src = open(path).read()
    src = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)   # strip blade comments
    src = re.sub(r'//[^\n]*', '', src)                       # strip php line comments

    defined = set(COMPOSER_PROVIDED)

    # @php $x = ... / $x ??= ...
    for m in re.finditer(r'\$(\w+)\s*(?:=|\?\?=)(?!=)', src):
        defined.add(m.group(1))
    # @foreach ($xs as $k => $v) / as $v
    for m in re.finditer(r'@(?:foreach|forelse)\s*\((.*?)\)', src, flags=re.S):
        inner = m.group(1)
        for mm in re.finditer(r'as\s+\$(\w+)\s*=>\s*\$(\w+)', inner):
            defined.add(mm.group(1)); defined.add(mm.group(2))
        for mm in re.finditer(r'as\s+\$(\w+)\s*(?!=>)', inner):
            defined.add(mm.group(1))
    # closure params:  function ($a, $b) use ($c)
    for m in re.finditer(r'function\s*\(([^)]*)\)(?:\s*use\s*\(([^)]*)\))?', src):
        for grp in m.groups():
            if grp:
                for mm in re.finditer(r'\$(\w+)', grp):
                    defined.add(mm.group(1))
    # arrow-fn params:  fn ($y) => ...
    for m in re.finditer(r'\bfn\s*\(([^)]*)\)', src):
        for mm in re.finditer(r'\$(\w+)', m.group(1)):
            defined.add(mm.group(1))
    # null-coalesced reads are safe by construction:  $x ?? [] , $x ?->
    for m in re.finditer(r'\$(\w+)\s*\?\?', src):
        defined.add(m.group(1))

    # every read
    reads = {}
    for m in re.finditer(r'\$(\w+)', src):
        name = m.group(1)
        line = src[:m.start()].count('\n') + 1
        reads.setdefault(name, line)

    missing = {n: l for n, l in reads.items() if n not in defined}

    # An @include does NOT export variables to the parent — flag the pattern.
    includes = [(m.group(1), src[:m.start()].count('\n') + 1)
                for m in re.finditer(r"@include\('([^']+)'\)", src)]

    return missing, includes

ok = True
for path in sys.argv[1:]:
    missing, includes = analyse(path)
    status = "FAIL" if missing else "OK  "
    print(f"{status} {path}")
    for name, line in sorted(missing.items(), key=lambda kv: kv[1]):
        print(f"     line {line}: ${name} is read but never defined")
        ok = False
    for inc, line in includes:
        print(f"     note line {line}: @include('{inc}') — child scope, exports nothing")

sys.exit(0 if ok else 1)
