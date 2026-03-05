<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'promptCount'  => Prompt::count(),
            'activeCount'  => Prompt::whereNotNull('active_version_id')->count(),
            'userCount'    => User::count(),
        ]);
    }
}
