<?php

namespace Mlangeni\Machinjiri\App\Controllers;

use Mlangeni\Machinjiri\Core\Artisans\Base\AbstractController;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;

class HomeController extends AbstractController
{
    /**
     * Display the welcome page.
     *
     * @param HttpRequest  $request
     * @param HttpResponse $response
     * @return string|HttpResponse
     */
    public function index(HttpRequest $request, HttpResponse $response)
    {
        // Example: render a view
        return $this->view('welcome');
    }
    
    // Add your custom methods below
}