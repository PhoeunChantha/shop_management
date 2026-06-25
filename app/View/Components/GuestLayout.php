<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  bool  $bare  When true, render the slot full-bleed (no centered card).
     */
    public function __construct(public bool $bare = false)
    {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('admin.layouts.guest');
    }
}
