<?php

namespace App\Http\View\Composers;

use App\Support\PlanGate;
use Illuminate\View\View;

class PlanGateComposer
{
    public function compose(View $view): void
    {
        $view->with('gate', PlanGate::forUser(auth('web')->user()));
    }
}
