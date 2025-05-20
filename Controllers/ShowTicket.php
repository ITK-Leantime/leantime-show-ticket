<?php

namespace Leantime\Plugins\ShowTicket\Controllers;

use Leantime\Core\Controller\Frontcontroller;
use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Plugins\ShowTicket\Services\ShowTicket as ShowTicketService;
use Leantime\Plugins\ShowTicket\Repositories\ShowTicket as ShowTicketRepository;
use Leantime\Core\UI\Template;
use Leantime\Domain\Users\Services\Users as UserService;
use Leantime\Domain\Comments\Repositories\Comments as CommentRepository;
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
    private CommentRepository $commentRepository;
    private ShowTicketRepository $showTicketRepository;

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
    public function init(ShowTicketService $showTicketService, TicketService $ticketService, SprintService $sprintService, UserService $userService, Template $template, FileRepository $filesRepo, CommentRepository $commentRepository, ShowTicketRepository $showTicketRepository): void
    {
        $this->showTicketService = $showTicketService;
        $this->ticketService = $ticketService;
        $this->sprintService = $sprintService;
        $this->userService = $userService;
        $this->template = $template;
        $this->filesRepo = $filesRepo;
        $this->commentRepository = $commentRepository;
        $this->showTicketRepository = $showTicketRepository;
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
     * Deletes ticket.
     * @param string[] $input The input for saving:
     *                     - 'id': The id.
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
     * Deletes comment.
     * @param string[] $input The input for saving:
     *                     - 'id': The id.
     * @return JsonResponse The JSON response containing a boolean.
     */
    public function deleteComment(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }
        $deleteResult = $this->showTicketService->deleteComment($input['id']);
        return response()->json(['ticket' => $deleteResult]);
    }
    /**
     * Edit comment.
     * @param string[] $input The input for saving:
     *                     - 'id': The id.
     * @return JsonResponse The JSON response containing a boolean.
     */
    public function editComment(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }

        $editResult = $this->showTicketService->editComment($input);
        return response()->json(['ticket' => $editResult]);
    }
    /**
     * Reply to comment.
     * @param string[] $input The input for saving:
     *                     - 'id': The id.
     * @return JsonResponse The JSON response containing a boolean.
     */
    public function replyToComment(array $input): JsonResponse
    {
        if (!AuthService::userIsAtLeast(Roles::$editor)) {
            return  response()->json(['error' => true]);
        }

        $editResult = $this->showTicketService->replyToComment($input);
        return response()->json(['ticket' => $editResult]);
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

                $comments = $this->commentRepository->getComments('ticket', $ticket->id);

                // In leantime, a comment has 'replies', which means in the database, there a comments
                // that have a "commentParent" set to the comments id, and these are found through the
                // commentrepo->getReplies
                foreach ($comments as &$comment) {
                    $comment['replies'] = [];
                    $replies = $this->commentRepository->getReplies($comment['id']);
                    foreach ($replies as &$reply) {
                        // Display date, this can probably be done in a better way...
                        $reply['display_date'] = dtHelper()->parseDbDateTime($reply['date'])->setToUserTimezone()->format('Y-m-d H:i:s');
                        $reply['editable'] = session('userdata.id') === $reply['userId'];
                    }
                    $comment['replies'] = $replies;
                    $comment['editable'] = session('userdata.id') === $comment['userId'];
                    $comment['display_date'] = dtHelper()->parseDbDateTime($comment['date'])->setToUserTimezone()->format('Y-m-d H:i:s');
                }

                $worklogs = $this->showTicketRepository->getTimesheetsByTicket($ticket->id);

                foreach ($worklogs as &$worklog) {
                    // Display date, this can probably be done in a better way...
                    $worklog['display_date'] = dtHelper()->parseDbDateTime($worklog['workdate'])->setToUserTimezone()->format('Y-m-d');

                    // Username for display
                    $userId = $worklog['userid'];
                    $userThatLogged = array_column($users, null, 'id')[$userId] ?? false;
                    $worklog['display_name'] = $userThatLogged ? $userThatLogged['firstname'] . ' ' . $userThatLogged['lastname'] : 'Ukendt bruger';
                }

                $this->template->assign('comments', $comments);
                $this->template->assign('worklogs', $worklogs);

                $this->template->assign('ticketExists', true);
                $this->template->assign('ticket', $ticket);
            }
        }

        return $this->template->display('ShowTicket.showTicket');
    }
}
