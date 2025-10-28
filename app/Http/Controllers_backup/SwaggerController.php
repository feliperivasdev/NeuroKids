<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SwaggerLume\Http\Controllers\SwaggerLumeController;

class SwaggerController extends SwaggerLumeController
{
    public function docs()
    {
        // Attempt to (re)generate, but don't let generation warnings break the request.
        try {
            \SwaggerLume\Generator::generateDocs();
        } catch (\Throwable $e) {
            logger()->warning('Swagger generateDocs() failed in docs(): ' . $e->getMessage());
        }

        $path = storage_path('api-docs' . DIRECTORY_SEPARATOR . 'api-docs.json');
        if (file_exists($path)) {
            $json = file_get_contents($path);
            return new \Symfony\Component\HttpFoundation\Response($json, 200, ['Content-Type' => 'application/json']);
        }

        // Last resort: delegate to parent which will attempt to generate/return the file
        return parent::docs();
    }

    /**
     * Render Swagger UI (HTML). Matches the vendor's SwaggerLumeController::api().
     */
    public function api()
    {
        try {
            if (config('swagger-lume.generate_always')) {
                \SwaggerLume\Generator::generateDocs();
            }
        } catch (\Throwable $e) {
            logger()->warning('Swagger generateDocs() failed in api(): ' . $e->getMessage());
        }

                // Always return a minimal CDN-based UI that points to the generated docs JSON.
                // Use a relative path to avoid resolving UrlGenerator from the container.
                $jsonUrl = '/api/docs-json';
                $html = <<<HTML
<!doctype html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Swagger UI</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@4/swagger-ui-bundle.js"></script>
        <script>
            window.ui = SwaggerUIBundle({
                url: '{$jsonUrl}',
                dom_id: '#swagger-ui',
                presets: [SwaggerUIBundle.presets.apis],
                layout: 'BaseLayout'
            })
        </script>
    </body>
</html>
HTML;

                return new \Symfony\Component\HttpFoundation\Response($html, 200, ['Content-Type' => 'text/html']);
    }
}