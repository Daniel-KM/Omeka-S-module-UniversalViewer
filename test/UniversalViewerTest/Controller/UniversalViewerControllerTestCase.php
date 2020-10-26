<?php declare(strict_types=1);

namespace UniversalViewerTest\Controller;

use OmekaTestHelper\Controller\OmekaControllerTestCase;

abstract class UniversalViewerControllerTestCase extends OmekaControllerTestCase
{
    protected $item;

    public function setUp(): void
    {
        $this->loginAsAdmin();

        $response = $this->api()->create('items');
        $this->item = $response->getContent();
    }

    public function tearDown(): void
    {
        $this->api()->delete('items', $this->item->id());
    }
}
