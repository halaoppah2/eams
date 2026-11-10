<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>EAMS - Login</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

    <div class="container">

        <div
            class="row justify-content-center align-items-center"
            style="min-height: 100vh;"
        >

            <div class="col-md-5 col-lg-4">

                <div class="card shadow-sm">

                    <div class="card-body p-4">

                        <div class="text-center mb-4">

                            <h3 class="fw-bold">
                                EAMS
                            </h3>

                            <p class="text-muted mb-0">
                                Attendance Management System
                            </p>

                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" id="loginErrorFallback" role="alert">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($expired)): ?>
                            <div class="alert alert-warning" role="alert">
                                You were signed out after 30 minutes without activity. 
                                Please sign in again.
                            </div>
                        <?php elseif (!empty($reset)): ?>
                            <div class="alert alert-success" role="status">
                                Your password has been updated. You can now sign in.
                            </div>
                        <?php elseif (!empty($verified)): ?>
                            <div class="alert alert-success" role="status">
                                Your email is verified and your organization is active. You can now sign in.
                            </div>
                        <?php elseif (!empty($loggedOut)): ?>
                            <div class="alert alert-info" role="status">
                                You have been signed out.
                            </div>
                        <?php endif; ?>

                        <form
                            method="POST"
                            action="<?= APP_BASE_PATH ?>/login"
                        >

                            <div class="mb-3">

                                <label
                                    for="username"
                                    class="form-label"
                                >
                                    Username
                                </label>

                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-control"
                                    value="<?= htmlspecialchars($username ?? '') ?>"
                                    required
                                    autofocus
                                >

                            </div>

                            <div class="mb-3">

                                <label
                                    for="password"
                                    class="form-label"
                                >
                                    Password
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    required
                                >

                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                Sign In
                            </button>

                        </form>

                        <div class="text-end mt-3">
                            <a href="<?= APP_BASE_PATH ?>/forgot-password">
                                Forgot password?
                            </a>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?= APP_BASE_PATH ?>/signup">Create an organization</a>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php if (!empty($error)): ?>
        <div
            class="modal fade"
            id="loginErrorModal"
            tabindex="-1"
            aria-labelledby="loginErrorTitle"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">

                    <div class="modal-header border-0 bg-danger-subtle">
                        <h2 class="modal-title h5 text-danger mb-0" id="loginErrorTitle">
                            Sign-in failed
                        </h2>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="modal-footer border-0">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Close
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('loginErrorModal');
                if (modal && window.bootstrap) {
                    const fallback = document.getElementById('loginErrorFallback');
                    if (fallback) {
                        fallback.hidden = true;
                    }
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>

    <?php endif; ?>

</body>

</html>