<?php
/*
 * Public Entry Point
 * @Author: Precious Lyson
 * This file serves as the front controller for all HTTP requests.
 * It bootstraps the application and handles incoming requests.
 * Make sure to keep this file secure and do not expose sensitive information.
 */
require __DIR__ . '/../bootstrap/app.php';
/**
 * Initialize Machinjiri Framework
 */
$machinjiri->init();
/* Handle the incoming request and send the response */