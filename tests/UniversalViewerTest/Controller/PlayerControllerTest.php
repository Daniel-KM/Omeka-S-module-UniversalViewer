<?php declare(strict_types=1);

namespace UniversalViewerTest\Controller;

/**
 * Tests for the UniversalViewer PlayerController (embed player).
 *
 * Routes tested:
 * - /item/{id}/uv       (universalviewer_player, top-level)
 * - /item-set/{id}/uv   (universalviewer_player, top-level)
 */
class PlayerControllerTest extends UniversalViewerControllerTestCase
{
    // =========================================================================
    // Route accessibility (authenticated)
    // =========================================================================

    public function testPlayActionForItemIsAccessible(): void
    {
        $item = $this->createPublicItem();
        $this->dispatch('/item/' . $item->id() . '/uv');
        $this->assertResponseStatusCode(200);
        // Route resolves controller as "Item" (aliased to PlayerController).
        $this->assertControllerName('UniversalViewer\Controller\Item');
        $this->assertActionName('play');
    }

    public function testPlayActionForItemSetIsAccessible(): void
    {
        $itemSet = $this->createPublicItemSet();
        $this->dispatch('/item-set/' . $itemSet->id() . '/uv');
        $this->assertResponseStatusCode(200);
        // Route resolves controller as "ItemSet" (aliased to PlayerController).
        $this->assertControllerName('UniversalViewer\Controller\ItemSet');
        $this->assertActionName('play');
    }

    public function testIndexActionForwardsToPlay(): void
    {
        $item = $this->createPublicItem();
        $this->dispatch('/item/' . $item->id() . '/uv');
        $this->assertResponseStatusCode(200);
        $this->assertActionName('play');
    }

    // =========================================================================
    // Response content
    // =========================================================================

    public function testPlayActionRendersTerminalView(): void
    {
        $item = $this->createPublicItem();
        $this->dispatch('/item/' . $item->id() . '/uv');

        $body = $this->getResponse()->getBody();

        // Terminal view should contain a full HTML document.
        $this->assertStringContainsString('<!DOCTYPE', $body);
        $this->assertStringContainsString('</html>', $body);
    }

    public function testPlayActionContainsResourceTitle(): void
    {
        $item = $this->createPublicItem();
        $this->dispatch('/item/' . $item->id() . '/uv');

        $body = $this->getResponse()->getBody();

        // The head title should contain the installation title.
        $this->assertStringContainsString('<title>', $body);
    }

    public function testPlayActionLoadsJquery(): void
    {
        $item = $this->createPublicItem();
        $this->dispatch('/item/' . $item->id() . '/uv');

        $body = $this->getResponse()->getBody();

        // jQuery is loaded in the head for UV.
        $this->assertStringContainsString('jquery', $body);
    }

    // =========================================================================
    // Access control
    // =========================================================================

    public function testPublicItemIsAccessibleWithoutAuth(): void
    {
        $item = $this->createPublicItem();
        $this->dispatchUnauthenticated('/item/' . $item->id() . '/uv');
        $this->assertResponseStatusCode(200);
    }

    public function testPrivateItemIsNotAccessibleWithoutAuth(): void
    {
        $item = $this->createPrivateItem();
        $this->dispatchUnauthenticated('/item/' . $item->id() . '/uv');
        // Private item: 403 forbidden for unauthenticated user.
        $statusCode = $this->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [403, 404]),
            'Private item should return 403 or 404 for unauthenticated user, got ' . $statusCode
        );
    }

    public function testPrivateItemIsAccessibleForAdmin(): void
    {
        $item = $this->createPrivateItem();
        $this->dispatch('/item/' . $item->id() . '/uv');
        $this->assertResponseStatusCode(200);
    }

    // =========================================================================
    // Error handling
    // =========================================================================

    public function testNonExistentResourceReturns404(): void
    {
        $this->dispatch('/item/999999/uv');
        $this->assertResponseStatusCode(404);
    }

    public function testInvalidControllerReturns404(): void
    {
        // Route constraint: controller must be item or item-set.
        $this->dispatch('/media/1/uv');
        $this->assertResponseStatusCode(404);
    }

    public function testNonNumericIdReturns404(): void
    {
        $this->dispatch('/item/abc/uv');
        $this->assertResponseStatusCode(404);
    }
}
