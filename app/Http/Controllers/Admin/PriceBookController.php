<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommercialPriceBookEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PriceBookController extends Controller
{
    public function index()
    {
        $entries = CommercialPriceBookEntry::query()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('admin.price-book.index', compact('entries'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'entries' => 'required|array',
            'entries.*.id' => 'required|integer|exists:commercial_price_book_entries,id',
            'entries.*.label' => 'required|string|max:120',
            'entries.*.amount_aed' => 'nullable|numeric|min:0|max:9999999',
            'entries.*.is_custom' => 'sometimes|boolean',
            'entries.*.notes' => 'nullable|string|max:1000',
        ]);

        foreach ($data['entries'] as $row) {
            $entry = CommercialPriceBookEntry::findOrFail($row['id']);
            $isCustom = !empty($row['is_custom']) || ($row['amount_aed'] === null || $row['amount_aed'] === '');

            $entry->update([
                'label' => $row['label'],
                'amount_aed' => $isCustom ? null : (float) $row['amount_aed'],
                'is_custom' => $isCustom,
                'notes' => $row['notes'] ?? null,
            ]);
        }

        Cache::forget('commercial_price_book_all');

        return back()->with('success', 'Price book updated. Quote suggestions will use the new amounts.');
    }
}
