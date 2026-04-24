<?php
/**
 * Functions.php — Orchestrator.
 *
 * Loads all feature modules and registers non-WooCommerce hooks.
 * Keep this file thin: no business logic, just wiring.
 *
 * @package  Theme_Customisations
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Load modules (order matters: helpers first) ────────────────────
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/acf-fields.php';
require_once __DIR__ . '/class-tf-product-sizing.php';
require_once __DIR__ . '/account-measures.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/product-pricing.php';

// ── Boot WooCommerce sizing integration ────────────────────────────
new TF_Product_Sizing();
