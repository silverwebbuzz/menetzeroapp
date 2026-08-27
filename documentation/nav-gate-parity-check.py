"""Simulate config/navigation.php rendering under each permission scenario,
and compare against what the ORIGINAL old-theme nav would have shown.

Guards risk R-1: no link may appear that the old nav hid.
"""
import re, sys, itertools

cfg = open('/Users/apple/Silverwebbuzz/menetzeroapp/config/navigation.php').read()

def parse_items(src):
    """Return list of (group_title, gate_group, label, gate_item)."""
    out = []
    # groups
    for gm in re.finditer(r"'(?:title)' => (null|'[^']*'),\s*\n\s*'gate' => '([^']+)',", src):
        pass
    # simpler: walk sequentially
    cur_group = None
    cur_group_gate = None
    lines = src.split('\n')
    pending_title = None
    for i, ln in enumerate(lines):
        mt = re.search(r"^\s{8}'title' => (null|'([^']*)')", ln)
        if mt:
            pending_title = mt.group(2) if mt.group(2) else 'Overview(untitled)'
            continue
        mg = re.search(r"^\s{12}'gate' => '([^']+)'", ln)
        if mg and pending_title is not None:
            cur_group, cur_group_gate = pending_title, mg.group(1)
            pending_title = None
            continue
        ml = re.search(r"^\s+'label' => '([^']+)'", ln)
        if ml:
            label = ml.group(1)
            # find this item's gate within next 8 lines
            gate = None
            for j in range(i, min(i+9, len(lines))):
                mig = re.search(r"^\s{16,}'gate' => '([^']+)'", lines[j])
                if mig:
                    gate = mig.group(1); break
            out.append((cur_group, cur_group_gate, label, gate))
    return out

items = parse_items(cfg)

def visible(gates, group_gate, item_gate):
    return gates.get(group_gate, False) and gates.get(item_gate, False)

# ---- scenarios -------------------------------------------------------
def gates_for(is_admin, has_company, locations, quick, reports, disclosures,
              staff, roles, managed):
    return {
        'always': True,
        'company': has_company,
        'locations': locations,
        'quick_input': quick,
        'reports': reports,
        'disclosures': disclosures,
        'team': staff or roles,
        'billing': is_admin and not managed,
        'admin': is_admin,
    }

# What the ORIGINAL old nav showed, per scenario (transcribed from the
# pre-change file, backed up in scratchpad/nav-client-old.bak.php).
def old_nav_shows(label, is_admin, has_company, locations, quick, reports,
                  disclosures, staff, roles, managed):
    if label == 'Overview':               return True           # Dashboard, always
    if label == 'Profile':
        # old: shown inside canViewReports block, or standalone when no company
        return (has_company and reports) or (not has_company)
    if not has_company:                   return False
    if label == 'Locations & boundaries': return locations
    if label in ('Measure', 'Bulk import'): return quick
    if label == 'GHG inventory':          return reports
    if label == 'Reporting':              return reports and is_admin
    if label == 'Team & access':          return (not managed) and (staff or roles or is_admin) and (staff or roles)
    if label in ('Billing', 'Find a consultant'):
        return (not managed) and is_admin
    if label == 'Help & guide':           return True
    # every disclosure surface sat behind canViewDisclosures
    return disclosures

violations = []
checked = 0
for combo in itertools.product([True, False], repeat=9):
    is_admin, has_company, locations, quick, reports, disclosures, staff, roles, managed = combo
    # isAdmin implies all module perms in the real gate computation
    if is_admin:
        locations = quick = reports = disclosures = staff = roles = True
    if not has_company and is_admin:
        # isAdmin needs companyId for company-admin path; super admin still True
        pass
    g = gates_for(is_admin, has_company, locations, quick, reports,
                  disclosures, staff, roles, managed)
    for group, ggate, label, igate in items:
        now = visible(g, ggate, igate)
        before = old_nav_shows(label, is_admin, has_company, locations, quick,
                               reports, disclosures, staff, roles, managed)
        checked += 1
        if now and not before:
            violations.append((label, combo))

print(f"items in config      : {len(items)}")
print(f"scenario checks      : {checked}")
if violations:
    seen = {}
    for label, combo in violations:
        seen.setdefault(label, combo)
    print(f"\nWIDENED ACCESS in {len(violations)} checks, {len(seen)} distinct links:")
    for label, combo in seen.items():
        keys = ['is_admin','has_company','locations','quick','reports','disclosures','staff','roles','managed']
        print("  -", label, dict(zip(keys, combo)))
    sys.exit(1)
print("\nNo link is shown that the old nav hid. OK")
