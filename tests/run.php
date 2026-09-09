<?php

require __DIR__ . '/bootstrap.php';

exit((new \Spark\Testing\Runner())->run(__DIR__, array_slice($argv, 1)));
