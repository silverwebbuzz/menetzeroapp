<?php

namespace App\Http\Controllers;

class PortalGuideController extends Controller
{
    public function company()
    {
        $guide = config('portal-guide-company');

        if (request()->routeIs('consultant.company-guide')) {
            return view('help.consultant-company-guide', compact('guide'));
        }

        return view('help.company', compact('guide'));
    }

    public function consultant()
    {
        return view('help.consultant', [
            'guide' => config('portal-guide-consultant'),
        ]);
    }
}
