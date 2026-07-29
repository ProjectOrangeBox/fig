<?php

// the project root path
define('__ROOT__', realpath(__DIR__ . '/../../../../'));
// the htdocs path
define('__WWW__', realpath(__DIR__ . '/../../../../htdocs'));

define('ORANGEDIR', realpath(__DIR__ . '/../../framework/src'));

// Every fig plugin calls logMsg(), and the facade itself does too. Those helpers
// are normally loaded at runtime by Application::preContainer() via dynamic
// include_once rather than through composer's autoloader, so nothing in fig
// works without them.
require ORANGEDIR . '/helpers/helpers.php';
require ORANGEDIR . '/helpers/errors.php';
require ORANGEDIR . '/helpers/wrappers.php';

// include the composer autoloader (fig and FigException are classmapped)
require __DIR__ . '/../../../autoload.php';
