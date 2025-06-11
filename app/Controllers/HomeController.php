<?php

namespace Controllers;

use Core\Controller;
use Core\Response;
use Core\View;

class HomeController extends Controller
{
    public function index(): void
    {
        Response::send("<h1>Welcome to the OpenFest custom framework</h1>");
    }

	public function view(): void
	{
        View::render('home', ['title' => 'Welcome', 'message' => 'Hello from native PHP!'], 'layouts/main');
    }

	public function view404(): void
	{
		View::render('home2', ['title' => 'Page Not Found', 'message' => 'Cannot find the pate you are looking for'], 'layouts/main2');
	}
}