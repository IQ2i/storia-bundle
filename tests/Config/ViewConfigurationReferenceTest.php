<?php

/*
 * This file is part of the UI Storia project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\StoriaBundle\Tests\Config;

use IQ2i\StoriaBundle\Config\ViewConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;

/**
 * The YAML format of a view is part of the public API: a change of the configuration tree
 * must be deliberate. Regenerate the fixture with UPDATE_REFERENCE=1 after such a change.
 */
class ViewConfigurationReferenceTest extends TestCase
{
    private const string FIXTURE = __DIR__.'/fixtures/view_configuration.yaml';

    public function testTheConfigurationTreeIsUnchanged(): void
    {
        $reference = (new YamlReferenceDumper())->dump(new ViewConfiguration());

        if (filter_var(getenv('UPDATE_REFERENCE'), \FILTER_VALIDATE_BOOL)) {
            file_put_contents(self::FIXTURE, $reference);
        }

        $this->assertStringEqualsFile(self::FIXTURE, $reference, 'The YAML format of a view changed. If it is deliberate, run the tests with UPDATE_REFERENCE=1 and document it in the changelog.');
    }
}
