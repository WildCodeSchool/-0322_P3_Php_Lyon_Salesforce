<?php

namespace App\Controller;

use App\Entity\Idea;
use App\Service\SlackService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\User;
use App\Repository\IdeaRepository;

#[IsGranted('ROLE_USER')]
class SlackController extends AbstractController
{
    #[Route('/slack', name: 'app_slack')]
    public function index(): Response
    {
        return $this->render('slack/index.html.twig', [
            'controller_name' => 'SlackController',
        ]);
    }
    #[Route('{id}/createchannel', name: 'create_channel')]
    public function createChannel(
        SlackService $slackService,
        IdeaRepository $ideaRepository,
        SluggerInterface $slugger,
        Idea $idea,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $authorSlack = $user->getSlackId();
        $ideaId = $idea->getId();

        if ($idea->isChannelCreatable()) {
            $slackArray = $ideaRepository->getSupportersSlackId($ideaId);

            $slackIds = $slackService->slackIdsHandler($slackArray, $authorSlack);
            $channelName = $idea->getTitle(); // Set the channel name based on idea name

            $slug = $slugger->slug($channelName, '_'); // Apply the slugger to the channel name
            $slug = strtolower($slug);
            $channel = $slackService->createChannel($slug);
            // Call the createChannel method of SlackService with the specified channel name with slug

            if ($channel['ok']) {
                $channelId = $channel['channel']['id']; // Extract the channel ID from the response

                $slackService->inviteUsers($channelId, $slackIds);

                $idea->setArchived(true);
                $ideaRepository->save($idea, true);

                $this->addFlash('success', "Nouveau canal Slack créé : {$channelName} (ID: {$channelId}).");
                // Create a success message with the channel name and ID
                return $this->redirectToRoute('app_home');
            } else {
                $error = $channel['error']; // Extract the error message from the response
                $this->addFlash('error', "Echec de création du canal Slack : {$error}.");
                // Create an error message with the error's name
            }
        }
        return $this->redirectToRoute('idea_show', ['id' => $ideaId]);
    }
}
