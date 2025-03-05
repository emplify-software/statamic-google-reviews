<?php

namespace EmplifySoftware\StatamicGoogleReviews\Tests;

use EmplifySoftware\StatamicGoogleReviews\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
