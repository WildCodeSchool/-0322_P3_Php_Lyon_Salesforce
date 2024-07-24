<?php

namespace App\Tests\Service;

use App\Service\SlackInviteUsers;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackInviteUsersTest extends KernelTestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|HttpClientInterface $httpClientMock;
    private SlackInviteUsers $slackInviteUsers;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->httpClientMock = $this->createMock(HttpClientInterface::class);

        $this->slackInviteUsers = $container->get(SlackInviteUsers::class);

        $reflection = new \ReflectionClass($this->slackInviteUsers);
        $httpClientProperty = $reflection->getProperty('client');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->slackInviteUsers, $this->httpClientMock);
    }

    public function testInviteUsersToChannelSuccess(): void
    {
        $channelId = 'C1234567890';
        $slackId = 'U1234567890,U0987654321';
        $responseData = ['ok' => true];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://slack.com/api/conversations.invite', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['SLACK_OAUTH_TOKEN'],
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'channel' => $channelId,
                    'users' => $slackId,
                ]),
            ])
            ->willReturn($response);

        $result = $this->slackInviteUsers->inviteUsersToChannel($channelId, $slackId);

        $this->assertEquals($responseData, $result);
    }

    public function testInviteUsersToChannelFailure(): void
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
            ->with('POST', 'https://slack.com/api/conversations.invite', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['SLACK_OAUTH_TOKEN'],
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'channel' => $channelId,
                    'users' => $slackId,
                ]),
            ])
            ->willReturn($response);

        $this->slackInviteUsers->inviteUsersToChannel($channelId, $slackId);
    }
}
