<?php

namespace Leantime\Plugins\ShowTicket\Controllers;

use Leantime\Core\Controller\Frontcontroller;
use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Plugins\ShowTicket\Services\ShowTicket as ShowTicketService;
use Leantime\Core\UI\Template;
use Leantime\Domain\Users\Services\Users as UserService;
use Illuminate\Http\JsonResponse as JsonResponse;
use Leantime\Domain\Sprints\Services\Sprints as SprintService;
use Leantime\Domain\Tickets\Services\Tickets as TicketService;
use Leantime\Domain\Projects\Services\Projects as ProjectService;
use Leantime\Domain\Auth\Services\Auth as AuthService;
use Leantime\Domain\Auth\Models\Roles;

/**
 * CreateTicket controller.
 */
class CreateTicket extends Controller
{
    private ShowTicketService $showTicketService;
    protected Template $template;
    private UserService $userService;
    private SprintService $sprintService;
    private TicketService $ticketService;
    private ProjectService $projectService;

    /**
     * constructor
     *
     * @param ShowTicketService $showTicketService
     * @param ProjectService    $projectService
     * @param TicketService     $ticketService
     * @param SprintService     $sprintService
     * @param UserService       $userService
     * @param Template          $template
     * @return void
     */
    public function init(ShowTicketService $showTicketService, ProjectService $projectService, TicketService $ticketService, SprintService $sprintService, UserService $userService, Template $template): void
    {
        $this->showTicketService = $showTicketService;
        $this->projectService = $projectService;
        $this->ticketService = $ticketService;
        $this->sprintService = $sprintService;
        $this->userService = $userService;
        $this->template = $template;
    }

    /**
     * Saves ticket headline.
     * @param array<string, string[]> $input The input for saving:
     *                     - 'id': The id.
     *                     - 'headline': The headline.
     * @return JsonResponse The JSON response containing the list of tickets or an empty array.
     */
    public function createTicket(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }
        $returnValue = $this->showTicketService->createTicket($input['input']);
        return response()->json(['ticket' => $returnValue]);
    }

    /**
     * Get tags
     *
     * @param string[] $input The input for getting tags:
     *                     - 'id': The project id.
     *
     * @return JsonResponse The JSON response containing the list of tags or an empty array.
     */
    public function getTags(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }
        $tags = $this->showTicketService->getTags((int)$input['projectId']);
        return response()->json(['tags' => $tags]);
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function post(): Response
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return Frontcontroller::redirect(BASE_URL);
        }

        $redirectUrl = BASE_URL . '/ShowTicket/CreateTicket';
        if (isset($_POST['projectId'])) {
            $redirectUrl = $redirectUrl . '?projectId=' . $_POST['projectId'];
        }

        return Frontcontroller::redirect($redirectUrl);
    }

    /**
     * get
     *
     * @return Response
     *
     * @throws \Exception
     * @throws BindingResolutionException
     */
    public function get(): Response
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return Frontcontroller::redirect(BASE_URL);
        }

        $this->template->assign('selectedProjectId', 0);
        $this->template->assign('projectId', $_GET['projectId'] ?? '');

        if (array_key_exists('projectId', $_GET) && $_GET['projectId'] !== null) {
            $statusLabels = $this->showTicketService->getStatusLabels($_GET['projectId']);
            $this->template->assign('statusLabels', $statusLabels);
            $priorityLabels = $this->showTicketService->getPriorityLabels();
            $this->template->assign('priorityLabels', $priorityLabels);
            $this->template->assign('sprints', $this->sprintService->getAllSprints($_GET['projectId']));
            $this->template->assign('allUsers', $this->userService->getAll());
            $milestones = $this->ticketService->getAllMilestones(['sprint' => '', 'type' => 'milestone', 'currentProject' => $_GET['projectId']]);
            $this->template->assign('milestones', $milestones);
        }

        // For reasons unknown, I can get all projects (also the ones to assignet the session user) _but not_
        // get specific projects if they are not assignet to the session user. Perhaps this is an error, but for
        // now we are just fetching all the projects, and if the user has selected a project, I lock that choice by disabling
        // the input. It would probably be better to not get all projects when the user already has selected a project, but oh well.
        $this->template->assign('projects', $this->projectService->getAllProjects());
        // Ticket assigned to the template
        return $this->template->display('ShowTicket.createTicket');
    }
}
