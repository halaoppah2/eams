<?php

namespace App\Controllers;

class Controller
{
    protected function view(
        string $view,
        array $data = []
    ): string {

        /*
         * First check the newer application views.
         */
        $appViewPath =
            __DIR__ .
            '/../Views/' .
            $view .
            '.php';

        /*
         * Fall back to the original resources/views
         * location.
         */
        $resourceViewPath =
            __DIR__ .
            '/../../resources/views/' .
            $view .
            '.php';

        if (file_exists($appViewPath)) {

            $viewPath = $appViewPath;

        } elseif (file_exists($resourceViewPath)) {

            $viewPath = $resourceViewPath;

        } else {

            throw new \LogicException(
                "View [{$view}] not found."
            );
        }

        extract($data);

        ob_start();

        require $viewPath;

        return ob_get_clean();
    }
}