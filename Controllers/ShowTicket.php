<?php

namespace Leantime\Plugins\ShowTicket\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Leantime\Plugins\ShowTicket\Services\ShowTicket as ShowTicketService;
use Leantime\Core\Language as LanguageCore;
use Leantime\Core\UI\Template;
use Leantime\Domain\Users\Services\Users as UserService;
use Illuminate\Http\JsonResponse as JsonResponse;
use Leantime\Domain\Sprints\Services\Sprints as SprintService;
use Leantime\Domain\Tickets\Services\Tickets as TicketService;
use Leantime\Domain\Tickets\Repositories\Tickets as TicketRepository;
use Leantime\Domain\Files\Repositories\Files as FileRepository;

/**
 * ShowTicket controller.
 */
class ShowTicket extends Controller
{
    private ShowTicketService $showTicketService;
    protected LanguageCore $language;
    protected Template $template;
    private UserService $userService;
    private SprintService $sprintService;
    private TicketService $ticketService;
    private TicketRepository $ticketRepository;
    private FileRepository $filesRepo;

    /**
     * constructor
     *
     * @param showTicketService $showTicketService
     * @param TicketService     $ticketService
     * @param UserService       $userService
     * @param LanguageCore      $language
     * @param Template          $template
     * @param TicketRepository  $ticketRepository
     * @return void
     */
    public function init(ShowTicketService $showTicketService, TicketService $ticketService, SprintService $sprintService, UserService $userService, LanguageCore $language, Template $template, TicketRepository $ticketRepository, FileRepository $filesRepo): void
    {
        $this->showTicketService = $showTicketService;
        $this->ticketService = $ticketService;
        $this->sprintService = $sprintService;
        $this->language = $language;
        $this->userService = $userService;
        $this->template = $template;
        $this->ticketRepository = $ticketRepository;
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
        $deleteResult = $this->showTicketService->deleteTicket($input['id']);
        return response()->json(['ticket' => $deleteResult]);
    }


        /**
         * @api
         */
    private function explodeAndMergeTags($dbTagValues, array $mergeInto): array
    {
        foreach ($dbTagValues as $tagGroup) {
            if (isset($tagGroup['tags']) && $tagGroup['tags'] != null) {
                $tagArray = explode(',', $tagGroup['tags']);
                $mergeInto = array_merge($tagArray, $mergeInto);
            }
        }

        return $mergeInto;
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
        $ticket = null;
        if (array_key_exists('ticketId', $_GET)) {
            $ticket = $this->showTicketService->getTicket($_GET['ticketId']);

            if ($ticket) {
                $tags = [];
                $ticketTags = $this->ticketRepository->getTags($ticket->projectId);
                $tags = $this->explodeAndMergeTags($ticketTags, $tags);
                $uniqueTags = array_unique($tags);
                $statusLabels = $this->showTicketService->getStatusLabels($ticket->projectId);
                $priorityLabels = $this->showTicketService->getPriorityLabels();
                $this->template->assign('ticket', $ticket);
                $this->template->assign('statusLabels', $statusLabels);
                $this->template->assign('priorityLabels', $priorityLabels);
                $this->tpl->assign('sprints', $this->sprintService->getAllSprints($ticket->projectId));
                $this->tpl->assign('allUsers', $this->userService->getAll());
                $this->tpl->assign('relatedTickets', $this->ticketService->getAllPossibleParents($ticket));
                $this->tpl->assign('tags', $uniqueTags);
                $milestones = $this->ticketService->getAllMilestones(['sprint' => '', 'type' => 'milestone', 'currentProject' => $ticket->projectId]);
                $this->tpl->assign('milestones', $milestones);
                $this->tpl->assign('files', $this->filesRepo->getFilesByModule('ticket', $ticket->id));
            }
            $this->tpl->assign('ticketIdFromUrl', $_GET['ticketId']);
        }
        // Ticket assigned to the template
        return $this->template->display('ShowTicket.showTicket');
    }
}
