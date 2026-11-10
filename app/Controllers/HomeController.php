<?php

namespace App\Controllers;

class HomeController extends Controller
{
    public function index(): string
    {
        return $this->view(
            'home',
            [
                'title' => 'EAMS'
            ]
        );
    }
}