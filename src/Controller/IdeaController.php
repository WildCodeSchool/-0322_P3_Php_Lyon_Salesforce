<?php

namespace App\Controller;

use App\Entity\Idea;
use App\Entity\User;
use App\Form\IdeaType;
use App\Repository\IdeaRepository;
use App\Service\IdeaFormHandler;
use App\Service\ReportingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrap5View;

#[IsGranted('ROLE_USER')]
#[Route('/idea', name: 'idea')]
class IdeaController extends AbstractController
{
    #[Route('/new', name: '_new')]
    public function new(Request $request, IdeaRepository $ideaRepository, IdeaFormHandler $ideaFormHandler): Response
    {

        $result = $ideaFormHandler->formHandler();
        $form = $result['form'];
        $idea = $result['idea'];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ideaRepository->save($idea, true);

            $this->addFlash('success', 'Votre nouvelle idée a été partagée !');

            return $this->redirectToRoute('idea_show', ['id' => $idea->getId()]);
        }

        return $this->render('idea/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(
        Request $request,
        Idea $idea,
        IdeaRepository $ideaRepository,
    ): Response {
        if ($this->getUser() !== $idea->getAuthor()) {
            $this->addFlash('danger', 'Seul l\'auteur d\'une idée peut la modifier');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(IdeaType::class, $idea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ideaRepository->save($idea, true);

            $this->addFlash('success', 'Votre idée a été modifié!');

            return $this->redirectToRoute('idea_show', ['id' => $idea->getId()]);
        }

        return $this->render('idea/edit.html.twig', [
            'idea' => $idea,
            'form' => $form,
        ]);
    }

    #[Route('/MyOffice/{page<\d+>}', name: 's_by_user_office')]
    public function showOffice(IdeaRepository $ideaRepository, int $page = 1): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $officeId = $user->getWorkplace()->getId();

        $ideas = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new ArrayAdapter($ideaRepository->getIdeasByUserOffice($officeId)),
            $page,
            6
        );

        $pagerfanta = new TwitterBootstrap5View();
        $order = 'récentes';
        return $this->render('idea/ideasByUserOffice.html.twig', [
            'order' => $order,
            'user' => $user,
            'ideas' => $ideas,
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route('/MyDepartment/{page<\d+>}', name: 's_by_user_department')]
    public function showDepartment(IdeaRepository $ideaRepository, int $page = 1): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $officeId = $user->getWorkplace()->getId();
        $departmentName = $user->getDepartment();

        $ideas = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new ArrayAdapter($ideaRepository->getIdeasByUserDepartment($officeId, $departmentName)),
            $page,
            6
        );

        $pagerfanta = new TwitterBootstrap5View();
        $order = 'récentes';
        return $this->render('idea/ideasByUserDepartment.html.twig', [
            'order' => $order,
            'user' => $user,
            'ideas' => $ideas,
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route('/{id}', name: '_show')]
    public function show(
        Idea $idea,
        Request $request,
        IdeaRepository $ideaRepository,
        ReportingHandler $reportingHandler,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $supporters = $idea->getSupporters();


        if ($request->get('motive')) {
            $reportingHandler->handleReport($request, $idea, $user);
        }

        if ($idea->isArchived() === true && $user->getRoles() !== ["ROLE_ADMIN"]) {
            $this->addFlash('danger', 'Cette idée est archivé vous ne pouvez plus la visualiser');
            return $this->redirectToRoute('app_home');
        }

        if ($supporters->contains($user)) {
            $isMember = true;
        } else {
            $isMember = false;
        }

        if (
            $request->get('membership')
            && $isMember === false
            && $user->getId() !== $idea->getAuthor()->getId()
        ) {
            $idea->addSupporter($user);
            $ideaRepository->save($idea, true);
            $this->addFlash('success', 'Vous avez bien adhéré à cette idée');
            return $this->redirectToRoute('idea_show', ['id' => $idea->getId()]);
        }

        return $this->render('idea/show.html.twig', [
            'idea' => $idea,
            'isMember' => $isMember,
        ]);
    }
}
