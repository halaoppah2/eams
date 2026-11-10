<?php

$submitted = $submitted ?? false;
$error = $error ?? null;
$organizationName = $organization_name ?? '';
$firstName = $first_name ?? '';
$lastName = $last_name ?? '';
$email = $email ?? '';
$phone = $phone ?? '';
$username = $username ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAMS - Create organization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container">
        <div class="row justify-content-center align-items-center py-4" style="min-height: 100vh;">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h3 text-center">Create your organization</h1>
                        <p class="text-muted text-center">
                            Start a 14-day trial. Verify your email before signing in.
                        </p>

                        <?php if ($submitted): ?>
                            <div class="alert alert-success" role="status">
                                We sent a verification link to
                                <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
                                The organization will be activated after you verify the address.
                            </div>
                            <div class="text-center">
                                <a href="<?= APP_BASE_PATH ?>/">Return to sign in</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger" id="signupErrorFallback" role="alert">
                                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= APP_BASE_PATH ?>/signup">
                                <h2 class="h5 mt-3">Organization</h2>
                                <div class="mb-3">
                                    <label for="organization_name" class="form-label">Organization name</label>
                                    <input
                                        type="text"
                                        id="organization_name"
                                        name="organization_name"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?= htmlspecialchars($organizationName, ENT_QUOTES, 'UTF-8') ?>"
                                        required
                                    >
                                </div>

                                <h2 class="h5 mt-4">Owner account</h2>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First name</label>
                                        <input
                                            type="text"
                                            id="first_name"
                                            name="first_name"
                                            class="form-control"
                                            maxlength="100"
                                            value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>"
                                            autocomplete="given-name"
                                            required
                                        >
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last name</label>
                                        <input
                                            type="text"
                                            id="last_name"
                                            name="last_name"
                                            class="form-control"
                                            maxlength="100"
                                            value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?>"
                                            autocomplete="family-name"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                        autocomplete="email"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone (optional)</label>
                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        class="form-control"
                                        maxlength="30"
                                        value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                        autocomplete="tel"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input
                                        type="text"
                                        id="username"
                                        name="username"
                                        class="form-control"
                                        minlength="3"
                                        maxlength="100"
                                        pattern="[A-Za-z0-9._-]{3,100}"
                                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                                        autocomplete="username"
                                        required
                                    >
                                    <div class="form-text">Usernames must be unique across EAMS.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control"
                                        minlength="8"
                                        autocomplete="new-password"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm password</label>
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        class="form-control"
                                        minlength="8"
                                        autocomplete="new-password"
                                        required
                                    >
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    Create organization
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <a href="<?= APP_BASE_PATH ?>/">Already have an account? Sign in</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php if ($error): ?>
        <div class="modal fade" id="signupErrorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger-subtle">
                        <h2 class="modal-title h5 text-danger">Unable to create organization</h2>
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
                const fallback = document.getElementById('signupErrorFallback');
                if (fallback) {
                    fallback.hidden = true;
                }
                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById('signupErrorModal')
                ).show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
