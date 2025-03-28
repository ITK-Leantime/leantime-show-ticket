<?php

namespace Leantime\Plugins\ShowTicket\Services;

use Leantime\Domain\Tickets\Services\Tickets as TicketService;
use Leantime\Core\Support\FromFormat;
use Leantime\Domain\Tickets\Models\Tickets as TicketModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse as JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Show ticket services file.
 */
class ShowTicket
{
    private TicketService $ticketService;
    /**
     * constructor
     *
     * @param  TicketService $ticketService
     * @return void
     */
    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * @var array<string, string>
     */
    private static array $assets = [
        // source => target
        __DIR__ . '/../assets/show-ticket.css' => APP_ROOT . '/public/dist/css/show-ticket.css',
        __DIR__ . '/../assets/show-ticket.js' => APP_ROOT . '/public/dist/js/show-ticket.js',
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
     * Retrieves the priority labels.
     *
     * @return array|string[] An array of priority labels.
     */
    public function getPriorityLabels(): array
    {
        return $this->ticketService->getPriorityLabels();
    }
}
