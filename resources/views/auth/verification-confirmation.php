<?php
$token = $_GET['token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAMS - Verify email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h1 class="h3">
                            Verify your email address
                        </h1>
                        <p class="text-muted">
                            Confirm this address to activate your organization and owner account.
                        </p>
                        <form method="POST" action="<?= APP_BASE_PATH ?>/verify-email">
                            <input
                                type="hidden"
                                name="token"
                                value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <button type="submit" class="btn btn-primary">
                                Verify email and activate
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
