import re, sys

# Blade directive balance check: @if/@endif, @foreach/@endforeach, @php/@endphp
PAIRS = {
    'if': 'endif', 'foreach': 'endforeach', 'php': 'endphp',
    'forelse': 'endforelse', 'while': 'endwhile', 'for': 'endfor',
    'section': 'endsection', 'isset': 'endisset', 'auth': 'endauth',
}
OPENERS = set(PAIRS)
CLOSERS = {v: k for k, v in PAIRS.items()}

ok = True
for path in sys.argv[1:]:
    src = open(path).read()
    # strip blade comments so commented directives don't count
    src = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)
    stack = []
    errs = []
    for m in re.finditer(r'@(\w+)', src):
        d = m.group(1)
        line = src[:m.start()].count('\n') + 1
        if d in OPENERS:
            # @php ... @endphp only when block form (no inline @php($x = 1))
            if d == 'php' and src[m.end():m.end()+1] == '(':
                continue
            stack.append((d, line))
        elif d in CLOSERS:
            want = CLOSERS[d]
            if not stack:
                errs.append(f"  line {line}: @{d} with nothing open")
            elif stack[-1][0] != want:
                errs.append(f"  line {line}: @{d} closes @{want}, but @{stack[-1][0]} (line {stack[-1][1]}) is open")
                stack.pop()
            else:
                stack.pop()
    for d, line in stack:
        errs.append(f"  line {line}: @{d} never closed")

    # Brace balance inside @php blocks
    for m in re.finditer(r'@php\b(?!\()(.*?)@endphp', src, flags=re.S):
        blk = m.group(1)
        if blk.count('{') != blk.count('}'):
            line = src[:m.start()].count('\n') + 1
            errs.append(f"  line {line}: @php block braces unbalanced ({blk.count('{')} open, {blk.count('}')} close)")
        if blk.count('(') != blk.count(')'):
            line = src[:m.start()].count('\n') + 1
            errs.append(f"  line {line}: @php block parens unbalanced")

    print(("FAIL " if errs else "OK   ") + path)
    for e in errs:
        print(e)
        ok = False

sys.exit(0 if ok else 1)
