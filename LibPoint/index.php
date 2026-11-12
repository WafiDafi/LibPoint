<?php
// Front controller: every request enters here (index.php?page=...)
require __DIR__ . '/src/bootstrap.php';
Router::dispatch();
