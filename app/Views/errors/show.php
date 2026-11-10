<?php

$title = isset($title) && is_string($title) && trim($title) !== ''
    ? $title
    : 'Something went wrong';
$message = isset($message) && is_string($message) && trim($message) !== ''
    ? $message
    : 'An unexpected error occurred. Please try again later.';

$safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $safeTitle ?> - EAMS</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="alert alert-danger" id="applicationErrorFallback" role="alert">
            <h1 class="h5"><?= $safeTitle ?></h1>
            <p class="mb-0"><?= $safeMessage ?></p>
        </div>
        <a href="<?= APP_BASE_PATH ?>/" class="btn btn-primary">Return to home</a>
    </main>

    <div
        class="modal fade"
        id="applicationErrorModal"
        tabindex="-1"
        aria-labelledby="applicationErrorTitle"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-danger-subtle">
                    <h2 class="modal-title h5 text-danger mb-0" id="applicationErrorTitle">
                        <?= $safeTitle ?>
                    </h2>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body"><?= $safeMessage ?></div>
                <div class="modal-footer border-0">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                    <a href="<?= APP_BASE_PATH ?>/" class="btn btn-primary">Return to home</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('applicationErrorModal');
            if (modal && window.bootstrap) {
                const fallback = document.getElementById('applicationErrorFallback');
                if (fallback) {
                    fallback.hidden = true;
                }
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    </script>
</body>
</html>
