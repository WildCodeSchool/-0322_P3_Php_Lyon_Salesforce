<?php

namespace App\Tests\Service;

use App\Service\SlackInviteUsers;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackInviteUsersTest extends KernelTestCase
{
    private $httpClientMock;
    private $slackInviteUsers;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);

        $this->slackInviteUsers = $container->get(SlackInviteUsers::class);

        $this->slackInviteUsers->setHttpClient($this->httpClientMock);
    }

    public function testInviteUsersToChannelSuccess()
    {
        $channelId = 'C1234567890';
        $slackId = 'U1234567890,U0987654321';
        $responseData = ['ok' => true];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'conversations.invite', [
                'body' => json_encode([
                    'channel' => $channelId,
                    'users' => $slackId,
                ]),
            ])
            ->willReturn($response);

        $result = $this->slackInviteUsers->inviteUsersToChannel($channelId, $slackId);

        $this->assertEquals($responseData, $result);
    }

    public function testInviteUsersToChannelFailure()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to invite to channel: user_not_found');

        $channelId = 'C1234567890';
        $slackId = 'U1234567890,U0987654321';
        $responseData = ['ok' => false, 'error' => 'user_not_found'];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'conversations.invite', [
                'body' => json_encode([
                    'channel' => $channelId,
                    'users' => $slackId,
                ]),
            ])
            ->willReturn($response);

        $this->slackInviteUsers->inviteUsersToChannel($channelId, $slackId);
    }
}
