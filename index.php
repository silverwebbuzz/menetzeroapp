<?php

// Front controller shim for environments where .htaccess rewrites are disabled.
// This file forwards all requests to Laravel's public/index.php.

require __DIR__ . '/public/index.php';


