<?php
namespace phputil\sql;

const __BASE_DIR = __DIR__ . '/';

// Commands
require_once __BASE_DIR . 'select.php';
require_once __BASE_DIR . 'insert.php';
require_once __BASE_DIR . 'update.php';
require_once __BASE_DIR . 'delete.php';

// Functions
require_once __BASE_DIR . 'functions/aggregate.php';
require_once __BASE_DIR . 'functions/basic.php';
require_once __BASE_DIR . 'functions/datetime.php';
require_once __BASE_DIR . 'functions/logical.php';
require_once __BASE_DIR . 'functions/math.php';
require_once __BASE_DIR . 'functions/null.php';
require_once __BASE_DIR . 'functions/ordering.php';
require_once __BASE_DIR . 'functions/string.php';
