<?php

use PHPUnit\Framework\TestCase;
use Http\Mock\Client;

require_once __DIR__ . "/../src/COARNotificationManager.php";
require_once __DIR__ . "/../src/orm/COARNotification.php";

final class COARNotificationManagerTest extends TestCase
{

    public function test_validate_notification(): void
    {
        $this->expectException(COARNotificationException::class);

        $json = '{"@context":["https://www.w3.org/ns/activitystreams","https://purl.org/coar/notify"],"actor":{"id":"https://orcid.org/0000-0002-1825-0097","name":"Josiah Carberry","type":"Person"},"id":"urn:uuid:0370c0fb-bb78-4a9b-87f5-bed307a509dd","object":{"id":"https://research-organisation.org/repository/preprint/201203/421/","ietf:cite-as":"https://doi.org/10.5555/12345680","type":"sorg:AboutPage","url":{"id":"https://research-organisation.org/repository/preprint/201203/421/content.pdf","media-type":"application/pdf","type":["Article","sorg:ScholarlyArticle"]}},"origin":{"id":"https://research-organisation.org/repository","inbox":"https://research-organisation.org/repository/inbox/","type":"Service"},"target":{"id":"https://overlay-journal.com/system","inbox":"https://overlay-journal.com/system/inbox/","type":"Service"},"type":["Offer","coar-notify:ReviewAction"]}';

        $json = json_decode($json,true,512,
            JSON_THROW_ON_ERROR);
        //COARNotificationInbox::fromString('invalid');
        validate_notification($json);
    }

    /*public function testClientReturnsResponse()
    {
        // $firstRequest and $secondRequest are Psr\Http\Message\RequestInterface
        // objects

        $client = new Client();
        $response = $this->createMock('Psr\Http\Message\ResponseInterface');
        $client->addResponse($response);

        // $request is an instance of Psr\Http\Message\RequestInterface
        $returnedResponse = $client->sendRequest($request);
        $this->assertSame($response, $returnedResponse);
        $this->assertSame($request, $client->getLastRequest());
    }*/
}
