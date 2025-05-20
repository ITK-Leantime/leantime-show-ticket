@extends($layout)
@section('content')
    <div class="maincontent">
        <div class="maincontentinner">
            <div class="ticket">
                <div class="delete-modal" id="delete-modal">
                    <div class="modal-content">
                        <p>{{ __('showTicket.confirm-delete-text') }}</p>
                        <div class="modal-buttons">
                            <button class="cancel-delete">{{ __('showTicket.modal-cancel') }}</button>
                            <button class="confirm-delete">{{ __('showTicket.delete-modal-confirm') }}</button>
                        </div>
                    </div>
                </div>
                <div class="edit-modal" id="edit-comment-modal">
                    <div class="modal-content">
                        <textarea type="text" class="textarea" id="comment-input"></textarea>
                        <div class="modal-buttons">
                            <button class="cancel-edit">{{ __('showTicket.modal-cancel') }}</button>
                            <button class="confirm-edit">{{ __('showTicket.edit-modal-confirm') }}</button>
                        </div>
                    </div>
                </div>
                <div class="reply-modal" id="reply-comment-modal">
                    <div class="modal-content">
                        <textarea type="text" class="textarea" id="reply-input"></textarea>
                        <div class="modal-buttons">
                            <button class="cancel-reply">{{ __('showTicket.modal-cancel') }}</button>
                            <button class="confirm-reply">{{ __('showTicket.reply-modal-confirm') }}</button>
                        </div>
                    </div>
                </div>
                <div class="ticket-header">
                    <div class="ticket-notification">
                        {{ __('showTicket.auto-save-on') }}
                        <i id="spinner" class="spinner fa-solid fa-spinner fa-spin-pulse"></i>
                    </div>
                    <div class="ticket-buttons">
                        @if ($ticketExists)
                            <a class="button" href="<?= BASE_URL ?>#/tickets/showTicket/{{ $ticketIdFromUrl }}`">
                                <span class="sr-only">
                                    {{ __('showTicket.open-task-leantime') }}
                                </span>
                                <i class="fa fa-l"></i>
                            </a>
                        @endif
                        <a class="button" href="<?= BASE_URL ?>/ShowTicket/createTicket">
                            <span class="sr-only">
                                {{ __('showTicket.create-ticket') }}
                            </span>
                            <i class="fa fa-plus"></i>
                        </a>
                        <button type="button" class="button" id="copy-url-button">
                            <span class="sr-only">
                                {{ __('showTicket.copy-current-url') }}
                            </span>
                            <i class="fa fa-link"></i>
                        </button>
                        @if ($ticketExists)
                            <button id="delete-ticket" class="button animate-button" type="button">
                                <span class="sr-only">{{ __('showTicket.delete-ticket') }}</span>
                                <i class="fa fa-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>
                @if (!isset($ticketIdFromUrl))
                    <form method="POST">
                        <main class="ticket-content">
                            <h1>
                                {!! sprintf(__('showTicket.ticket-id')) !!}
                            </h1>
                            <label class="sr-only" for="ticket-id">
                                {{ __('showTicket.ticket-id') }}
                            </label>
                            <div class="find-ticket-container">
                                <input type="number" name="ticket-id" class="input" id="ticket-id" />
                                <button class="button" type="submit">{{ __('showTicket.find-ticket') }}
                                </button>
                            </div>
                        </main>
                    </form>
                @endif
                @if (!isset($ticket) && isset($ticketIdFromUrl))
                    <main class="ticket-content">
                        <h1>
                            {!! sprintf(__('showTicket.no-ticket'), $ticketIdFromUrl) !!}
                        </h1>
                    </main>
                @endif
                @if (isset($ticket) && isset($ticketIdFromUrl))
                    <main id="{{ $ticket->id }}" project-id="{{ $ticket->projectId }}" class="ticket-content">
                        <div class="input-container">
                            <h1>{{ $ticket->projectName }}: {{ $ticket->id }}</h1>
                            <label class="sr-only" for="headline-input">
                                {{ __('showTicket.headline-label') }}
                            </label>
                            <input defaultValue="{{ $ticket->headline }}" type="text" class="input" id="headline-input"
                                value='{{ $ticket->headline }}' />
                            <label class="sr-only" for="description-input">
                                {{ __('showTicket.description-label') }}
                            </label>
                            {{-- The below is a wrapper to show the success/error animation, it could be improved --}}
                            <div class="rich-text-success" id="rich-text-success">
                                {{-- this textarea will be a rich text editor, init in show-ticket.js --}}
                                <textarea defaultValue="{{ $ticket->description }}" type="text" class="textarea"
                                    id="description-input">{{ $ticket->description }}</textarea>
                            </div>
                            <div class="label-input-container">
                                <label for="status-select">{{ __('showTicket.status-label') }}</label>
                                <select defaultValue="{{ $ticket->status }}" id="status-select" class="select">
                                    @foreach ($statusLabels as $key => $statusLabel)
                                        <option value="{{ $key }}"
                                            {{ $ticket->status == $key ? 'selected' : '' }}>
                                            {{ $statusLabel['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="label-input-container">
                                <label for="plan-hours-input">{{ __('showTicket.plan-hours-label') }}</label>
                                <input defaultValue="{{ $ticket->planHours }}" type="text" class="input"
                                    id="plan-hours-input" value='{{ $ticket->planHours }}' />
                            </div>
                            <div class="label-input-container">
                                <label for="priority-select">{{ __('showTicket.priority-label') }}</label>
                                <select defaultValue="{{ $ticket->priority }}" id="priority-select" class="select">
                                    <option value="0" {{ $ticket->priority == 0 ? 'selected' : '' }}>
                                        {{ __('showTicket.no-priority-set-option') }}
                                    </option>
                                    @foreach ($priorityLabels as $key => $priorityLabel)
                                        <option value="{{ $key }}"
                                            {{ $ticket->priority == $key ? 'selected' : '' }}>
                                            {{ $priorityLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="label-input-container">
                                <label for="user-select">{{ __('showTicket.editor-label') }}</label>
                                <select defaultValue="{{ $ticket->editorId }}" class="select" id="user-select">
                                    @foreach ($allUsers as $user)
                                        <option value={{ $user['id'] }}
                                            {{ $ticket->editorId == $user['id'] ? 'selected' : '' }}>
                                            {{ $user['firstname'] }}
                                            {{ $user['lastname'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @php
                                $dateToFinish = $ticket->dateToFinish == '0000-00-00 00:00:00' ? '' : strtok($ticket->dateToFinish, ' ');
                            @endphp
                            <div class="label-input-container">
                                <label for="date-to-finish-input">{{ __('showTicket.date-to-finish-label') }}</label>
                                <input type="date" defaultValue="{{ $ticket->dateToFinish }}" type="text" class="input"
                                    id="date-to-finish-input" value='{{ $dateToFinish }}' />
                            </div>
                            <div class="label-input-container">
                                <label for="sprint-select">{{ __('showTicket.sprint-label') }}</label>
                                <select defaultValue="{{ $ticket->sprint }}" class="select" id="sprint-select">
                                    <option value="0">{{ __('showTicket.no-sprint-set-option') }}</option>
                                    @foreach ($sprints as $sprint)
                                        <option value={{ $sprint->id }}
                                            {{ $ticket->sprint == $sprint->id ? 'selected' : '' }}>
                                            {{ $sprint->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="label-input-container" id="tags-container">
                                <label for="tags-select">{{ __('showTicket.tags-label') }}</label>
                                <select id="tags-select" class="input"></select>
                                <div id="skeleton-input" class="skeleton-input">{{ __('showTicket.loading-tags') }}</div>
                            </div>
                            <input id="selected-tags" value="{{ $ticket->tags }}" type="hidden">
                            <div class="label-input-container">
                                <label for="milestone-select">{{ __('showTicket.milestone-label') }}</label>
                                <select defaultValue="{{ $ticket->milestoneid }}" class="select" id="milestone-select">
                                    <option value="0">{{ __('showTicket.no-milestone-set-option') }}</option>
                                    @foreach ($milestones as $milestone)
                                        <option value={{ $milestone->id }}
                                            {{ $ticket->milestoneid == $milestone->id ? 'selected' : '' }}>
                                            {{ $milestone->headline }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="label-input-container">
                                <label for="related-tickets-select">{{ __('showTicket.related-tickets-label') }}</label>
                                <select defaultValue="{{ $ticket->dependingTicketId }}" class="select"
                                    id="related-tickets-select">
                                    <option value="0">{{ __('showTicket.no-related-tickets-set-option') }}</option>
                                    @foreach ($relatedTickets as $relatedTicket)
                                        <option value={{ $relatedTicket->id }}
                                            {{ $ticket->dependingTicketId == $relatedTicket->id ? 'selected' : '' }}>
                                            {{ $relatedTicket->headline }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if (count($files) > 0)
                                <div class="label-input-container">
                                    <label for="files-list"
                                        class="equal-space">{{ __('showTicket.files-label') }}</label>
                                    <div class="equal-space" id="files-list">
                                        @foreach ($files as $file)
                                            <a class="file-link"
                                                href="<?= BASE_URL ?>/files/get?module={{ $file['module'] }}&encName={{ $file['encName'] }}&ext={{ $file['extension'] }}&realName={{ $file['realName'] }}">
                                                @if ($file['extension'] == 'pdf')
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                @elseif( in_array($file['extension'], ['png','jpg','jpeg','gif']))
                                                    <i class="fa-solid fa-file-image"></i>
                                                @elseif( in_array($file['extension'], ['mp4','mov']))
                                                    <i class="fa-solid fa-film"></i>
                                                @else
                                                    <i class="fa-solid fa-file"></i>
                                                @endif
                                                <span
                                                    class="filename">{{ $file['realName'] }}.{{ $file['extension'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div>
                                @if (count($subtasks) > 0)
                                    <h2 class="sub-header">{{ __('showTicket.subtasks-headline') }}</h2>
                                    <div class="sub-tasks">
                                        @foreach ($subtasks as $subtask)
                                            <div class="sub-task" id='subtask-{{ $subtask['id'] }}'>
                                                <div class="font-bold">{{ $subtask['projectName'] }}:
                                                    {{ $subtask['id'] }}
                                                </div>
                                                <h3>{{ $subtask['headline'] }}</h3>

                                                <div class="sub-task-controls">
                                                    <label class="sr-only"
                                                        for='subtask-status-select-{{ $subtask['id'] }}'>{{ __('showTicket.status-label') }}</label>
                                                    <select defaultValue="{{ $subtask['status'] }}"
                                                        id='subtask-status-select-{{ $subtask['id'] }}' class="select">
                                                        @foreach ($statusLabels as $key => $statusLabel)
                                                            <option value="{{ $key }}"
                                                                {{ $subtask['status'] == $key ? 'selected' : '' }}>
                                                                {{ $statusLabel['name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label class="sr-only"
                                                        for='subtask-user-select-{{ $subtask['id'] }}'>{{ __('showTicket.editor-label') }}</label>
                                                    <select defaultValue="{{ $subtask['editorId'] }}" class="select"
                                                        id='subtask-user-select-{{ $subtask['id'] }}'>
                                                        @foreach ($allUsers as $user)
                                                            <option value={{ $user['id'] }}
                                                                {{ $subtask['editorId'] == $user['id'] ? 'selected' : '' }}>
                                                                {{ $user['firstname'] }}
                                                                {{ $user['lastname'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @php
                                                        $subtaskDateToFinish = $subtask['dateToFinish'] == '0000-00-00 00:00:00' ? '' : strtok($subtask['dateToFinish'], ' ');
                                                    @endphp
                                                    <label class="sr-only"
                                                        for='subtask-date-to-finish-input-{{ $subtask['id'] }}'>{{ __('showTicket.date-to-finish-label') }}</label>
                                                    <input type="date" defaultValue="{{ $subtask['dateToFinish'] }}"
                                                        type="text" class="input"
                                                        id='subtask-date-to-finish-input-{{ $subtask['id'] }}'
                                                        value='{{ $subtaskDateToFinish }}' />
                                                    <label class="sr-only"
                                                        for='subtask-plan-hours-input-{{ $subtask['id'] }}'>{{ __('showTicket.plan-hours-label') }}</label>
                                                    <input defaultValue="{{ $subtask['planHours'] }}" type="text"
                                                        class="input" id='subtask-plan-hours-input-{{ $subtask['id'] }}'
                                                        value='{{ $subtask['planHours'] }}' />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <section class="tabs-container">
                            <div role="tablist" aria-label="Activity Tabs" class="tabs">
                                <button role="tab" id="tab-comments" aria-selected="true" aria-controls="comments-content"
                                    class="tab" tabindex="0">
                                    <i aria-hidden="true"
                                        class="fa-regular fa-comments"></i>{{ __('showTicket.tabs-header-comments') }}
                                </button>
                                <button role="tab" id="tab-logs" aria-selected="false" aria-controls="panel-logs"
                                    class="tab" tabindex="-1">
                                    <i aria-hidden="true"
                                        class="fa-regular fa-clock"></i>{{ __('showTicket.tabs-header-work-logs') }}
                                </button>
                            </div>
                            <div id="comments-content" role="tabpanel" aria-labelledby="tab-comments"
                                class="activity-container tab-content active">
                                <h2 class="h2 sr-only">{{ __('showTicket.tabs-header-comments') }}</h2>
                                <div class="timeline">
                                    @foreach ($comments as $comment)
                                        <div class="comment" id="comment-{{ $comment['id'] }}">
                                            <div class="comment-icon"><i class="fa-solid fa-comment"></i></div>
                                            <div>{{ $comment['firstname'] }} {{ $comment['lastname'] }}</div>
                                            <div class="date">{!! sprintf(__('showTicket.user-commented'), $comment['display_date']) !!}</div>
                                            <div id="comment-text-{{ $comment['id'] }}" class="comment-text">
                                                {!! $tpl->escapeMinimal($comment['text']) !!}
                                            </div>
                                            @if ($comment['editable'])
                                                <button type="button" id="edit-comment-{{ $comment['id'] }}">
                                                    <i aria-hidden="true" class="fa-solid fa-edit"></i>
                                                    <span class="sr-only">{{ __('showTicket.edit-comment') }}</span>
                                                </button>
                                                <button type="button" id="delete-comment-{{ $comment['id'] }}">
                                                    <i aria-hidden="true" class="fa-solid fa-trash"></i>
                                                    <span class="sr-only">{{ __('showTicket.delete-comment') }}</span>
                                                </button>
                                            @endif
                                            <button type="button" id="reply-to-comment-{{ $comment['id'] }}">
                                                <i aria-hidden="true" class="fa-solid fa-reply"></i>
                                                <span class="sr-only">{{ __('showTicket.reply-to-comment') }}</span>
                                            </button>

                                            @if (count($comment['replies']) > 0)
                                                @foreach ($comment['replies'] as $reply)
                                                    <div class="comment indented" id="comment-{{ $reply['id'] }}">
                                                        <div class="comment-icon"><i class="fa-solid fa-comment"></i>
                                                        </div>
                                                        <div>{{ $reply['firstname'] }} {{ $reply['lastname'] }}
                                                        </div>
                                                        <div class="date">{!! sprintf(__('showTicket.user-answered'), $reply['display_date']) !!}
                                                        </div>
                                                        @if ($comment['editable'])
                                                            <button type="button" id="edit-comment-{{ $reply['id'] }}">
                                                                <i aria-hidden="true" class="fa-solid fa-edit"></i>
                                                                <span
                                                                    class="sr-only">{{ __('showTicket.edit-comment') }}</span>

                                                            </button>
                                                            <button type="button"
                                                                id="delete-comment-{{ $reply['id'] }}">
                                                                <i aria-hidden="true" class="fa-solid fa-trash"></i>
                                                                <span
                                                                    class="sr-only">{{ __('showTicket.delete-comment') }}</span>
                                                            </button>
                                                        @endif
                                                        <div class="comment-text">
                                                            {!! $tpl->escapeMinimal($reply['text']) !!}
                                                        </div>
                                                    </div>
                                                    <div id="replace-comment-{{ $comment['id'] }}">
                                                @endforeach
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
            </div>
        </div>
    </div>
    <div id="worklog-content" role="tabpanel" aria-labelledby="tab-worklogs" class="activity-container tab-content">
        <h2 class="h2 sr-only">{{ __('showTicket.tabs-header-work-logs') }}</h2>
        <div class="timeline">
            @foreach ($worklogs as $worklog)
                <div class="worklog">
                    <div class="worklog-icon"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        {!! sprintf(__('showTicket.user-logged'), $worklog['display_name'], $worklog['loggedHours']) !!}
                    </div>
                    <div class="date">{{ $worklog['display_date'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
    </section>

    </main>
    @endif
    </div>
    </div>
    </div>
@endsection
