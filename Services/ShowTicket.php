<?php

namespace Leantime\Plugins\ShowTicket\Services;

use Leantime\Domain\Tickets\Services\Tickets as TicketService;
use Leantime\Core\Support\FromFormat;
use Leantime\Domain\Tickets\Models\Tickets as TicketModel;
use Carbon\CarbonImmutable;

/**
 * Time table services file.
 */
class ShowTicket
{
    private TicketService $ticketService;
    /**
     * constructor
     *
     * @param  ShowTicketRepository $showTicketRepo
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

    private function transformToLeantimeTimes(?string $time)
    {
        if ($time === null) {
            return '';
        }
        return format(value: $time ?? '', fromFormat: FromFormat::User24hTime)->userTime24toUserTime();
    }
    private function transformToLeantimeDates(?string $date)
    {
        if ($date === null || $date === '0000-00-00 00:00:00') {
            return '';
        }
        $date = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date);
        return $date->format('d/m/Y');
    }

    function saveTicket($id, $key, $value)
    {
        $ticket = $this->ticketService->getTicket($id);

        $values = [
            'id' => $ticket->id,
            'headline' => $ticket->headline ?? '',
            'type' => $ticket->type ?? '',
            'description' => $ticket->description ?? '',
            'projectId' => $ticket->projectId ?? session('currentProject'),
            'editorId' => $ticket->editorId ?? '',
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

    function deleteTicket($id)
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

    public function getTicket(int $ticketId): TicketModel|bool
    {
        return $this->ticketService->getTicket($ticketId);
    }

    /**
     */
    public function getStatusLabels(int $projectId): array
    {
        return $this->ticketService->getStatusLabels($projectId);
    }
    /**
     */
    public function getPriorityLabels(): array
    {
        return $this->ticketService->getPriorityLabels();
    }
}
