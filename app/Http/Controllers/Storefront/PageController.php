<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        return view('storefront.pages.show', ['slug' => $slug]);
    }
}
