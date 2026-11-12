<?php

declare(strict_types=1);

/*
 * Test groups are bound to folders so --group=arch|unit|feature works.
 * Unit tests mirror src/Domain only: business logic is the only thing
 * worth unit-testing here (testing.md).
 */
pest()->group('arch')->in('Arch');
pest()->group('unit')->in('Unit');
pest()->group('feature')->in('Feature');
