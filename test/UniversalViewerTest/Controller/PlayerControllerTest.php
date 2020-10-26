<?php declare(strict_types=1);

namespace UniversalViewerTest\Controller;

class PlayerControllerTest extends UniversalViewerControllerTestCase
{
    public function testIndexActionCanBeAccessed(): void
    {
        $this->dispatch('/item/' . $this->item->id() . '/play');

        $this->assertResponseStatusCode(200);
    }
}
