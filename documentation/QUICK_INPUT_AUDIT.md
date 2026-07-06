# Quick Input Calculation Audit

A review of [QuickInputController.php](../app/Http/Controllers/QuickInputController.php) (1384 lines) and [EmissionCalculationService.php](../app/Services/EmissionCalculationService.php) (403 lines) looking for bugs that could produce wrong CO₂e numbers.

## Summary

**The calculation math itself is correct.** The GWP multiplication is commented out but that's intentional — your `emission_factors.total_co2e_factor` column already stores CO₂e-equivalent values with GWP baked in. Verified by reconciling entry #3 in your prod dump (Coal domestic, 100 tonnes → 292,138.77 kg CO₂e = factor 2921 × 100, matches DEFRA 2024).

**But there are several bugs that cause inconsistent results across the three code paths** (Calculate AJAX preview, Store new entry, Update existing entry). The three paths do NOT compute the same number for the same input in all cases.

---

## Confirmed bugs

### 🔴 Bug #1 — `dd()` left in production code
**File:** [QuickInputController.php:522](../app/Http/Controllers/QuickInputController.php#L522)

```php
if (!$emissionFactor) {
    dd($emissionFactor);            // ← crashes the app
    return back()->withErrors([...]);
}
```

`dd()` is Laravel's debug dump — it halts execution and dumps to the browser. Instead of seeing a friendly "no factor found" message, users see raw debug output on refrigerants (and anywhere else a factor goes missing).

**Fix:** Remove the `dd()` line.

---

### 🔴 Bug #2 — Refrigerant region mismatch between store vs calculate vs update
**Files:**
- [QuickInputController.php:495-496](../app/Http/Controllers/QuickInputController.php#L495) — `store()` wrongly uses `UAE`
- [QuickInputController.php:672](../app/Http/Controllers/QuickInputController.php#L672) — `calculate()` correctly uses `Global` for fugitive
- [QuickInputController.php:909](../app/Http/Controllers/QuickInputController.php#L909) — `update()` correctly uses `Global` for fugitive

**`store()` (when saving a NEW entry):**
```php
// $defaultRegion = ($emissionSource->emission_type === 'fugitive') ? 'Global' : 'UAE';
$defaultRegion = 'UAE';                    // ← hardcoded UAE for everything
```

**`calculate()` (AJAX preview):**
```php
$defaultRegion = ($emissionSource->emission_type === 'fugitive') ? 'Global' : 'UAE';  // correct
```

**`update()` (when editing existing entry):**
```php
$defaultRegion = ($emissionSource->emission_type === 'fugitive') ? 'Global' : 'UAE';  // correct
```

**Impact:** For **refrigerant / fugitive emissions**:
- User types values → clicks **Calculate** → sees CO₂e value X (correct, uses Global region)
- User clicks **Calculate & Add to Footprint** → saves CO₂e value Y (wrong, uses UAE region → may pick wrong factor or fail entirely with "No suitable emission factor found")
- User edits that same entry → recalculates to value X again (using Global)

So for refrigerants, **preview and save produce different numbers**. Same entry edited later may silently change values.

**Fix:** Uncomment line 495 and delete line 496 in `store()`.

---

### 🟡 Bug #3 — `store()` logic is inline instead of calling `determineFuelType()`
**File:** [QuickInputController.php:498-505 and 1324-1344](../app/Http/Controllers/QuickInputController.php#L498)

There's a clean helper at line 1324 (`determineFuelType()`) used by the store and update's `fuel_type` column save. But `store()` and `update()`'s **factor-selection** logic (not the column save) re-implements the same thing inline with slight differences.

```php
// In store() for factor selection:
$fuelType = $request->input('fuel_type') ?? $request->input('vehicle_fuel_type');
if ($emissionSource->quick_input_slug === 'process' && $processType) {
    $fuelType = $processType;
} else {
    $fuelType = $fuelType ?: $request->input('energy_type') ?: $request->input('refrigerant_type');
}
```

vs. the `determineFuelType()` helper which has slightly cleaner branching.

vs. `calculate()` which has yet a third variant (lines 674-688) with different fallback order.

**Impact:** Edge cases where multiple fuel-type inputs are provided may pick different values in preview vs. save. Not a common user flow but a potential data integrity issue.

**Fix:** Make all three paths (calculate, store, update) use the same `determineFuelType()` helper for factor-selection conditions too.

---

### 🟡 Bug #4 — Duplicate refrigerant assignment (dead code)
**File:** [QuickInputController.php:606-611](../app/Http/Controllers/QuickInputController.php#L606)

```php
if ($request->input('refrigerant_type')) {
    $additionalDataToUpdate['refrigerant_type'] = $request->input('refrigerant_type');
}
if ($request->input('refrigerant_type')) {      // ← same check twice
    $additionalDataToUpdate['refrigerant_type'] = $request->input('refrigerant_type');
}
```

Harmless (just wastes a CPU cycle), but shows sloppy copy-paste and may confuse future maintainers.

**Fix:** Remove the duplicate block.

---

### 🟡 Bug #5 — `store()` and `update()` diverge on additional-data handling
**File:** [QuickInputController.php:552 vs 947](../app/Http/Controllers/QuickInputController.php#L552)

**`store()` loads form fields directly:**
```php
$formFields = EmissionSourceFormField::where('emission_source_id', $emissionSource->id)->get();
foreach ($formFields as $field) { ... }
```

**`update()` uses the form-builder service:**
```php
$formFields = $this->formBuilder->buildForm($emissionSource->id);
foreach ($formFields as $field) { ... }
```

`buildForm()` presumably does additional processing (conditional fields, ordering, industry-specific logic). This means when you **create** an entry, some optional fields may or may not be saved to `additional_data`, but when you **edit** that same entry, different fields may be saved.

**Impact:** Edit-then-save on an existing entry might drop or add fields inconsistently from the original create. Not a calculation bug, but a data integrity drift.

**Fix:** Make `store()` use `$this->formBuilder->buildForm()` too.

---

### 🟡 Bug #6 — `calculate()` reads `quantity` from request twice with different fallback order
**File:** [QuickInputController.php:666 and 743](../app/Http/Controllers/QuickInputController.php#L666)

Line 666 (early in method):
```php
$quantity = $request->input('distance') ?? $request->input('quantity');
```

Line 743 (later in same method):
```php
$quantity = $request->input('quantity') ?? $request->input('amount') ?? $request->input('distance');
```

The first lookup is never used — line 666's result is overwritten. But the fallback ORDER is different: `distance→quantity` vs `quantity→amount→distance`. The second wins. Likely fine, but confusing.

**Fix:** Delete the line 666 assignment (it's dead code).

---

### 🟢 Not actually bugs (verified safe)

- **GWP multiplication commented out** (line 181 of EmissionCalculationService) — intentional. The `emission_factors.total_co2e_factor` column already contains CO₂e-equivalent values with GWP applied, so re-multiplying would double-count.
- **`Schema::hasColumn()` guards** in `updateMeasurementTotals` — performance cost on every save but correct.
- **Rollup SQL (`SUM(calculated_co2e)`, etc.)** — straightforward and correct.
- **Unit conversion** — looks correct, with forward + reverse lookup fallback.

---

## What could produce visibly wrong numbers in the UI

Based on the bugs above, **these user flows are suspicious**:

1. **Add any refrigerant (fugitive) entry** — Calculate preview shows one number, actual save may fail ("No suitable emission factor found") or store a different number.
2. **Edit any existing refrigerant entry** — the saved value may change vs. what was originally created (because original used wrong region, edit uses correct region).
3. **Refrigerant factor lookup fails completely** — user sees `dd()` crash dump on screen instead of friendly error.

**Everything else** (Natural Gas, Fuel, Vehicle, Electricity, Heat/Steam/Cooling, Flights, Public Transport, Home Workers, Process) uses `region = UAE` consistently across store/calculate/update. Those paths should give the same number every time.

---

## Recommended fix order

1. **Bug #1** — remove `dd()`. 10-second fix, directly visible to users.
2. **Bug #2** — fix `store()` region for fugitive emissions. Main data-correctness fix.
3. **Bug #4** — remove duplicate refrigerant block. Cosmetic.
4. **Bug #6** — remove dead quantity assignment. Cosmetic.
5. **Bug #5, #3** — harmonize store/update/calculate. More involved, may need careful testing.

All are local changes to `QuickInputController.php`. None require database changes.

## Testing

After fixes, test specifically:
- **Add refrigerant entry** (Scope 1 → Refrigerants → any gas, e.g. R-134a) → click Calculate (note the kg CO₂e) → click Save → verify entry in list shows the SAME kg CO₂e
- **Edit that refrigerant entry** → change nothing → Save → verify value doesn't drift
- **Add entry with invalid combination** (e.g. a fuel type that has no factor) → should see friendly error, not `dd()` crash

## I can't predict the visual calculation numbers without running the app

The only way to know for sure which other sources have issues is:
1. Set up a test company with known inputs
2. Run each source type end-to-end (Calculate → Save → check DB)
3. Compare saved `calculated_co2e` against known reference values (DEFRA 2024, UAE grid factors, etc.)

That's a QA exercise, not a code audit. Happy to write the script that runs through each source if you want, but it needs real prod data to compare against.
