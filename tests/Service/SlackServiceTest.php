<?php

namespace App\Tests\Service;

use App\Service\SlackInviteUsers;
use App\Service\SlackService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackServiceTest extends KernelTestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|HttpClientInterface $httpClientMock;
    private \PHPUnit\Framework\MockObject\MockObject|SlackInviteUsers $slackInviteUsersMock;
    private SlackService $slackService;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->slackInviteUsersMock = $this->createMock(SlackInviteUsers::class);

        $this->slackService = new SlackService();

        $reflection = new \ReflectionClass($this->slackService);

        $httpClientProperty = $reflection->getProperty('client');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->slackService, $this->httpClientMock);

        $slackInviteUsersProperty = $reflection->getProperty('slackInviteUsers');
        $slackInviteUsersProperty->setAccessible(true);
        $slackInviteUsersProperty->setValue($this->slackService, $this->slackInviteUsersMock);
    }

    public function testCreateChannelSuccess(): void
    {
        $channelName = 'testchannel';
        $responseData = ['ok' => true, 'channel' => ['id' => '123456']];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://slack.com/api/conversations.create', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['SLACK_OAUTH_TOKEN'],
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode(['name' => $channelName]),
            ])
            ->willReturn($response);

        $result = $this->slackService->createChannel($channelName);

        $this->assertEquals($responseData, $result);
    }

    public function testCreateChannelFailure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create channel');

        $channelName = 'testchannel';
        $responseData = ['ok' => false, 'error' => 'channel_name_taken'];
        $response = new MockResponse(json_encode($responseData), ['http_code' => 200]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://slack.com/api/conversations.create', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['SLACK_OAUTH_TOKEN'],
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode(['name' => $channelName]),
            ])
            ->willReturn($response);

        $this->slackService->createChannel($channelName);
    }

    public function testInviteUsersSuccess(): void
    {
        $channelId = 'C123456';
        $slackIds = ['U123456', 'U789012'];
        $responseData = ['ok' => true];

        $this->slackInviteUsersMock
            ->expects($this->once())
            ->method('inviteUsers')
            ->with($channelId, implode(', ', $slackIds))
            ->willReturn($responseData);

        $response = $this->slackService->inviteUsers($channelId, implode(', ', $slackIds));

        $this->assertEquals(
            'Les utilisateurs ont bien été invités sur le nouveau canal slack',
            $response->getContent()
        );
    }

    public function testInviteUsersFailure(): void
    {
        $channelId = 'C123456';
        $slackIds = 'U1234567890, U0987654321';
        $responseData = ['ok' => false, 'error' => 'not_in_channel'];

        $this->slackInviteUsersMock
            ->expects($this->once())
            ->method('inviteUsers')
            ->with($channelId, $slackIds)
            ->willReturn($responseData);

        $response = $this->slackService->inviteUsers($channelId, $slackIds);

        $this->assertEquals("Les utilisateurs n'ont pas été invités sur le canal", $response->getContent());
    }

    public function testSlackIdsHandler(): void
    {
        $slackArray = [['U1234567890'], ['U0987654321']];
        $authorSlack = 'U1122334455';

        $result = $this->slackService->slackIdsHandler($slackArray, $authorSlack);

        $expected = 'U1234567890, U0987654321, U1122334455';

        $this->assertEquals($expected, $result);
    }
}
