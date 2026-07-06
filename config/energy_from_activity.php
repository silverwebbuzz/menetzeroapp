<?php

/**
 * Phase E.2e — Convert Quick Input activity quantities to GJ (GRI 302-1).
 *
 * Factors are net calorific / IEC conventions for inventory energy totals.
 * Used only by enterprise scorecard metric — does not overwrite GRI manual entry.
 */
return [
  'excluded_slugs' => [
    'refrigerants',
    'fugitive-emissions',
    'waste',
    'wastewater',
    'business-travel',
    'employee-commuting',
    'purchased-goods',
  ],

  /** Direct unit → GJ multiplier (case-insensitive match). */
  'unit_to_gj' => [
    'kwh' => 0.0036,
    'mwh' => 3.6,
    'gj' => 1.0,
    'mj' => 0.001,
    'm3' => 0.039,
    'cubic metres' => 0.039,
    'therm' => 0.1055,
  ],

  /** Per-litre GJ by fuel hint (slug or fuel_type substring). */
  'litre_gj_factors' => [
    'diesel' => 0.0386,
    'petrol' => 0.0342,
    'gasoline' => 0.0342,
    'lpg' => 0.0273,
    'natural_gas' => 0.024,
    'natural-gas' => 0.024,
    'default' => 0.036,
  ],
];
