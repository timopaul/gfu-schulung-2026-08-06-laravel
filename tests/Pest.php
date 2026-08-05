<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Block 7 – Pest-Bootstrap. RefreshDatabase gibt jedem Test eine frische,
| migrierte In-Memory-SQLite-Datenbank (schnell & isoliert).
*/
uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');
