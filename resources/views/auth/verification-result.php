<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAMS - Email verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h1 class="h3">Email verification failed</h1>
                        <p class="text-danger" role="alert">
                            <?= htmlspecialchars($error ?? 'This verification link is invalid or expired.', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <a class="btn btn-primary" href="<?= APP_BASE_PATH ?>/signup">
                            Create organization
                        </a>
                        <a class="btn btn-link" href="<?= APP_BASE_PATH ?>/">
                            Sign in
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
