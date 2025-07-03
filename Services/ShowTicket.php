<?php

namespace Leantime\Plugins\ShowTicket\Services;

use Leantime\Domain\Tickets\Services\Tickets as TicketService;
use Leantime\Core\Support\FromFormat;
use Leantime\Domain\Tickets\Models\Tickets as TicketModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse as JsonResponse;
use Leantime\Domain\Tickets\Repositories\Tickets as TicketRepository;
use Leantime\Domain\Comments\Services\Comments as CommentService;

/**
 * Show ticket services file.
 */
class ShowTicket
{
    private TicketService $ticketService;
    private TicketRepository $ticketRepository;
    private CommentService $commentService;
    /**
     * constructor
     *
     * @param  TicketService $ticketService
     *      * @param TicketRepository  $ticketRepository
     * @return void
     */
    public function __construct(TicketService $ticketService, TicketRepository $ticketRepository, CommentService $commentService)
    {
        $this->ticketService = $ticketService;
        $this->ticketRepository = $ticketRepository;
        $this->commentService = $commentService;
    }

    /**
     * @var array<string, string>
     */
    private static array $assets = [
        // source => target
        __DIR__ . '/../dist/show-ticket.css' => APP_ROOT . '/public/dist/css/show-ticket.css',
        __DIR__ . '/../dist/show-ticket.js' => APP_ROOT . '/public/dist/js/show-ticket.js',
        __DIR__ . '/../dist/create-ticket.js' => APP_ROOT . '/public/dist/js/create-ticket.js',
    ];

    /**
     * Install plugin.
     *
     * @return void
     */
    public function install(): void
    {
        foreach (self::getAssets() as $source => $target) {
            if (file_exists($target)) {
                unlink($target);
            }
            symlink($source, $target);
        }
    }

    /**
     * Uninstall plugin.
     *
     * @return void
     */
    public function uninstall(): void
    {
        foreach (self::getAssets() as $target) {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    /**
     * Transform to leantime times, this is copy pasted from somewhere in leantime.
     *
     * @param string $time the time.
     *
     * @return string i think
     */
    private function transformToLeantimeTimes(?string $time)
    {
        if ($time === null) {
            return '';
        }
        return format(value: $time, fromFormat: FromFormat::User24hTime)->userTime24toUserTime();
    }

    /**
     * Transform to leantime dates, this is copy pasted from somewhere in leantime.
     *
     * @param string $date the date.
     *
     * @return string i think
     */
    private function transformToLeantimeDates(?string $date)
    {
        if ($date === null || $date === '0000-00-00 00:00:00') {
            return '';
        }
        $date = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date);
        return $date->format('d/m/Y');
    }

    /**
     * Transform to leantime dates, this is copy pasted from somewhere in leantime.
     *
     * @param string $id    the id.
     * @param string $key   the key of the value to be changed.
     * @param string $value the value to change.
     *
     * @return JsonResponse The JSON response containing the updated ticket.
     */
    public function saveTicket(string $id, string $key, string $value): JSonResponse
    {
        $ticket = $this->ticketService->getTicket($id);
        $values = [
            'id' => $ticket->id,
            'headline' => $ticket->headline ?? '',
            'type' => $ticket->type ?? '',
            'description' => $ticket->description ?? '',
            'projectId' => $ticket->projectId ?? session('currentProject'),
            'editorId' => $ticket->editorId ?? '',
            // @phpstan-ignore-next-line
            'date' => dtHelper()->userNow()->formatDateTimeForDb(),
            'status' => $ticket->status ?? '',
            'planHours' => $ticket->planHours ?? '',
            'tags' => $ticket->tags ?? '',
            'sprint' => $ticket->sprint ?? '',
            'storypoints' => $ticket->storypoints ?? '',
            'hourRemaining' => $ticket->hourRemaining ?? '',
            'priority' => $ticket->priority ?? '',
            'acceptanceCriteria' => $ticket->acceptanceCriteria ?? '',
            'dependingTicketId' => $ticket->dependingTicketId ?? '',
            'milestoneid' => $ticket->milestoneid ?? '',
            'dateToFinish' => $this->transformToLeantimeDates($ticket->dateToFinish),
            'editTo' => $this->transformToLeantimeDates($ticket->editTo),
            'editFrom'  => $this->transformToLeantimeDates($ticket->editFrom),
            'timeTo' => $this->transformToLeantimeTimes($ticket->timeTo),
            'timeFrom' => $this->transformToLeantimeTimes($ticket->timeFrom),
            'timeToFinish' => $this->transformToLeantimeTimes($ticket->timeToFinish),
        ];

        $values[$key] = $value;
        $result = $this->ticketService->updateTicket($values);

        if ($result) {
            $ticket = $this->ticketService->getTicket($id);
            return response()->json($ticket);
        } else {
            return response()->json(false);
            ;
        }
    }

    /**
     * Transform to leantime dates, this is copy pasted from somewhere in leantime.
     *
     * @param array<string, string> $values the values to save.
     *
     * @return array<mixed,mixed>|int|bool boolean indicating whether it succeeded or not.
     */
    public function createTicket(array $values): array|int|bool
    {
        $values = [
            'headline' => $values['headline'] ?? '',
            'type' => 'task',
            'description' => $values['description'] ?? '',
            'projectId' => $values['projectId'],
            'editorId' => $values['editorId'] ?? '',
            // @phpstan-ignore-next-line
            'date' => dtHelper()->userNow()->formatDateTimeForDb(),
            'status' => $values['status'] ?? '',
            'planHours' => $values['planHours'] ?? '',
            'tags' => $values['tags'] ?? '',
            'sprint' => $values['sprint'] ?? '',
            'storypoints' => $values['storypoints'] ?? '',
            'hourRemaining' => $values['hourRemaining'] ?? '',
            'priority' => $values['priority'] ?? '',
            'acceptanceCriteria' => $values['acceptanceCriteria'] ?? '',
            'dependingTicketId' => $values['dependingTicketId'] ?? '',
            'milestoneid' => $values['milestoneid'] ?? '',
            'dateToFinish' => $values['dateToFinish'],
        ];

        return $this->ticketService->addTicket($values);
    }

    /**
     * Delete ticket.
     *
     * @param string $id the id of the ticket ot delete.
     *
     * @return JsonResponse containing nothing of value i think.
     */
    public function deleteTicket(string $id)
    {
        $result = $this->ticketService->delete($id);
        return response()->json($result);
    }

    /**
     * Delete comment.
     *
     * @param string $id the id of the comment to delete.
     *
     * @return JsonResponse true or false
     */
    public function deleteComment(string $id)
    {
        if ($this->commentService->deleteComment((int)$id)) {
            return response()->json(['result' => true]);
        }
        return response()->json(['result' => false]);
    }
    /**
     * Edit comment.
     *
     * @param mixed $input array with edited text and id.
     *
     * @return JsonResponse true or false
     */
    public function editComment(mixed $input)
    {
        if ($this->commentService->editComment($input, $input['id'])) {
            return response()->json(['result' => true]);
        }
        return response()->json(['result' => false]);
    }
    /**
     * Reply to comment.
     *
     * @param mixed $input array with text, "father", which is the parent task, and id.
     *
     * @return JsonResponse true or false
     */
    public function replyToComment(mixed $input)
    {
        $ticket = $this->ticketService->getTicket($input['father']);
        if ($this->commentService->addComment($input, 'ticket', $input['father'], $ticket)) {
            return response()->json(['result' => true]);
        }
        return response()->json(['result' => false]);
    }

    /**
     * Get assets
     *
     * @return array|string[]
     */
    private static function getAssets(): array
    {
        return self::$assets;
    }

    /**
     * Retrieves a ticket by its ID.
     *
     * @param int $ticketId The ID of the ticket to retrieve.
     *
     * @return TicketModel|bool The ticket model if found, or `false` if not found.
     */
    public function getTicket(int $ticketId): TicketModel|bool
    {
        return $this->ticketService->getTicket($ticketId);
    }

    /**
     * Retrieves the status labels defined in a project.
     *
     * @param int $projectId The ID of the project for which status labels are fetched.
     *
     * @return array An array of status labels for the given project.
     */
    // phpcs:disable
    /** @phpstan-ignore-next-line*/
    public function getStatusLabels(int $projectId): array
    {
        return $this->ticketService->getStatusLabels($projectId);
    }
    // phpcs:enable

    /**
     * Retrieves the subtasks of the ticket.
     *
     * @param int $ticketId The id of the ticket.
     *
     * @return array|false An array of subtasks, or false.
     */
    // phpcs:disable
    /** @phpstan-ignore-next-line*/
    public function getAllSubtasks(int $ticketId): false|array
    {
        return $this->ticketService->getAllSubtasks($ticketId);
    }
    // phpcs:enable

    /**
     * Retrieves the priority labels.
     *
     * @return array|string[] An array of priority labels.
     */
    public function getPriorityLabels(): array
    {
        return $this->ticketService->getPriorityLabels();
    }

    /**
     * Get tags.
     *
     * @param int $projectId the id of the project the tags belong to
     *
     * @return array<string, string> The JSON response containing the list of tags or an empty array.
     */
    public function getTags(int $projectId): array
    {
        $tags = [];
        $ticketTags = $this->ticketRepository->getTags($projectId);
        $tags = $this->explodeAndMergeTags($ticketTags, $tags);
        $uniqueTags = array_unique($tags);
        return $uniqueTags;
    }

    // phpcs:disable
    /** @phpstan-ignore-next-line */
    private function explodeAndMergeTags(array $dbTagValues, array $mergeInto): array
    {
        foreach ($dbTagValues as $tagGroup) {
            if (isset($tagGroup['tags']) && $tagGroup['tags'] != null) {
                $tagArray = explode(',', $tagGroup['tags']);
                $mergeInto = array_merge($tagArray, $mergeInto);
            }
        }

        return $mergeInto;
    }
    // phpcs:enable
}
