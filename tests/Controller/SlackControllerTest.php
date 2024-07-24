<?php

namespace App\Tests\Controller;

use App\Entity\Idea;
use App\Entity\User;
use App\Repository\IdeaRepository;
use App\Service\SlackService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlackControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private \PHPUnit\Framework\MockObject\MockObject|SlackService $slackServiceMock;
    private \PHPUnit\Framework\MockObject\MockObject|IdeaRepository $ideaRepositoryMock;
    private \PHPUnit\Framework\MockObject\MockObject|SluggerInterface $sluggerMock;
    private \PHPUnit\Framework\MockObject\MockObject|Security $securityMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->slackServiceMock = $this->createMock(SlackService::class);
        $this->ideaRepositoryMock = $this->createMock(IdeaRepository::class);
        $this->sluggerMock = $this->createMock(SluggerInterface::class);
        $this->securityMock = $this->createMock(Security::class);
    }

    public function testIndex(): void
    {
        $this->client->request('GET', '/slack');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'SlackController');
    }

    public function testCreateChannelSuccess(): void
    {
        $user = new User();
        $user->setSlackId('U1234567890');
        $idea = new Idea();
        $idea->setTitle('Test Idea');
        $idea->setId(1);
        $idea->isChannelCreatable();

        $slackArray = [['U0987654321'], ['U1234567890']];
        $slackIds = 'U1234567, U0987654, U1245780';

        $this->ideaRepositoryMock
            ->expects($this->once())
            ->method('getSupportersSlackId')
            ->with(1)
            ->willReturn($slackArray);

        $this->slackServiceMock
            ->expects($this->once())
            ->method('slackIdsHandler')
            ->with($slackArray, $user->getSlackId())
            ->willReturn($slackIds);

            $this->slackServiceMock
            ->expects($this->once())
            ->method('createChannel')
            ->with('test_idea')
            ->willReturn(['ok' => true, 'channel' => ['id' => 'C123456']]);

        $this->slackServiceMock
            ->expects($this->once())
            ->method('inviteUsers')
            ->with('C123456', $slackIds);

        $this->ideaRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($idea, true);

        $this->client->getContainer()->set('app.service.slack_service', $this->slackServiceMock);
        $this->client->getContainer()->set('app.repository.idea_repository', $this->ideaRepositoryMock);
        $this->client->getContainer()->set('security.helper', $this->securityMock);
        $this->client->getContainer()->set('slugger', $this->sluggerMock);

        $this->client->loginUser($user);
        $this->client->request('GET', '/1/createchannel');

        $this->assertResponseRedirects('/app_home');
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-success');
        $this->assertSelectorTextContains('.flash-success', 'Nouveau canal Slack créé : Test Idea (ID: C123456).');
    }

    public function testCreateChannelFailure(): void
    {
        $user = new User();
        $user->setSlackId('U123456');
        $idea = new Idea();
        $idea->setTitle('Test Idea');
        $idea->setId(1);
        $idea->isChannelCreatable();

        $slackArray = [['U1234567890'], ['U0987654321']];
        $slackIds = 'U1234567890, U0987654321, U123456';

        $this->ideaRepositoryMock
            ->expects($this->once())
            ->method('getSupportersSlackId')
            ->with($idea->getId())
            ->willReturn($slackArray);

        $this->slackServiceMock
            ->expects($this->once())
            ->method('slackIdsHandler')
            ->with($slackArray, $user->getSlackId())
            ->willReturn($slackIds);

        $this->sluggerMock
            ->expects($this->once())
            ->method('slug')
            ->with($idea->getTitle(), '_')
            ->willReturn('test_idea');

        $this->slackServiceMock
            ->expects($this->once())
            ->method('createChannel')
            ->with('test_idea')
            ->willReturn(['ok' => false, 'error' => 'name_taken']);

        $this->client->getContainer()->set('app.service.slack_service', $this->slackServiceMock);
        $this->client->getContainer()->set('app.repository.idea_repository', $this->ideaRepositoryMock);
        $this->client->getContainer()->set('security.helper', $this->securityMock);
        $this->client->getContainer()->set('slugger', $this->sluggerMock);

        $this->client->loginUser($user);
        $this->client->request('GET', '/1/createchannel');

        $this->assertResponseRedirects('/idea/1');
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-error');
        $this->assertSelectorTextContains('.flash-error', 'Echec de création du canal Slack : name_taken.');
    }
}
