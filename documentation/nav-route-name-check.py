import re, sys

src = open('/Users/apple/Silverwebbuzz/menetzeroapp/routes/web.php').read()
lines = src.split('\n')

# Walk the file tracking brace depth, and the ->name('x.') prefix opened by
# each group. Route::resource / Route::get etc. get the accumulated prefix.
names = set()
stack = []          # list of (depth_at_open, name_prefix)
depth = 0

for raw in lines:
    line = raw
    stripped = line.strip()

    # Does this line OPEN a group with a name prefix?
    grp = None
    if 'Route::' in line and 'group(' in line:
        m = re.findall(r"->name\('([^']+)'\)", line)
        grp = m[0] if m else ''

    prefix = ''.join(p for _, p in stack)

    # Named endpoints on this line (not group declarations)
    if grp is None:
        for m in re.finditer(r"->name\('([^']+)'\)", line):
            names.add(prefix + m.group(1))
        # Route::resource('locations', ...) -> locations.index/create/store/...
        rm = re.search(r"Route::resource\('([^']+)'", line)
        if rm:
            base = rm.group(1)
            for verb in ('index', 'create', 'store', 'show', 'edit', 'update', 'destroy'):
                names.add(prefix + base + '.' + verb)
        # ->names([...]) explicit map
        for m in re.finditer(r"'(?:index|create|store|edit|update|destroy|show)' => '([^']+)'", line):
            names.add(prefix + m.group(1))

    opens = line.count('{') + line.count('(')
    closes = line.count('}') + line.count(')')

    if grp is not None:
        stack.append((depth, grp))

    depth += opens - closes

    while stack and depth <= stack[-1][0]:
        stack.pop()

wanted = [l.strip() for l in open(sys.argv[1]) if l.strip()]
missing = [w for w in wanted if w not in names]

print("routes parsed from web.php:", len(names))
print("nav route names checked   :", len(wanted))
if missing:
    print("\nMISSING (would throw at render):")
    for m in missing:
        near = sorted(n for n in names if n.startswith(m.split('.')[0]))[:8]
        print("  -", m, "   near:", near)
    sys.exit(1)
print("\nAll nav route names resolve. OK")
