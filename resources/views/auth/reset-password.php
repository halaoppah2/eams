<?php

$token = $token ?? '';
$error = $error ?? null;
$invalidToken = $invalidToken ?? false;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAMS - Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">

                        <h1 class="h3 text-center">
                            Reset your password
                        </h1>

                        <?php if ($invalidToken): ?>
                            <div class="alert alert-warning" role="alert">
                                This reset link is invalid or expired. Request a new link to continue.
                            </div>
                            <a href="<?= APP_BASE_PATH ?>/forgot-password" class="btn btn-primary w-100">
                                Request a new reset link
                            </a>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger" id="resetPasswordErrorFallback" role="alert">
                                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= APP_BASE_PATH ?>/reset-password">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        New password
                                    </label>
                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        class="form-control"
                                        minlength="8"
                                        autocomplete="new-password"
                                        required
                                    >
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">
                                        Confirm new password
                                    </label>
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        class="form-control"
                                        minlength="8"
                                        autocomplete="new-password"
                                        required
                                    >
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    Update password
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="<?= APP_BASE_PATH ?>/login">
                                Back to sign in
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if ($error && !$invalidToken): ?>
        <div class="modal fade" id="resetPasswordErrorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger-subtle">
                        <h2 class="modal-title h5 text-danger">Unable to reset password</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const fallback = document.getElementById('resetPasswordErrorFallback');
                if (fallback) {
                    fallback.hidden = true;
                }
                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById('resetPasswordErrorModal')
                ).show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
