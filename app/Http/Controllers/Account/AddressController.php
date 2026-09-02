<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(): View
    {
        return view('account.addresses');
    }
}
