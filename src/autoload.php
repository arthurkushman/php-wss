<?php

/**
 * Create by Arthur Kushman
 */
spl_autoload_register(function ($class) {
    require_once __DIR__ . '/' . $class;
});
