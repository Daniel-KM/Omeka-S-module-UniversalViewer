<?php

namespace UniversalViewerTest\Controller;

class PlayerControllerTest extends UniversalViewerControllerTestCase
{
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/item/' . $this->item->id() . '/play');

        $this->assertResponseStatusCode(200);
    }
}
