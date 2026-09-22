<?php
namespace App\Controllers;

use App\Core\Controller;

class ApiController extends Controller
{
    public function status(): void
    {
        $this->json([
            'application' => config('app.name'),
            'status' => 'ok',
            'version' => 'fase-3-v3',
            'timestamp' => date(DATE_ATOM),
        ]);
    }
}
