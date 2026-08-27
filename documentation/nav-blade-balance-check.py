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
            # @section('title', 'X') is INLINE and self-closing; only the
            # single-argument @section('content') opens a block.
            if d == 'section':
                tail = src[m.end():]
                depth_p = 0
                arg = ''
                for ch in tail:
                    if ch == '(':
                        depth_p += 1
                        if depth_p == 1:
                            continue
                    elif ch == ')':
                        depth_p -= 1
                        if depth_p == 0:
                            break
                    if depth_p >= 1:
                        arg += ch
                    elif ch not in ' \t':
                        break
                # a comma at paren-depth 1 means a second argument
                d2 = 0
                inline = False
                for ch in arg:
                    if ch in '([': d2 += 1
                    elif ch in ')]': d2 -= 1
                    elif ch == ',' and d2 == 0:
                        inline = True
                        break
                if inline:
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
