<?php declare(strict_types=1);

namespace UniversalViewerTest\Controller;

use CommonTest\AbstractHttpControllerTestCase;

/**
 * Abstract controller test case for UniversalViewer module.
 *
 * Provides helpers to create test resources for player tests.
 */
abstract class UniversalViewerControllerTestCase extends AbstractHttpControllerTestCase
{
    /**
     * @var int[] IDs of items created during tests (for cleanup).
     */
    protected array $createdItemIds = [];

    /**
     * @var int[] IDs of item sets created during tests (for cleanup).
     */
    protected array $createdItemSetIds = [];

    public function setUp(): void
    {
        parent::setUp();
        // Clear the static user cache to avoid detached entity errors
        // across tests (each test gets a fresh EntityManager).
        self::$adminUser = null;
        // Authenticate so API calls (create/delete) work outside dispatch().
        $this->loginAsAdmin();
    }

    public function tearDown(): void
    {
        foreach ($this->createdItemIds as $id) {
            try {
                $this->api()->delete('items', $id);
            } catch (\Exception $e) {
                // Ignore cleanup errors.
            }
        }
        $this->createdItemIds = [];

        foreach ($this->createdItemSetIds as $id) {
            try {
                $this->api()->delete('item_sets', $id);
            } catch (\Exception $e) {
                // Ignore cleanup errors.
            }
        }
        $this->createdItemSetIds = [];

        parent::tearDown();
    }

    /**
     * Create a public item for testing.
     *
     * @return \Omeka\Api\Representation\ItemRepresentation
     */
    protected function createPublicItem()
    {
        $response = $this->api()->create('items', [
            'o:is_public' => true,
        ]);
        $item = $response->getContent();
        $this->createdItemIds[] = $item->id();
        return $item;
    }

    /**
     * Create a private item for testing.
     *
     * @return \Omeka\Api\Representation\ItemRepresentation
     */
    protected function createPrivateItem()
    {
        $response = $this->api()->create('items', [
            'o:is_public' => false,
        ]);
        $item = $response->getContent();
        $this->createdItemIds[] = $item->id();
        return $item;
    }

    /**
     * Create a public item set for testing.
     *
     * @return \Omeka\Api\Representation\ItemSetRepresentation
     */
    protected function createPublicItemSet()
    {
        $response = $this->api()->create('item_sets', [
            'o:is_public' => true,
        ]);
        $itemSet = $response->getContent();
        $this->createdItemSetIds[] = $itemSet->id();
        return $itemSet;
    }
}
