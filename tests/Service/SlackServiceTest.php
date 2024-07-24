<?php

namespace App\Tests\Service;

use App\Service\SlackInviteUsers;
use App\Service\SlackService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackServiceTest extends KernelTestCase
{

    private $httpClientMock;
    private $slackInviteUsersMock;
    private $slackService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->slackInviteUsersMock = $this->createMock(SlackInviteUsers::class);
        
        $this->slackService = $container->get(SlackService::class);
        
        $this->slackService->setHttpClient($this->httpClientMock);
        $this->slackService->setSlackInviteUsers($this->slackInviteUsersMock);
    }

    public function testCreateChannelSuccess()
    {
        $channelName = 'testchannel';
        $responseData = ['ok' => true, 'channel' => ['id' => '123456']];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'conversation.create', [
                'body' => json_encode(['name' => $channelName]),
                ])
            ->willReturn($response);

        $result = $this->slackService->createChannel($channelName);

        $this->assertEquals($responseData, $result);
    }

    public function testCreateChannelFailure()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create channel');

        $channelName = 'testchannel';
        $responseData = ['ok' => false, 'error' => 'channel_name_taken'];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'conversation.create', [
                'body' => json_encode(['name' => $channelName]),
                ])
            ->willReturn($response);

        $this->slackService->createChannel($channelName);
    }

    public function testInviteUsersSuccess()
    {
        $channelId = 'C123456';
        $slackIds = ['U123456', 'U789012'];
        $responseData = ['ok' => true];

        $this->slackInviteUsersMock
            ->expects($this->once())
            ->method('inviteUsers')
            ->with($channelId, $slackIds)
            ->willReturn($responseData);

        $this->slackService->slackInviteUsers = $this->slackInviteUsersMock;

        $response = $this->slackService->inviteUsers($channelId, $slackIds);

        $this->assertEquals('Les utilisateurs ont bien été invités sur le nouveau canal slack', $response->getContent());
    }

    public function testInviteUsersFailure()
    {
        $channelId = 'C123456';
        $slackIds = 'U1234567890, U0987654321';
        $responseData = ['ok' => false, 'error' => 'not_in_channel'];

        $this->slackInviteUsersMock
            ->expects($this->once())
            ->method('inviteUsers')
            ->with($channelId, $slackIds)
            ->willReturn($responseData);

        $this->slackService->slackInviteUsers = $this->slackInviteUsersMock;

        $response = $this->slackService->inviteUsers($channelId, $slackIds);

        $this->assertEquals("Les utilisateurs n'ont pas été invités sur le canal", $response->getContent());
    }

    public function testSlackIdsHandler()
    {
        $slackArray = [['U1234567890'], ['U0987654321']];
        $authorSlack = 'U1122334455';

        $result = $this->slackService->slackIdsHandler($slackArray, $authorSlack);

        $expected = 'U1234567890, U0987654321, U1122334455';

        $this->assertEquals($expected, $result);
    }

}
