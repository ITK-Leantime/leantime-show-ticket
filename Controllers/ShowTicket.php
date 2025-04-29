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
use Leantime\Domain\Files\Repositories\Files as FileRepository;
use Leantime\Domain\Auth\Services\Auth as AuthService;
use Leantime\Domain\Auth\Models\Roles;

/**
 * ShowTicket controller.
 */
class ShowTicket extends Controller
{
    private ShowTicketService $showTicketService;
    protected Template $template;
    private UserService $userService;
    private SprintService $sprintService;
    private TicketService $ticketService;
    private FileRepository $filesRepo;

    /**
     * constructor
     *
     * @param ShowTicketService $showTicketService
     * @param TicketService     $ticketService
     * @param SprintService     $sprintService
     * @param UserService       $userService
     * @param Template          $template
     * @param FileRepository    $filesRepo
     * @return void
     */
    public function init(ShowTicketService $showTicketService, TicketService $ticketService, SprintService $sprintService, UserService $userService, Template $template, FileRepository $filesRepo): void
    {
        $this->showTicketService = $showTicketService;
        $this->ticketService = $ticketService;
        $this->sprintService = $sprintService;
        $this->userService = $userService;
        $this->template = $template;
        $this->filesRepo = $filesRepo;
    }

    /**
     * Saves ticket headline.
     * @param string[] $input The input for saving:
     *                     - 'id': The id.
     *                     - 'headline': The headline.
     * @return JsonResponse The JSON response containing the list of tickets or an empty array.
     */
    public function saveTicket(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }
        $saveResult = $this->showTicketService->saveTicket($input['id'], $input['key'], $input['value']);
        return response()->json(['ticket' => $saveResult]);
    }

    /**
     * Saves ticket headline.
     * @param string[] $input The input for saving:
     *                     - 'id': The id.
     *                     - 'headline': The headline.
     * @return JsonResponse The JSON response containing the list of tickets or an empty array.
     */
    public function deleteTicket(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }
        $deleteResult = $this->showTicketService->deleteTicket($input['id']);
        return response()->json(['ticket' => $deleteResult]);
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
            return  response()->json(['error' => true]);
        }
        $redirectUrl = BASE_URL . '/ShowTicket/ShowTicket';
        if (isset($_POST['ticket-id'])) {
                $redirectUrl = $redirectUrl . '?ticketId=' . $_POST['ticket-id'];
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
            return  response()->json(['error' => true]);
        }
        $this->template->assign('ticketExists', false);
        $ticket = null;
        if (array_key_exists('ticketId', $_GET) && $_GET['ticketId'] !== null) {
            $ticket = $this->showTicketService->getTicket($_GET['ticketId']);
            $this->template->assign('ticketIdFromUrl', $_GET['ticketId']);

            if ($ticket) {
                $statusLabels = $this->showTicketService->getStatusLabels($ticket->projectId);
                $this->template->assign('statusLabels', $statusLabels);

                $priorityLabels = $this->showTicketService->getPriorityLabels();
                $this->template->assign('priorityLabels', $priorityLabels);

                $sprints = $this->sprintService->getAllSprints($ticket->projectId);
                $this->template->assign('sprints', $sprints);

                $users = $this->userService->getAll();
                $this->template->assign('allUsers', $users);

                $milestones = $this->ticketService->getAllMilestones(['sprint' => '', 'type' => 'milestone', 'currentProject' => $ticket->projectId]);
                $this->template->assign('milestones', $milestones);

                $relatedTickets = $this->ticketService->getAllPossibleParents($ticket);
                $this->template->assign('relatedTickets', $relatedTickets);

                $files = $this->filesRepo->getFilesByModule('ticket', $ticket->id);
                $this->template->assign('files', $files);

                $subtasks = $this->showTicketService->getAllSubtasks($ticket->id);
                $this->template->assign('subtasks', $subtasks);

                $this->template->assign('ticketExists', true);
                $this->template->assign('ticket', $ticket);
            }
        }

        return $this->template->display('ShowTicket.showTicket');
    }
}
