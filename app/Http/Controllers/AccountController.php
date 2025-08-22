<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function edit($id)
    {
        return view('accounts.edit', compact('id'));
    }
}
