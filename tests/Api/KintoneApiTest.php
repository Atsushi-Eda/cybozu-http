<?php

namespace CybozuHttp\Tests\Api;

require_once __DIR__ . '/../_support/KintoneTestHelper.php';
use PHPUnit\Framework\TestCase;
use KintoneTestHelper;
use CybozuHttp\Api\KintoneApi;

/**
 * @author ochi51 <ochiai07@gmail.com>
 */
class KintoneApiTest extends TestCase
{

    /**
     * @var KintoneApi
     */
    private $api;

    protected function setup()
    {
        $this->api = KintoneTestHelper::getKintoneApi();
    }

    public function testGenerateUrl(): void
    {
        $this->assertEquals('/k/v1/record.json', KintoneApi::generateUrl('record.json'));
        $this->assertEquals('/k/guest/123/v1/record.json', KintoneApi::generateUrl('record.json', 123));
    }

    public function testPostBulkRequest(): void
    {
        $spaceId = KintoneTestHelper::createTestSpace();
        $space = $this->api->space()->get($spaceId);
        $appId = KintoneTestHelper::createTestApp($spaceId, $space['defaultThread']);
        $recordId = KintoneTestHelper::postTestRecord($appId);
        $requests = [
            [
                'method' => 'DELETE',
                'api' => KintoneApi::generateUrl('records.json'),
                'payload' => [
                    'app' => $appId,
                    'ids' => [$recordId]
                ]
            ]
        ];

        $result = $this->api->postBulkRequest($requests);
        $this->assertArrayNotHasKey('message', $result);
    }
}
