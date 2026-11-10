<?php

$email = $email ?? '';
$error = $error ?? null;
$submitted = $submitted ?? false;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAMS - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center">Forgot password?</h1>
                        <p class="text-muted text-center">
                            Enter your account email and we’ll send a reset link if it matches an active account.
                        </p>

                        <?php if ($submitted): ?>
                            <div class="alert alert-success" role="status">
                                Password reset link has been sent. Please check your email.
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" id="forgotPasswordErrorFallback" role="alert">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= APP_BASE_PATH ?>/forgot-password">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="form-control"
                                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                    autocomplete="email"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Send reset link</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="<?= APP_BASE_PATH ?>/">Back to sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if ($error): ?>
        <div class="modal fade" id="forgotPasswordErrorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger-subtle">
                        <h2 class="modal-title h5 text-danger">
                            Unable to continue
                        </h2>
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
                const fallback = document.getElementById('forgotPasswordErrorFallback');
                if (fallback) {
                    fallback.hidden = true;
                }
                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById('forgotPasswordErrorModal')
                ).show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
